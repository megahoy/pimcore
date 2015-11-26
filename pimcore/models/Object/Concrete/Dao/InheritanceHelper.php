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

namespace Pimcore\Model\Object\Concrete\Dao;

use Pimcore\Model;
use Pimcore\Model\Object;

class InheritanceHelper {

    /**
     *
     */
    const STORE_TABLE = "object_store_";

    /**
     *
     */
    const QUERY_TABLE = "object_query_";

    /**
     *
     */
    const RELATION_TABLE = "object_relations_";

    /**
     *
     */
    const OBJECTS_TABLE = 'objects';

    /**
     *
     */
    const ID_FIELD = "oo_id";

    /**
     * @param $classId
     * @param null $idField
     * @param null $storetable
     * @param null $querytable
     * @param null $relationtable
     */
    public function __construct($classId, $idField = null, $storetable = null, $querytable = null, $relationtable = null) {
        $this->db = \Pimcore\Db::get();
        $this->fields = array();
        $this->relations = array();
        $this->fieldIds = array();
        $this->deletionFieldIds = array();
        $this->fieldDefinitions = [];

        if($storetable == null) {
            $this->storetable = self::STORE_TABLE . $classId;
        } else {
            $this->storetable = $storetable;
        }

        if($querytable == null) {
            $this->querytable = self::QUERY_TABLE . $classId;
        } else {
            $this->querytable = $querytable;
        }

        if($relationtable == null) {
            $this->relationtable = self::RELATION_TABLE . $classId;
        } else {
            $this->relationtable = $relationtable;
        }

        if($idField == null) {
            $this->idField = self::ID_FIELD;
        } else {
            $this->idField = $idField;
        }
    }

    /**
     *
     */
    public function resetFieldsToCheck() {
        $this->fields = array();
        $this->relations = array();
        $this->fieldIds = array();
        $this->deletionFieldIds = array();
        $this->fieldDefinitions = [];

    }

    /**
     * @param $fieldname
     */
    public function addFieldToCheck($fieldname, $fieldDefinition) {
        $this->fields[$fieldname] = $fieldname;
        $this->fieldIds[$fieldname] = array();
        $this->fieldDefinitions[$fieldname] = $fieldDefinition;
    }

    /**
     * @param $fieldname
     * @param null $queryfields
     */
    public function addRelationToCheck($fieldname, $fieldDefinition, $queryfields = null) {
        if($queryfields == null) {
            $this->relations[$fieldname] = $fieldname;
        } else {
            $this->relations[$fieldname] = $queryfields;
        }

        $this->fieldIds[$fieldname] = array();
        $this->fieldDefinitions[$fieldname] = $fieldDefinition;
    }

    /**
     * @param $oo_id
     * @param bool $createMissingChildrenRows
     * @throws \Zend_Db_Adapter_Exception
     */
    public function doUpdate($oo_id, $createMissingChildrenRows = false) {

        if(empty($this->fields) && empty($this->relations) && !$createMissingChildrenRows) {
            return;
        }

        $this->idTree = array();


        $fields = implode("`,`", $this->fields);
        if(!empty($fields)) {
            $fields = ", `" . $fields . "`";
        }

        $result = $this->db->fetchRow("SELECT " . $this->idField . " AS id" . $fields . " FROM " . $this->storetable . " WHERE " . $this->idField . " = ?", $oo_id);
        $o = new \stdClass();
        $o->id = $result['id'];
        $o->values = $result;
        $o->childs = $this->buildTree($result['id'], $fields);

        if(!empty($this->fields)) {
            foreach($this->fields as $fieldname) {
                foreach($o->childs as $c) {
                    $this->getIdsToUpdateForValuefields($c, $fieldname);
                }

                $this->updateQueryTable($oo_id, $this->fieldIds[$fieldname], $fieldname);
            }
        }

        if(!empty($this->relations)) {
            foreach($this->relations as $fieldname => $fields) {
                foreach($o->childs as $c) {
                    $this->getIdsToUpdateForRelationfields($c, $fieldname);
                }

                if(is_array($fields)) {
                    foreach($fields as $f) {
                        $this->updateQueryTable($oo_id, $this->fieldIds[$fieldname], $f);
                    }
                } else {
                    $this->updateQueryTable($oo_id, $this->fieldIds[$fieldname], $fieldname);
                }
            }
        }

        // check for missing entries which can occur in object bricks and localized fields
        // this happens especially in the following case:
        // parent object has no brick, add child to parent, add brick to parent & click save
        // without this code there will not be an entry in the query table for the child object
        if($createMissingChildrenRows) {
            $idsToUpdate = $this->extractObjectIdsFromTreeChildren($o->childs);
            if(!empty($idsToUpdate)) {
                $idsInTable = $this->db->fetchCol("SELECT " . $this->idField . " FROM " . $this->querytable . " WHERE " . $this->idField . " IN (" . implode(",", $idsToUpdate) . ")");

                $diff = array_diff($idsToUpdate, $idsInTable);

                // create entries for children that don't have an entry yet
                $originalEntry = $this->db->fetchRow("SELECT * FROM " . $this->querytable . " WHERE " . $this->idField . " = ?", $oo_id);
                foreach ($diff as $id) {
                    $originalEntry[$this->idField] = $id;
                    $this->db->insert($this->querytable, $originalEntry);
                }
            }
        }
    }

