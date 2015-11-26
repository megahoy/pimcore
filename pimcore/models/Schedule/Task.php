<?php
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @category   Pimcore
 * @package    Schedule
 * @copyright  Copyright (c) 2009-2015 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */

namespace Pimcore\Model\Schedule;

use Pimcore\Model;

class Task extends Model\AbstractModel {

    /**
     * @var integer
     */
    public $id;

    /**
     * @var integer
     */
    public $cid;

    /**
     * @var string
     */
    public $ctype;

    /**
     * @var integer
     */
    public $date;

    /**
     * @var string
     */
    public $action;

    /**
     * @var version
     */
    public $version;

    /**
     * @var version
     */
    public $active;

    /**
     * @param integer $id
     * @return Schedule\Task
     */
    public static function getById($id) {

        $cacheKey = "scheduled_task_" . $id;

        try {
            $task = \Zend_Registry::get($cacheKey);
            if(!$task) {
                throw new \Exception("Scheduled Task in Registry is not valid");
            }
        }
        catch (\Exception $e) {
            $task = new self();
            $task->getDao()->getById(intval($id));

            \Zend_Registry::set($cacheKey, $task);
        }

        return $task;
    }

    /**
     * @param array $data
     * @return Schedule\Task
     */
    public static function create($data) {

        $task = new self();
        $task->setValues($data);
        return $task;
    }

    /**
     * @param array $data
     */
    public function __construct($data = array()) {
        $this->setValues($data);
    }

    /**
     * @return integer
     */
    public function getId() {
        return $this->id;
    }

    /**
     * @return integer
     */
    public function getCid() {
        return $this->cid;
    }

    /**
     * @return string
     */
    public function getCtype() {
        return $this->ctype;
    }

    /**
     * @return integer
     */
    public function getDate() {
        return $this->date;
    }

    /**
     * @return string
     */
    public function getAction() {
        return $this->action;
    }

    /**
     * @return integer
     */
    public function getVersion() {
        return $this->version;
    }

    /**
     * @param $id
     * @return $this
     */
    public function setId($id) {
        $this->id = (int) $id;
        return $this;
    }

    /**
     * @param $cid
     * @return $this
     */
    public function setCid($cid) {
        $this->cid = (int) $cid;
        return $this;
    }

    /**
     * @param $ctype
     * @return $this
     */
    public function setCtype($ctype) {
        $this->ctype = $ctype;
        return $this;
    }

    /**
     * @param $date
     * @return $this
     */
    public function setDate($date) {
        $this->date = (int) $date;
        return $this;
    }

    /**
     * @param $action
     * @return $this
     */
    public function setAction($action) {
        $this->action = $action;
        return $this;
    }

    /**
     * @param $version
     * @return $this
     */
    public function setVersion($version) {
        $this->version = $version;
        return $this;
    }

    /**
     * @return boolean
     */
    public function getActive() {
        return $this->active;
    }

    /**
     * @param $active
     * @return $this
     */
    public function setActive($active) {
        if (empty($active)) {
            $active = false;
        }
        $this->active = (bool) $active;
        return $this;
    }

}
