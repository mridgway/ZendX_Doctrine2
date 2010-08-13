<?php

namespace ZendX\Doctrine2;

class Paginator implements \Zend_Paginator_Adapter_Interface
{
    /**
     * @var \Doctrine\ORM\QueryBuilder
     */
    protected $_qb = null;

    protected $_countQb = null;

    /**
     * @var int
     */
    protected $_rowCount = null;

    /**
     * @param \Doctrine\ORM\QueryBuilder $qb
     */
    public function __construct(\Doctrine\ORM\QueryBuilder $qb)
    {
        $this->_qb = $qb;
    }

    /**
     * @param int $count
     * @return Paginator
     */
    public function setRowCount($count)
    {
        $this->_rowCount = (int)$count;
        return $this;
    }

    /**
     * Returns the total number of rows in the collection.
     *
     * @return integer
     */
    public function count()
    {
        if($this->_rowCount > 0) {
            return $this->_rowCount;
        } else {
            if (null === $this->_countQb) {
                $this->_countQb = clone $this->_qb;
                $from = $this->_countQb->getDqlPart('from');
                $identifierNames = \Zend_Registry::get('doctrine')->getClassMetadata($from[0]->getFrom())->getIdentifierFieldNames();
                $this->_countQb->select('count('.$this->_countQb->getRootAlias().'.' . $identifierNames[0] . ')');
            }
            return $this->_rowCount = $this->_countQb->getQuery()->getSingleScalarResult();
        }
    }

    /**
     * Returns an collection of items for a page.
     *
     * @param  integer $offset Page offset
     * @param  integer $itemCountPerPage Number of items per page
     * @return array
     */
    public function getItems($offset, $itemCountPerPage)
    {
        $this->_qb->setFirstResult($offset)->setMaxResults($itemCountPerPage);
        return $this->_qb->getQuery()->getResult();
    }

    public function setCountQueryBuilder(\Doctrine\ORM\QueryBuilder $qb)
    {
        $this->_countQb = $qb;
    }
}