    /** Currently solely used for object bricks. If a brick is removed, this info must be propagated to all
     * child elements.
     * @param $objectId
     */
    public function doDelete ($objectId) {
        // NOT FINISHED - NEEDS TO BE COMPLETED !!!

        // as a first step, build an ID list of all child elements that are affected. Stop at the level
        // which has a non-empty value.

        $fields = implode("`,`", $this->fields);
        if(!empty($fields)) {
            $fields = ", `" . $fields . "`";
        }

        $o = new \stdClass();
        $o->id = $objectId;
        $o->values = array();
        $o->childs = $this->buildTree($objectId, $fields);

        if(!empty($this->fields)) {
            foreach($this->fields as $fieldname) {
                foreach($o->childs as $c) {
                    $this->getIdsToCheckForDeletionForValuefields($c, $fieldname);
                }
                $this->updateQueryTableOnDelete($objectId, $this->deletionFieldIds[$fieldname], $fieldname);
            }
        }

        if(!empty($this->relations)) {
            foreach($this->relations as $fieldname => $fields) {
                foreach($o->childs as $c) {
                    $this->getIdsToCheckForDeletionForRelationfields($c, $fieldname);
                }
                $this->updateQueryTableOnDelete($objectId, $this->deletionFieldIds[$fieldname], $fieldname);
            }
        }

        $affectedIds = array();

        foreach ($this->deletionFieldIds as $fieldname => $ids) {
            foreach ($ids as $id) {
                $affectedIds[$id] = $id;
            }
        }

        $systemFields = array("o_id", "fieldname");

        $toBeRemovedItemIds = array();


        // now iterate over all affected elements and check if the object even has a brick. If it doesn't, then
        // remove the query row entirely ...
        if ($affectedIds) {

            $objectsWithBrickIds = array();
            $objectsWithBricks = $this->db->fetchAll("SELECT " . $this->idField . " FROM " . $this->storetable . " WHERE " . $this->idField . " IN (" . implode(",", $affectedIds) . ")");
            foreach ($objectsWithBricks as $item) {
                $objectsWithBrickIds[] = $item["id"];
            }

            $currentQueryItems = $this->db->fetchAll("SELECT * FROM " . $this->querytable . " WHERE " . $this->idField . " IN (" . implode(",", $affectedIds) . ")");

            foreach ($currentQueryItems as $queryItem) {
                $toBeRemoved = true;
                foreach ($queryItem as $fieldname => $value) {
                    if (!in_array($fieldname, $systemFields)) {
                        if (!is_null($value)) {
                            $toBeRemoved = false;
                            break;
                        }
                    }
                }
                if ($toBeRemoved) {
                    if (!in_array($queryItem["o_id"], $objectsWithBrickIds)) {
                        $toBeRemovedItemIds[] = $queryItem["o_id"];
                    }
                }
            }
        }

        if ($toBeRemovedItemIds) {
            $this->db->delete($this->querytable, $this->idField . " IN (" . implode(",", $toBeRemovedItemIds) . ")");
        }

    }

    /**
     * @param $currentParentId
     * @param string $fields
     * @return array
     */
    protected function buildTree($currentParentId, $fields = "", $parentIdGroups = null) {

        if (!$parentIdGroups) {
            $object = Object::getById($currentParentId);

            $result = $this->db->fetchAll("SELECT b.o_id AS id $fields, b.o_type AS type, b.o_parentId AS parentId, CONCAT(o_path,o_key) as fullpath FROM objects b LEFT JOIN " . $this->storetable . " a ON b.o_id = a." . $this->idField . " WHERE o_path LIKE ? GROUP BY b.o_id ORDER BY LENGTH(o_path) ASC", $object->getFullPath() . "/%");

            $objects = array();

            // group the results together based on the parent id's
            $parentIdGroups = [];
            foreach ($result as $r) {
                if (!isset($parentIdGroups[$r["parentId"]])) {
                    $parentIdGroups[$r["parentId"]] = [];
                }

                $parentIdGroups[$r["parentId"]][] = $r;
            }
        }

        if(isset($parentIdGroups[$currentParentId])) {
            foreach ($parentIdGroups[$currentParentId] as $r) {
                $o = new \stdClass();
                $o->id = $r['id'];
                $o->values = $r;
                $o->type = $r["type"];
                $o->childs = $this->buildTree($r['id'], $fields, $parentIdGroups);

                $objects[] = $o;
            }
        }

        return $objects;
    }

