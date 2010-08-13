<?php
/**
 * Zend Framework
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://framework.zend.com/license/new-bsd
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@zend.com so we can send you a copy immediately.
 *
 * @category   ZendX
 * @package    Doctrine2
 * @copyright  Copyright (c) 2005-2009 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id$
 */

namespace ZendX\Doctrine2;

/**
 * Doctrine 2 and Firebug Profiler
 *
 * @uses       \Zend_Wildfire_Plugin_FirePhp
 * @uses       \Doctrine\DBAL\Logging\SqlLogger
 * @category   ZendX
 * @package    Doctrine2
 * @copyright  Copyright (c) 2005-2009 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class FirebugProfiler
    implements \Doctrine\DBAL\Logging\SqlLogger
{

    /**
     * Sum of query times
     * 
     * @var float
     */
    protected $_totalMS = 0;

    /**
     * Total number of queries logged
     *
     * @var integer
     */
    protected $_queryCount = 0;

    /**
     * Table of queries and their times
     *
     * @var \Zend_Wildfire_Plugin_FirePhp_TableMessage
     */
    protected $_message;

    public function __construct()
    {
        $this->_message = new \Zend_Wildfire_Plugin_FirePhp_TableMessage('Doctrine Queries');
        $this->_message->setBuffered(true);
        $this->_message->setHeader(array('Time','Event','Parameters'));
        $this->_message->setOption('includeLineNumbers', false);
        \Zend_Wildfire_Plugin_FirePhp::getInstance()->send($this->_message, 'Doctrine Queries');
    }

    /**
     * @param string $sql The SQL statement that was executed
     * @param array $params Arguments for SQL
     * @param float $executionMS Time for query to return
     */
    public function logSQL($sql, array $params = null, $executionMS = null)
    {
        $this->_totalMS += $executionMS;
        ++$this->_queryCount;

        $this->_message->addRow(array(
            number_format($executionMS, 5),
            $sql,
            $params
        ));

        $this->updateLabel();
    }

    /**
     * Sets the label for the FireBug entry
     */
    public function updateLabel()
    {
        $this->_message->setLabel(
            sprintf('Doctrine Queries (%d @ %f sec)',
            $this->_queryCount,
            number_format($this->_totalMS, 5))
        );
    }
}