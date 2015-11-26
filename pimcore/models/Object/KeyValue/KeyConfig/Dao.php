<?php
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @category   Pimcore
 * @package    Object
 * @copyright  Copyright (c) 2009-2015 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */

namespace Pimcore\Model\Object\KeyValue\KeyConfig;

use Pimcore\Model;

class Dao extends Model\Dao\AbstractDao {

    const TABLE_NAME_KEYS = "keyvalue_keys";

    /**
     * Get the data for the object from database for the given id, or from the ID which is set in the object
     *
     * @param integer $id
     * @return void
     */
    public function getById($id = null) {

        if ($id != null) {
            $this->model->setId($id);
        }

        $data = $this->db->fetchRow("SELECT * FROM " . self::TABLE_NAME_KEYS . " WHERE id = ?", $this->model->getId());

        $this->assignVariablesToModel($data);
    }

    /**
     * @param null $name
     * @throws \Exception
     */
    public function getByName($name = null) {

        if ($name != null) {
            $this->model->setName($name);
        }

        $name = $this->model->getName();
        $groupId = $this->model->getGroup();

        $stmt = "SELECT * FROM " . self::TABLE_NAME_KEYS . " WHERE name = '" . $name . "'";
        if ($groupId > 0) {
            $stmt .= " AND `group` = " . $groupId;
        }

        $data = $this->db->fetchRow($stmt);

        if($data["id"]) {
            $this->assignVariablesToModel($data);
        } else {
            throw new \Exception("KeyConfig with name: " . $this->model->getName() . " does not exist");
        }
    }

    /**
     * Save object to database
     *
     * @return void
     */
    public function save() {
        if ($this->model->getId()) {
            return $this->model->update();
        }
        return $this->create();
    }

    /**
     * Deletes object from database
     *
     * @return void
     */
    public function delete() {
        $this->db->delete(self::TABLE_NAME_KEYS, $this->db->quoteInto("id = ?", $this->model->getId()));
    }

    /**
     * @throws \Exception
     */
    public function update() {
        try {
            $ts = time();
            $this->model->setModificationDate($ts);

            $type = get_object_vars($this->model);

            foreach ($type as $key => $value) {
                if (in_array($key, $this->getValidTableColumns(self::TABLE_NAME_KEYS))) {
                    if(is_bool($value)) {
                        $value = (int) $value;
                    }
                    if(is_array($value) || is_object($value)) {
                        if($this->model->getType() == 'select'){
                            $value = \Zend_Json::encode($value);
                        }else{
                            $value = \Pimcore\Tool\Serialize::serialize($value);
                        }
                    }

                    $data[$key] = $value;
                }
            }

            $this->db->update(self::TABLE_NAME_KEYS, $data, $this->db->quoteInto("id = ?", $this->model->getId()));
            return $this->model;
        }
        catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Create a new record for the object in database
     *
     * @return boolean
     */
    public function create() {
        $ts = time();
        $this->model->setCreationDate($ts);
        $this->model->setModificationDate($ts);

        $this->db->insert(self::TABLE_NAME_KEYS, array());

        $this->model->setId($this->db->lastInsertId());

        return $this->save();
    }
}