    /**
     * @param $node
     * @return mixed
     */
    protected function getRelationsForNode($node) {

        // if the relations are already set, skip here
        if(isset($node->relations)) {
            return $node;
        }

        $objectRelationsResult =  $this->db->fetchAll("SELECT fieldname, count(*) as COUNT FROM " . $this->relationtable . " WHERE src_id = ? AND fieldname IN('" . implode("','", array_keys($this->relations)) . "') GROUP BY fieldname;", $node->id);

        $objectRelations = array();
        if(!empty($objectRelationsResult)) {
            foreach($objectRelationsResult as $orr) {
                if($orr['COUNT'] > 0) {
                    $objectRelations[$orr['fieldname']] = $orr['fieldname'];
                }
            }
            $node->relations = $objectRelations;
        }

        return $node;
    }

    /**
     * @param $treeChildren
     * @return array
     */
    protected function extractObjectIdsFromTreeChildren($treeChildren)  {
        $ids = [];

        if(is_array($treeChildren)) {
            foreach($treeChildren as $child) {
                if($child->type != "folder") {
                    $ids[] = $child->id;
                }
                $ids = array_merge($ids, $this->extractObjectIdsFromTreeChildren($child->childs));
            }
        }

        return $ids;
    }

    /**
     * @param $currentNode
     * @param $fieldname
     */
    protected function getIdsToCheckForDeletionForValuefields($currentNode, $fieldname) {
        $value = $currentNode->values[$fieldname];

        if(!$this->fieldDefinitions[$fieldname]->isEmpty($value)) {
            return;
        }

        $this->deletionFieldIds[$fieldname][] = $currentNode->id;

        if(!empty($currentNode->childs)) {
            foreach($currentNode->childs as $c) {
                $this->getIdsToCheckForDeletionForValuefields($c, $fieldname);
            }
        }
    }


    /**
     * @param $currentNode
     * @param $fieldname
     */
    protected function getIdsToUpdateForValuefields($currentNode, $fieldname) {
        $value = $currentNode->values[$fieldname];
        if($this->fieldDefinitions[$fieldname]->isEmpty($value)) {
            $this->fieldIds[$fieldname][] = $currentNode->id;
            if(!empty($currentNode->childs)) {
                foreach($currentNode->childs as $c) {
                    $this->getIdsToUpdateForValuefields($c, $fieldname);
                }
            }
        }
    }

    /**
     * @param $currentNode
     * @param $fieldname
     */
    protected function getIdsToCheckForDeletionForRelationfields($currentNode, $fieldname) {
        $this->getRelationsForNode($currentNode);
        $value = $currentNode->relations[$fieldname];
        if(!$this->fieldDefinitions[$fieldname]->isEmpty($value)) {
            return;
        }
        $this->deletionFieldIds[$fieldname][] = $currentNode->id;

        if(!empty($currentNode->childs)) {
            foreach($currentNode->childs as $c) {
                $this->getIdsToCheckForDeletionForRelationfields($c, $fieldname);
            }
        }
    }

    /**
     * @param $currentNode
     * @param $fieldname
     */
    protected function getIdsToUpdateForRelationfields($currentNode, $fieldname) {
        $this->getRelationsForNode($currentNode);
        $value = $currentNode->relations[$fieldname];
        if($this->fieldDefinitions[$fieldname]->isEmpty($value)) {
            $this->fieldIds[$fieldname][] = $currentNode->id;
            if(!empty($currentNode->childs)) {
                foreach($currentNode->childs as $c) {
                    $this->getIdsToUpdateForRelationfields($c, $fieldname);
                }
            }
        }
    }

    /**
     * @param $oo_id
     * @param $ids
     * @param $fieldname
     * @throws \Zend_Db_Adapter_Exception
     */
    protected function updateQueryTable($oo_id, $ids, $fieldname) {
        if(!empty($ids)) {
            $value = $this->db->fetchOne("SELECT `$fieldname` FROM " . $this->querytable . " WHERE " . $this->idField . " = ?", $oo_id);
            $this->db->update($this->querytable, array($fieldname => $value), $this->idField . " IN (" . implode(",", $ids) . ")");
        }
    }


    protected function updateQueryTableOnDelete($oo_id, $ids, $fieldname) {
        if(!empty($ids)) {
            $value = null;
            $this->db->update($this->querytable, array($fieldname => $value), $this->idField . " IN (" . implode(",", $ids) . ")");
        }
    }


}
