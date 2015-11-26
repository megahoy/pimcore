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

namespace Pimcore\Model\Tool\Targeting\Persona;

use Pimcore\Model;
use Pimcore\Tool\Serialize; 

class Dao extends Model\Dao\AbstractDao {

    /**
     * @param null $id
     * @throws \Exception
     */
    public function getById($id = null) {

        if ($id != null) {
            $this->model->setId($id);
        }

        $data = $this->db->fetchRow("SELECT * FROM targeting_personas WHERE id = ?", $this->model->getId());

        if($data["id"]) {
            $data["conditions"] = Serialize::unserialize($data["conditions"]);
            $data["actions"] = Serialize::unserialize($data["actions"]);
            $this->assignVariablesToModel($data);
        } else {
            throw new \Exception("persona with id " . $this->model->getId() . " doesn't exist");
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
        $this->db->delete("targeting_personas", $this->db->quoteInto("id = ?", $this->model->getId()));
    }

    /**
     * @throws \Exception
     */
    public function update() {

        try {
            $type = get_object_vars($this->model);

            foreach ($type as $key => $value) {
                if (in_array($key, $this->getValidTableColumns("targeting_personas"))) {
                    if(is_array($value) || is_object($value)) {
                        $value = Serialize::serialize($value);
                    }
                    if(is_bool($value)) {
                        $value = (int) $value;
                    }
                    $data[$key] = $value;
                }
            }

            $this->db->update("targeting_personas", $data, $this->db->quoteInto("id = ?", $this->model->getId()));
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
        $this->db->insert("targeting_personas", array());

        $this->model->setId($this->db->lastInsertId());

        return $this->save();
    }
}
