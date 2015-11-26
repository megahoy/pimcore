<?php
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @category   Pimcore
 * @package    Staticroute
 * @copyright  Copyright (c) 2009-2015 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */

namespace Pimcore\Model\Staticroute;

use Pimcore\Model;

class Dao extends Model\Dao\AbstractDao {

    /**
     * @param null $id
     * @throws \Exception
     */
    public function getById($id = null) {

        if ($id != null) {
            $this->model->setId($id);
        }

        $data = $this->db->fetchRow("SELECT * FROM staticroutes WHERE id = ?", $this->model->getId());
        $this->assignVariablesToModel($data);
        
        if($data["id"]) {
            $this->assignVariablesToModel($data);
        } else {
            throw new \Exception("Route with id: " . $this->model->getId() . " does not exist");
        }
    }

    /**
     * @param null $name
     * @param null $siteId
     * @throws \Exception
     */
    public function getByName($name = null, $siteId = null) {

        if ($name != null) {
            $this->model->setName($name);
        }
        $data = $this->db->fetchRow("SELECT id FROM staticroutes WHERE name = ? AND (siteId IS NULL OR siteId = '' OR siteId = ?) ORDER BY siteId DESC", array($this->model->getName(), $siteId));
        
        if($data["id"]) {
            $this->assignVariablesToModel($data);
        } else {
            throw new \Exception("Route with name: " . $this->model->getName() . " does not exist");
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
        $this->db->delete("staticroutes", $this->db->quoteInto("id = ?", $this->model->getId()));
        
        $this->model->clearDependentCache();
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
                if (in_array($key, $this->getValidTableColumns("staticroutes"))) {
                    $data[$key] = $value;
                }
            }


            $this->db->update("staticroutes", $data, $this->db->quoteInto("id = ?", $this->model->getId()));
        }
        catch (\Exception $e) {
            throw $e;
        }
        
        $this->model->clearDependentCache();
    }

    /**
     * Create a new record for the object in database
     *
     * @return boolean
     */
    public function create() {
        $ts = time();
        $this->model->setModificationDate($ts);
        $this->model->setCreationDate($ts);

        $this->db->insert("staticroutes", array());

        $this->model->setId($this->db->lastInsertId());

        return $this->save();
    }
}
