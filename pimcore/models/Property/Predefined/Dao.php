<?php
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @category   Pimcore
 * @package    Property
 * @copyright  Copyright (c) 2009-2015 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */

namespace Pimcore\Model\Property\Predefined;

use Pimcore\Model;

class Dao extends Model\Dao\AbstractDao {

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

        $data = $this->db->fetchRow("SELECT * FROM properties_predefined WHERE id = ?", $this->model->getId());
        $this->assignVariablesToModel($data);
    }


    /**
     * Get the data for the object from database for the given key, or from the key which is set in the object
     *
     * @param string $key
     * @return void
     */
    public function getByKey($key = null) {

        if ($key != null) {
            $this->model->setKey($key);
        }

        $data = $this->db->fetchRow("SELECT * FROM properties_predefined WHERE `key` = ?", $this->model->getKey());
        $this->assignVariablesToModel($data);
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
        $this->db->delete("properties_predefined", $this->db->quoteInto("id = ?", $this->model->getId()));
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
                if (in_array($key, $this->getValidTableColumns("properties_predefined"))) {
                    if(is_bool($value)) {
                        $value = (int)$value;
                    }
                    $data[$key] = $value;
                }
            }

            $this->db->update("properties_predefined", $data, $this->db->quoteInto("id = ?", $this->model->getId() ));
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

        $this->db->insert("properties_predefined", array());

        $this->model->setId($this->db->lastInsertId());

        return $this->save();
    }
}
