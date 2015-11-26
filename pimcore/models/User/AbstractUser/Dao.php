<?php
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @category   Pimcore
 * @package    User
 * @copyright  Copyright (c) 2009-2015 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */

namespace Pimcore\Model\User\AbstractUser;

use Pimcore\Model;

class Dao extends Model\Dao\AbstractDao {

    /**
     * @param $id
     * @throws \Exception
     */
    public function getById($id) {

        if($this->model->getType()) {
            $data = $this->db->fetchRow("SELECT * FROM users WHERE `type` = ? AND id = ?", array($this->model->getType(), $id));
        } else {
            $data = $this->db->fetchRow("SELECT * FROM users WHERE `id` = ?", $id);
        }

        if (is_numeric($data["id"])) {
            $this->assignVariablesToModel($data);
        } else {
            throw new \Exception("user doesn't exist");
        }


    }

    /**
     * @param $name
     * @throws \Exception
     */
    public function getByName($name) {
        try {
            $data = $this->db->fetchRow("SELECT * FROM users WHERE `type` = ? AND `name` = ?", array($this->model->getType(), $name));

            if ($data["id"]) {
                $this->assignVariablesToModel($data);
            }
            else {
                throw new \Exception("user doesn't exist");
            }
        }
        catch (\Exception $e) {
            throw $e;
        }

    }


    /**
     * @return bool
     * @throws \Exception
     */
    public function save() {

        if ($this->model->getId()) {
            return $this->model->update();
        }
        return $this->create();
    }

    /**
     * @throws \Exception
     */
    public function create() {
        try {
            $this->db->insert("users", array(
                "name" => $this->model->getName(),
                "type" => $this->model->getType()
            ));

            $this->model->setId($this->db->lastInsertId());

            return $this->save();
        }
        catch (\Exception $e) {
            throw $e;
        }
    }

     /**
     * Quick test if there are children
     *
     * @return boolean
     */
    public function hasChilds() {
        $c = $this->db->fetchOne("SELECT id FROM users WHERE parentId = ?",  $this->model->getId());
        return (bool) $c;
    }


    /**
     * @throws \Exception
     */
    public function update() {
        try {

            if(strlen($this->model->getName()) < 2) {
                throw new \Exception("Name of user/role must be at least 2 characters long");
            }

            $data = array();
            $dataRaw = get_object_vars($this->model);
            foreach ($dataRaw as $key => $value) {
                if (in_array($key, $this->getValidTableColumns("users"))) {

                    if (is_bool($value)) {
                        $value = (int) $value;
                    } else if(in_array($key, ["permissions", "roles", "docTypes", "classes"])) {
                        // permission and roles are stored as csv
                        $value = implode(",", $value);
                    }
                    $data[$key] = $value;
                }
            }

            $this->db->update("users", $data, $this->db->quoteInto("id = ?", $this->model->getId() ));

        }
        catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @throws \Exception
     */
    public function delete() {

        $userId = $this->model->getId();
        \Logger::debug("delete user with ID: " . $userId);

        try {
            $this->db->delete("users", $this->db->quoteInto("id = ?", $userId ));
        }
        catch (\Exception $e) {
            throw $e;
        }
    }
}
