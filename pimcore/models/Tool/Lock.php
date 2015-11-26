<?php
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @category   Pimcore
 * @package    Tool
 * @copyright  Copyright (c) 2009-2015 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */

namespace Pimcore\Model\Tool;

use Pimcore\Model;

class Lock extends Model\AbstractModel {

    /**
     * @var string
     */
    public $id;

    /**
     * @var int
     */
    public $date;

    /**
     * @var array
     */
    protected static $acquiredLocks = array();

    /**
     * @var Lock
     */
    protected static $instance;

    /**
     * @return Lock
     */
    protected static function getInstance () {
        if(!self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * @param string $key
     */
    public static function acquire ($key, $expire = 120, $refreshInterval = 1) {

        $instance = self::getInstance();
        $instance->getDao()->acquire($key, $expire, $refreshInterval);

        self::$acquiredLocks[$key] = $key;
    }

    /**
     * @param string $key
     */
    public static function release ($key) {

        $instance = self::getInstance();
        $instance->getDao()->release($key);

        unset(self::$acquiredLocks[$key]);
    }

    /**
     * @param string $key
     * @return bool
     */
    public static function lock ($key) {

        $instance = self::getInstance();
        return $instance->getDao()->lock($key);
    }

    /**
     * @param $key
     * @param int $expire
     * @return mixed
     */
    public static function isLocked ($key, $expire = 120) {
        $instance = self::getInstance();
        return $instance->getDao()->isLocked($key, $expire);
    }

    /**
     * @param $key
     * @return Lock
     */
    public static function get($key) {
        $lock = new self;
        $lock->getById($key);
        return $lock;
    }

    /**
     *
     */
    public static function releaseAll() {
        $locks = self::$acquiredLocks;

        foreach($locks as $key) {
            self::release($key);
        }
    }

    /**
     * @param int $date
     */
    public function setDate($date)
    {
        $this->date = $date;
    }

    /**
     * @return int
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * @param string $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }
}
