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

namespace Pimcore\Model\Tool\Lock;

use Pimcore\Model;

class Dao extends Model\Dao\AbstractDao {

    /**
     * @param $key
     * @param int $expire
     * @return bool
     */
    public function isLocked ($key, $expire = 120) {
        if(!is_numeric($expire)) {
            $expire = 120;
        }

        $lock = $this->db->fetchRow("SELECT * FROM locks WHERE id = ?", $key);

        // a lock is only valid for a certain time (default: 2 minutes)
        if(!$lock) {
            return false;
        } else if(is_array($lock) && array_key_exists("id", $lock) && $lock["date"] < (time()-$expire)) {
            if($expire > 0){
                \Logger::debug("Lock '" . $key . "' expired (expiry time: " . $expire . ", lock date: " . $lock["date"] . " / current time: " . time() . ")");
                $this->release($key);
                return false;
            }
        }

        return true;
    }

    /**
     * @param $key
     * @param int $expire
     * @param int $refreshInterval
     */
    public function acquire ($key, $expire = 120, $refreshInterval = 1) {

        \Logger::debug("Acquiring key: '" . $key . "' expiry: " . $expire);

        if(!is_numeric($refreshInterval)) {
            $refreshInterval = 1;
        }

        while(true) {
            while($this->isLocked($key, $expire)) {
                sleep($refreshInterval);
            }

            try {
                $this->lock($key, false);
                return true;
            } catch (\Exception $e) {
                \Logger::debug($e);
            }
        }
    }

    /**
     * @param $key
     */
    public function release ($key) {

        \Logger::debug("Releasing: '" . $key . "'");

        $this->db->delete("locks", "id = " . $this->db->quote($key));
    }

    /**
     * @param $key
     * @param bool $force
     */
    public function lock ($key, $force = true) {

        \Logger::debug("Locking: '" . $key . "'");

        $updateMethod = $force ? "insertOrUpdate" : "insert";

        $this->db->$updateMethod("locks", array(
            "id" => $key,
            "date" => time()
        ));
    }

    public function getById($key) {
        $lock = $this->db->fetchRow("SELECT * FROM locks WHERE id = ?", $key);
        $this->assignVariablesToModel($lock);
    }
}
