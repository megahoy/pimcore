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

namespace Pimcore\Db;

use Pimcore\Db;

class Wrapper {

    /**
     * @var \Zend_Db_Adapter_Abstract
     */
    protected $resource;

    /**
     * use a dedicated connection for write queries if configured
     * @var \Zend_Db_Adapter_Abstract
     */
    protected $writeResource = null;

    /**
     * @var bool
     */
    protected $inTransaction = false;

    /**
     * @param $writeResource
     * @return $this
     */
    public function setWriteResource($writeResource)
    {
        $this->writeResource = $writeResource;
        return $this;
    }

    /**
     * @return \Zend_Db_Adapter_Abstract
     */
    public function getWriteResource()
    {
        if($this->writeResource === null) {
            // get the \Zend_Db_Adapter_Abstract not the wrapper
            try {
                $this->writeResource = Db::getConnection(true, true);
            } catch (\Exception $e) {
                $this->writeResource = false;
            }
        }

        if($this->writeResource !== false) {
            return $this->writeResource;
        }

        // use the default connection if we don't have a dedicated write connection config
        return $this->getResource();
    }

    /**
     *
     */
    public function closeWriteResource() {
        $this->closeConnectionResource($this->writeResource);
        $this->writeResource = null;
    }

    /**
     * @param $resource
     */
    public function __construct($resource = false) {
        if($resource) {
            $this->setResource($resource);
        }
    }

    /**
     * @param $resource
     * @return void
     */
    public function setResource($resource)
    {
        $this->resource = $resource;
        return $this;
    }

    /**
     * @return \Zend_Db_Adapter_Abstract
     */
    public function getResource()
    {
        if(!$this->resource) {
            // get the \Zend_Db_Adapter_Abstract not the wrapper
            $this->resource = Db::getConnection(true);
        }
        return $this->resource;
    }

    /**
     *
     */
    public function closeResource() {
        $this->closeConnectionResource($this->resource);
        $this->resource = null;
    }

    /**
     * @param \Zend_Db_Adapter_Abstract $resource
     */
    protected function closeConnectionResource($resource) {
        if($resource) {
            try {
                $connectionId = null;

                // unfortunately mysqli doesn't throw an exception in the case the connection is lost (issues a warning)
                // and when sending a query to the broken connection (eg. when forking)
                // so we have to handle mysqli and pdo_mysql differently
                if($resource instanceof \Zend_Db_Adapter_Mysqli) {
                    if($resource->getConnection()) {
                        $connectionId = $resource->getConnection()->thread_id;
                    }
                } else if ($resource instanceof \Zend_Db_Adapter_Pdo_Mysql) {
                    $connectionId = $resource->fetchOne("SELECT CONNECTION_ID()");
                }
                \Logger::debug(get_class($resource) . ": closing MySQL-Server connection with ID: " . $connectionId);

                $resource->closeConnection();
            } catch (\Exception $e) {
                // this is the case when the mysql connection has gone away (eg. when forking using pcntl)
                \Logger::info($e);
            }
        }
    }


    /**
     * insert on dublicate key update extension to the \Zend_Db Adapter
     * @param $table
     * @param array $data
     * @return mixed
     * @throws \Zend_Db_Adapter_Exception
     */
    public function insertOrUpdate($table, array $data)
    {
        // extract and quote col names from the array keys
        $i = 0;
        $bind = array();
        $cols = array();
        $vals = array();
        foreach ($data as $col => $val) {
            $cols[] = $this->quoteIdentifier($col, true);
            if ($val instanceof \Zend_Db_Expr) {
                $vals[] = $val->__toString();
            } else {
                if ($this->supportsParameters('positional')) {
                    $vals[] = '?';
                    $bind[] = $val;
                } else {
                    if ($this->supportsParameters('named')) {
                        $bind[':col' . $i] = $val;
                        $vals[] = ':col' . $i;
                        $i++;
                    } else {
                        /** @see \Zend_Db_Adapter_Exception */
                        throw new \Zend_Db_Adapter_Exception(get_class($this->getResource()) . " doesn't support positional or named binding");
                    }
                }
            }
        }


        // build the statement
        $set = array();
        foreach ($cols as $i => $col) {
            $set[] = sprintf('%s = %s', $col, $vals[$i]);
        }

        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s) ON DUPLICATE KEY UPDATE %s;',
            $this->quoteIdentifier($table, true),
            implode(', ', $cols),
            implode(', ', $vals),
            implode(', ', $set)
        );

        // execute the statement and return the number of affected rows
        if ($this->supportsParameters('positional')) {
            $bind = array_values($bind);
        }

        $bind = array_merge($bind, $bind);

        $stmt = $this->query($sql, $bind);
        $result = $stmt->rowCount();
        return $result;
    }

    /**
     * @return mixed
     */
    public function beginTransaction() {
        $this->inTransaction = true;
        return $this->__call("beginTransaction", []);
    }

    /**
     * @return mixed
     */
    public function commit() {
        $return = $this->__call("commit", []);
        $this->inTransaction = false;
        return $return;
    }

    /**
     * @throws \Exception
     * @param  $method
     * @param  $args
     * @return mixed
     */
    public function __call($method, $args) {
        try {
            $r = $this->callResourceMethod($method, $args);
            return $r;
        }
        catch (\Exception $e) {
            return Db::errorHandler($method, $args, $e);
        }
    }

    /**
     * @param string $method
     * @param array $args
     * @return mixed
     */
    public function callResourceMethod ($method, $args) {

        $resource = $this->getResource();
        if($this->inTransaction || Db::isWriteQuery($method, $args)) {
            $resource = $this->getWriteResource();
        }

        $capture = false;

        if(\Pimcore::inAdmin()) {
            $methodsToCheck = array("query","update","delete","insert");
            if(in_array($method, $methodsToCheck)) {
                $capture = true;
                Db::startCapturingDefinitionModifications($resource, $method, $args);
            }
        }

        $r = call_user_func_array(array($resource, $method), $args);

        if(\Pimcore::inAdmin() && $capture) {
            Db::stopCapturingDefinitionModifications($resource);
        }

        return $r;
    }
}
