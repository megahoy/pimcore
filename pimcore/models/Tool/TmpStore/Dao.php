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

namespace Pimcore\Model\Tool\TmpStore;

use Pimcore\Model;

class Dao extends Model\Dao\AbstractDao {

    /**
     * @param $id
     * @param $data
     * @param $tag
     * @param $lifetime
     * @return bool
     */
    public function add ($id, $data, $tag, $lifetime) {

        try {
            $serialized = false;
            if(is_object($data) || is_array($data)) {
                $serialized = true;
                $data = serialize($data);
            }

            $this->db->insertOrUpdate("tmp_store", [
                "id" => $id,
                "data" => $data,
                "tag" => $tag,
                "date" => time(),
                "expiryDate" => (time()+$lifetime),
                "serialized" => (int) $serialized
            ]);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * @param $id
     */
    public function delete ($id) {
        $this->db->delete("tmp_store", "id = " . $this->db->quote($id));
    }

    /**
     * @param $id
     * @return bool
     */
    public function getById($id) {
        $item = $this->db->fetchRow("SELECT * FROM tmp_store WHERE id = ?", $id);

        if(is_array($item) && array_key_exists("id", $item)) {

            if($item["serialized"]) {
                $item["data"] = unserialize($item["data"]);
            }

            $this->assignVariablesToModel($item);
            return true;
        }

        return false;
    }

    /**
     *
     */
    public function cleanup() {
        $this->db->delete("tmp_store", "expiryDate < " . time());
    }

    /**
     * @param $tag
     * @return array
     */
    public function getIdsByTag($tag) {
        $items = $this->db->fetchCol("SELECT id FROM tmp_store WHERE tag = ?", [$tag]);
        return $items;
    }
}
