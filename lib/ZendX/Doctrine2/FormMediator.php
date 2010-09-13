<?php

/**
 * Based on work by Benjamin Eberlei (http://framework.zend.com/svn/framework/standard/branches/user/beberlei/zfdoctrine/library/Zend/Form/ObjectMediator.php)
 */

namespace ZendX\Doctrine2;

class FormMediator
{
    /**
     * @var string
     */
    protected $_className = null;

    /**
     * @var Zend_Form
     */
    protected $_form = null;

    /**
     * @var array
     */
    protected $_fields = array();

    /**
     * @var Object
     */
    protected $_instance = null;

    /**
     *
     * @param Zend_Form $form
     * @param string|Object $class
     * @param array $fields
     */
    public function __construct(\Zend_Form $form, $class, array $fields = array())
    {
        $this->setForm($form);
        if (is_object($class)) {
            $this->setInstance($class);
        } else {
            $this->setClassName($class);
        }
        $this->setFields($fields);

        $this->init();
    }

    public function init()
    {
        // save for subclasses
    }

    /**
     * @param string $name
     * @param array $options
     * @return FormMediator
     */
    public function addField($name, array $options = array())
    {
        if (!isset($options['setMethod'])) {
            $options['setMethod'] = "set".ucfirst($name);
        }
        if (!isset($options['getMethod'])) {
            $options['getMethod'] = "get".ucfirst($name);
        }

        $defaultOptions = array(
            'baseMethod' => array(),
            'getterUseBase' => true,
            'setterUseBase' => true,
            'populateFilters' => array(),
            'filterMethod' => false,
            'validatorMethod' => false,
            'validatorErrorMessage' => 'Object validator method failed.',
        );
        $options = array_merge($defaultOptions, $options);
        $this->_fields[$name] = $options;
        return $this;
    }

    /**
     * @return void
     */
    public function populate()
    {
        $data = array();
        foreach ($this->_fields AS $name => $field) {

            $data[$name] = $this->callFieldGetMethod($field);
            if (count($field['populateFilters'])) {
                foreach ($field['populateFilters'] AS $filter) {
                    /* @var $filter Zend_Filter_Interface */
                    $data[$name] = $filter->filter($data[$name]);
                }
            }
        }
        $this->_form->populate($data);
    }

    protected function callFieldGetMethod($field)
    {
        if ($get = $field['getMethod']) {
            $instance = $this->getInstance();
            if ($field['getterUseBase']) {
                $instance = $this->callMethod($instance, $field['baseMethod']);
            }
            return $this->callMethod($instance, $get);
        }
        return null;
    }

    protected function callFieldSetMethod($field, $value)
    {
        $set = $field['setMethod'];
        $instance = $this->getInstance();
        if ($field['setterUseBase']) {
            $instance = $this->callMethod($instance, $field['baseMethod']);
        }
        return $this->callMethod($instance, $set, array($value));
    }

    protected function callMethod($instance, $method, $args = array())
    {
        if (is_null($instance) || is_null($method)) {
            return null;
        }

        if (is_array($method)) {
            if (empty($method)) {
                return $instance;
            }
            $instance = $this->callMethod($instance, $method[0], $args);
            $method = array_splice($method, 1);
            if (count($method) == 1) {
                $method = $method[0];
            }
            return $this->callMethod($instance, $method, $args);

        } else if (is_string($method)) {
            return call_user_func_array(array($instance, $method), $args);
        } else {
            $args = array_merge(array($instance), $args);
            return call_user_func_array($method, $args);
        }
    }

    /**
     * @param  array $data
     * @return bool
     */
    public function isValid($data)
    {
        $instance = $this->getInstance();

        $isValid = false;
        if ($this->_form->isValid($data)) {
            $isValid = true;
            foreach ($this->_fields AS $name => $field) {
                if ($field['validatorMethod'] !== false) {
                    if (!$this->callMethod($instance, $field['validatorMethod'], array($this->getForm()->getElement($name)))) {
                        $element = $this->_form->getElement($name);
                        $element->addError($field['validatorErrorMessage']);

                        $isValid = false;
                    }
                }
            }
        }
        return $isValid;
    }

    /**
     * Transfer the current form element values onto the attached instance
     *
     * @param bool $suppressArrayNotation
     */
    public function transferValues($suppressArrayNotation = false)
    {
        $instance = $this->getInstance();

        $values = $this->_form->getValues($suppressArrayNotation);
        $this->setData($values);

        return $this->_instance;
    }

    /**
     * Calls setters for each key with the given values
     *
     * @param array $values
     */
    public function setData(array $values)
    {
        foreach ($this->_fields AS $name => $field) {

            if ($field['setMethod'] === false || !array_key_exists($name, $values)) {
                continue;
            }

            $value = $values[$name];

            if ($field['filterMethod'] !== false) {
                $filterMethod = $field['filterMethod'];
                $value = $this->callMethod($this->_instance, $filterMethod, array($value));
            }

            $set = $field['setMethod'];
            $this->callFieldSetMethod($field, $value);

        }
    }

    /**
     * @param Zend_Form $form
     * @return FormMediator
     */
    public function setForm(\Zend_Form $form)
    {
        $this->_form = $form;
        return $this;
    }

    /**
     * @return Zend_Form
     */
    public function getForm() { return $this->_form; }

    /**
     * @param string $className
     * @return FormMediator
     */
    public function setClassName($className)
    {
        $this->_className = $className;
        return $this;
    }

    /**
     * @return string
     */
    public function getClassName() { return $className; }

    /**
     * @param array $field
     */
    public function setFields(array $fields)
    {
        $this->clearFields();
        $this->addFields($fields);
    }

    public function addFields(array $fields)
    {
        foreach ($fields AS $field => $options) {
            $this->addField($field, $options);
        }
    }

    public function removeField($fieldName, $removeFormField = false)
    {
        unset($this->_fields[$fieldName]);
        if ($removeFormField && $this->getForm()->getElement($fieldName)) {
            $this->getForm()->removeElement($fieldName);
        }
    }

    public function clearFields()
    {
        $this->_fields = array();
    }

    /**
     * @param Object $instance
     * @return FormMediator
     */
    public function setInstance($instance)
    {
        if (is_null($this->_className)) {
            $this->setClassName(get_class($instance));
        }

        if (!($instance instanceof $this->_className)) {
            throw new \Exception('Instance is not of the correct class type.');
        }

        $this->_instance = $instance;
        return $this;
    }

    /**
     * @return Object
     */
    public function getInstance()
    {
        if (null === $this->_instance) {
            $this->_instance = new $this->_className;
        }
        return $this->_instance;
    }
}