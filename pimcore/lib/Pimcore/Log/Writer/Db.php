<?php
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2015 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */

namespace Pimcore\Log\Writer;

use Pimcore\Db as Database;
use Pimcore\Log;
use Zend_Log_Writer_Db;
use Zend_Log;

class Db extends Zend_Log_Writer_Db {

    /**
     * @param int|\Zend_Db_Adapter $filterPriority
     */
    public function __construct($filterPriority = \Zend_Log::ERR) {

        $mapping = array(
            'priority' => 'priority',
            'message' => 'message',
            'timestamp' => 'timestamp',
            'info' => 'info',
            'fileobject' => 'fileobject',
            'relatedobject' => 'relatedobject',
            'relatedobjecttype' => 'relatedobjecttype',
            'component' => 'component',
            'source' => 'source'
        );
        parent::__construct(Database::get(), Log\Helper::ERROR_LOG_TABLE_NAME, $mapping);

        $this->setFilterPriority($filterPriority);
    }

    public function setFilterPriority($filterPriority) {
        $unsetKeys = array();

        foreach($this->_filters as $key => $filter) {
            if($filter instanceof \Zend_Log_Filter_Priority) {
                $unsetKeys[] = $key;
            }
        }

        foreach($unsetKeys as $key) {
            unset($this->_filters[$key]);
        }

        $filter = new \Zend_Log_Filter_Priority($filterPriority);
        $this->addFilter($filter);
    }

    /**
     * @static
     * @return string[]
     */
    public static function getComponents() {
        $db = Database::get();

        $components = $db->fetchCol("SELECT component FROM " . Log\Helper::ERROR_LOG_TABLE_NAME . " WHERE NOT ISNULL(component) GROUP BY component;");
        return $components;
    }

    /**
     * @static
     * @return string[]
     */
    public static function getPriorities() {
        $priorities = array();
        $priorityNames = array(
            Zend_Log::DEBUG => "DEBUG",
            Zend_Log::INFO => "INFO",
            Zend_Log::NOTICE => "INFO",
            Zend_Log::WARN => "WARN",
            Zend_Log::ERR => "ERR",
            Zend_Log::CRIT => "CRIT",
            Zend_Log::ALERT => "ALERT",
            Zend_Log::EMERG => "EMERG"
        );

        $db = Database::get();

        $priorityNumbers = $db->fetchCol("SELECT priority FROM " . Log\Helper::ERROR_LOG_TABLE_NAME . " WHERE NOT ISNULL(priority) GROUP BY priority;");
        foreach($priorityNumbers as $priorityNumber) {
            $priorities[$priorityNumber] = $priorityNames[$priorityNumber];
        }

        return $priorities;
    }

    protected function _write($event) {
        $timestamp = strtotime($event["timestamp"]);
        $event["timestamp "] = date("Y-m-d H:i:s", $timestamp);

        parent::_write($event);

    }
}
