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

namespace Pimcore\Model\Object\Data\ObjectMetadata;

use Pimcore\Model;
use Pimcore\Model\Object;

class Dao extends Model\Dao\AbstractDao {

    /**
     * @param Object\Concrete $object
     * @param $ownertype
     * @param $ownername
     * @param $position
     * @param $type
     * @throws \Zend_Db_Adapter_Exception
     */
    public function save(Object\Concrete $object, $ownertype, $ownername, $position, $type = "object") {
        $table = $this->getTablename($object);

        $dataTemplate = array("o_id" => $object->getId(),
            "dest_id" => $this->model->getElement()->getId(),
            "fieldname" => $this->model->getFieldname(),
            "ownertype" => $ownertype,
            "ownername" => $ownername ? $ownername : "",
            "position" => $position ?  $position : "0",
            "type" => $type ?  $type : "object");

        foreach($this->model->getColumns() as $column) {
            $getter = "get" . ucfirst($column);
            $data = $dataTemplate;
            $data["column"] = $column;
            $data["data"] = $this->model->$getter();
            $this->db->insert($table, $data);
        }

    }

    /**
     * @param $object
     * @return string
     */
    private function getTablename($object) {
        return "object_metadata_" . $object->getClassId();
    }

    /**
     * @param Object\Concrete $source
     * @param $destination
     * @param $fieldname
     * @param $ownertype
     * @param $ownername
     * @param $position
     * @param $type
     * @return null|Model\Dao\Pimcore_Model_Abstract
     */
    public function load(Object\Concrete $source, $destination, $fieldname, $ownertype, $ownername, $position, $type = "object") {

        if ($type == "object") {
            $typeQuery = " AND (type = 'object' or type = '')";
        } else {
            $typeQuery = " AND type = " . $this->db->quote($type);
        }

        $dataRaw = $this->db->fetchAll("SELECT * FROM " . $this->getTablename($source) . " WHERE o_id = ? AND dest_id = ? AND fieldname = ? AND ownertype = ? AND ownername = ? and position = ? " . $typeQuery, array($source->getId(), $destination->getId(), $fieldname, $ownertype, $ownername, $position));
        if(!empty($dataRaw)) {
            $this->model->setElement($destination);
            $this->model->setFieldname($fieldname);
            $columns = $this->model->getColumns();
            foreach($dataRaw as $row) {
                if(in_array($row['column'], $columns)) {
                    $setter = "set" . ucfirst($row['column']);
                    $this->model->$setter($row['data']);
                }
            }

            return $this->model;
        } else {
            return null;
        }
    }

    /**
     * @return void
     */
    public function createOrUpdateTable($class) {

        $classId = $class->getId();
        $table = "object_metadata_" . $classId;

        $this->db->query("CREATE TABLE IF NOT EXISTS `" . $table . "` (
              `o_id` int(11) NOT NULL default '0',
              `dest_id` int(11) NOT NULL default '0',
	          `type` VARCHAR(50) NOT NULL DEFAULT '',
              `fieldname` varchar(71) NOT NULL,
              `column` varchar(255) NOT NULL,
              `data` text,
              `ownertype` ENUM('object','fieldcollection','localizedfield','objectbrick') NOT NULL DEFAULT 'object',
              `ownername` VARCHAR(70) NOT NULL DEFAULT '',
              `position` VARCHAR(70) NOT NULL DEFAULT '0',
              PRIMARY KEY (`o_id`, `dest_id`, `fieldname`, `column`, `ownertype`, `ownername`, `position`),
              INDEX `o_id` (`o_id`),
              INDEX `dest_id` (`dest_id`),
              INDEX `fieldname` (`fieldname`),
              INDEX `column` (`column`),
              INDEX `ownertype` (`ownertype`),
              INDEX `ownername` (`ownername`),
              INDEX `position` (`position`)
		) DEFAULT CHARSET=utf8;");

    }
}
