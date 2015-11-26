<?php
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @category   Pimcore
 * @package    Element
 * @copyright  Copyright (c) 2009-2015 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */

namespace Pimcore\Model\Element;

use Pimcore\Model;

class Editlock extends Model\AbstractModel {

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
    public $userId;

    /**
     * @var string
     */
    public $sessionId;

    /**
     * @var integer
     */
    public $date;

    /**
     * @var
     */
    public $cpath;

    /**
     * @param $cid
     * @param $ctype
     * @return bool
     */
    public static function isLocked($cid, $ctype) {

        if ($lock = self::getByElement($cid, $ctype)) {
            if ((time() - $lock->getDate()) > 3600 || $lock->getSessionId() == session_id()) {
                // lock is out of date unlock it
                self::unlock($cid, $ctype);
                return false;
            }
            return true;
        }

        return false;
    }

    /**
     * @param $cid
     * @param $ctype
     * @return null|Editlock
     */
    public static function getByElement($cid, $ctype) {

        try {
            $lock = new self();
            $lock->getDao()->getByElement($cid, $ctype);
            return $lock;
        }
        catch (\Exception $e) {
            return null;
        }
    }

    /**
     * @param $sessionId
     * @return bool|null
     */
    public static function clearSession($sessionId) {
        try {
            $lock = new self();
            $lock->getDao()->clearSession($sessionId);
            return true;
        }
        catch (\Exception $e) {
            return null;
        }
    }

    /**
     * @param $cid
     * @param $ctype
     * @return bool|Editlock
     */
    public static function lock($cid, $ctype) {

        // try to get user
        if(!$user = \Pimcore\Tool\Admin::getCurrentUser()) {
            return false;
        }

        $lock = new self();
        $lock->setCid($cid);
        $lock->setCtype($ctype);
        $lock->setDate(time());
        $lock->setUserId($user->getId());
        $lock->setSessionId(session_id());
        $lock->save();

        return $lock;
    }

    /**
     * @param $cid
     * @param $ctype
     * @return bool
     */
    public static function unlock($cid, $ctype) {
        if ($lock = self::getByElement($cid, $ctype)) {
            $lock->delete();
        }
        return true;
    }

    /**
     * @return integer
     */
    public function getCid() {
        return $this->cid;
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
    public function getUserId() {
        return $this->userId;
    }

    /**
     * @return void
     */
    public function setCid($cid) {
        $this->cid = (int) $cid;
        return $this;
    }

    /**
     * @param integer $id
     * @return void
     */
    public function setId($id) {
        $this->id = (int) $id;
        return $this;
    }

    /**
     * @param integer $userId
     * @return void
     */
    public function setUserId($userId) {
        if ($userId) {
            if ($user = Model\User::getById($userId)) {
                $this->userId = (int) $userId;
                $this->setUser($user);
            }
        }
        return $this;
    }

    /**
     * @return string
     */
    public function getCtype() {
        return $this->ctype;
    }

    /**
     * @param string $ctype
     * @return void
     */
    public function setCtype($ctype) {
        $this->ctype = (string) $ctype;
        return $this;
    }

    /**
     * @return string
     */
    public function getSessionId() {
        return $this->sessionId;
    }

    /**
     * @param string $sessionId
     * @return void
     */
    public function setSessionId($sessionId) {
        $this->sessionId = (string) $sessionId;
        return $this;
    }

    /**
     * @return User
     */
    public function getUser() {
        return $this->user;
    }

    /**
     * @param User $user
     * @return void
     */
    public function setUser($user) {
        $this->user = $user;
        return $this;
    }

    /**
     * @return integer
     */
    public function getDate() {
        return $this->date;
    }

    /**
     * @param integer $date
     * @return void
     */
    public function setDate($date) {
        $this->date = (int) $date;
        return $this;
    }

    /**
     * @param $cpath
     * @return $this
     */
    public function setCpath($cpath)
    {
        $this->cpath = $cpath;
        return $this;
    }

    /**
     * @return
     */
    public function getCpath()
    {
        return $this->cpath;
    }
}
