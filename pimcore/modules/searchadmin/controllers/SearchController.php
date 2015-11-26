<?php
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2015 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */

use Pimcore\Model\Search\Backend\Data;
use Pimcore\Model\Element;
use Pimcore\Model\Asset;
use Pimcore\Model\Document;
use Pimcore\Model\Object;

class Searchadmin_SearchController extends \Pimcore\Controller\Action\Admin {


    /**
     * @return void
     */
    public function findAction() {

        $user = $this->getUser();

        $query = $this->getParam("query");
        if ($query == "*") {
            $query = "";
        }

        $query = str_replace("%", "*", $query);

        $types = explode(",", $this->getParam("type"));
        $subtypes = explode(",", $this->getParam("subtype"));
        $classnames = explode(",", $this->getParam("class"));

        if ($this->getParam("type") == "object" && is_array($classnames) && empty($classnames[0])) {
            $subtypes = array("object","variant","folder");
        }

        $offset = intval($this->getParam("start"));
        $limit = intval($this->getParam("limit"));

        $offset = $offset ? $offset : 0;
        $limit = $limit ? $limit : 50;

        $searcherList = new Data\Listing();
        $conditionParts = array();
        $db = \Pimcore\Db::get();

        //exclude forbidden assets
        if(in_array("asset", $types)) {
            if (!$user->isAllowed("assets")) {
                $forbiddenConditions[] = " `type` != 'asset' ";
            } else {
                $forbiddenAssetPaths = Element\Service::findForbiddenPaths("asset", $user);
                if (count($forbiddenAssetPaths) > 0) {
                    for ($i = 0; $i < count($forbiddenAssetPaths); $i++) {
                        $forbiddenAssetPaths[$i] = " (maintype = 'asset' AND fullpath not like " . $db->quote($forbiddenAssetPaths[$i] . "%") . ")";
                    }
                    $forbiddenConditions[] = implode(" AND ", $forbiddenAssetPaths) ;
                }
            }
        }


        //exclude forbidden documents
        if(in_array("document", $types)) {
            if (!$user->isAllowed("documents")) {
                $forbiddenConditions[] = " `type` != 'document' ";
            } else {
                $forbiddenDocumentPaths = Element\Service::findForbiddenPaths("document", $user);
                if (count($forbiddenDocumentPaths) > 0) {
                    for ($i = 0; $i < count($forbiddenDocumentPaths); $i++) {
                        $forbiddenDocumentPaths[$i] = " (maintype = 'document' AND fullpath not like " . $db->quote($forbiddenDocumentPaths[$i] . "%") . ")";
                    }
                    $forbiddenConditions[] =  implode(" AND ", $forbiddenDocumentPaths) ;
                }
            }
        }

        //exclude forbidden objects
        if(in_array("object", $types)) {
            if (!$user->isAllowed("objects")) {
                $forbiddenConditions[] = " `type` != 'object' ";
            } else {
                $forbiddenObjectPaths = Element\Service::findForbiddenPaths("object", $user);
                if (count($forbiddenObjectPaths) > 0) {
                    for ($i = 0; $i < count($forbiddenObjectPaths); $i++) {
                        $forbiddenObjectPaths[$i] = " (maintype = 'object' AND fullpath not like " . $db->quote($forbiddenObjectPaths[$i] . "%") . ")";
                    }
                    $forbiddenConditions[] = implode(" AND ", $forbiddenObjectPaths);
                }
            }
        }

        if ($forbiddenConditions) {
            $conditionParts[] = "(" . implode(" AND ", $forbiddenConditions) . ")";
        }


        if (!empty($query)) {
            $queryCondition = "( MATCH (`data`,`properties`) AGAINST (" . $db->quote($query) . " IN BOOLEAN MODE) )";

            // the following should be done with an exact-search now "ID", because the Element-ID is now in the fulltext index
            // if the query is numeric the user might want to search by id
            //if(is_numeric($query)) {
                //$queryCondition = "(" . $queryCondition . " OR id = " . $db->quote($query) ." )";
            //}

            $conditionParts[] = $queryCondition;
        }                      


        //For objects - handling of bricks
        $fields = array();
        $bricks = array();
        if($this->getParam("fields")) {
            $fields = $this->getParam("fields");

            foreach($fields as $f) {
                $parts = explode("~", $f);
                if (substr($f, 0, 1) == "~") {
//                    $type = $parts[1];
//                    $field = $parts[2];
//                    $keyid = $parts[3];
                    // key value, ignore for now
                } else if(count($parts) > 1) {
                    $bricks[$parts[0]] = $parts[0];
                }
            }
        }        

        // filtering for objects
        if ($this->getParam("filter") && $this->getParam("class")) {
            $class = Object\ClassDefinition::getByName($this->getParam("class"));
            $conditionFilters = Object\Service::getFilterCondition($this->getParam("filter"), $class);
            $join = "";
            foreach($bricks as $ob) {
                $join .= " LEFT JOIN object_brick_query_" . $ob . "_" . $class->getId();

                $join .= " `" . $ob . "`";
                $join .= " ON `" . $ob . "`.o_id = `object_" . $class->getId() . "`.o_id";
            }

            $conditionParts[] = "( id IN (SELECT `object_" . $class->getId() . "`.o_id FROM object_" . $class->getId() . $join . " WHERE " . $conditionFilters . ") )";
        }

        if (is_array($types) and !empty($types[0])) {
            foreach ($types as $type) {
                $conditionTypeParts[] = $db->quote($type);
            }
            if(in_array("folder",$subtypes)){
                $conditionTypeParts[] = $db->quote('folder');
            }
            $conditionParts[] = "( maintype IN (" . implode(",", $conditionTypeParts) . ") )";
        }

        if (is_array($subtypes) and !empty($subtypes[0])) {
            foreach ($subtypes as $subtype) {
                $conditionSubtypeParts[] = $db->quote($subtype);
            }
            $conditionParts[] = "( type IN (" . implode(",", $conditionSubtypeParts) . ") )";
        }

        if (is_array($classnames) and !empty($classnames[0])) {
            if(in_array("folder",$subtypes)){
                $classnames[]="folder";    
            }
            foreach ($classnames as $classname) {
                $conditionClassnameParts[] = $db->quote($classname);
            }
            $conditionParts[] = "( subtype IN (" . implode(",", $conditionClassnameParts) . ") )";
        }


        if (count($conditionParts) > 0) {
            $condition = implode(" AND ", $conditionParts);

            //echo $condition; die();
            $searcherList->setCondition($condition);
        }


        $searcherList->setOffset($offset);
        $searcherList->setLimit($limit);

        // do not sort per default, it is VERY SLOW
        //$searcherList->setOrder("desc");
        //$searcherList->setOrderKey("modificationdate");

        if ($this->getParam("sort")) {
            // we need a special mapping for classname as this is stored in subtype column
            $sortMapping = [
                "classname" => "subtype"
            ];

            $sort = $this->getParam("sort");
            if(array_key_exists($this->getParam("sort"), $sortMapping)) {
                $sort = $sortMapping[$this->getParam("sort")];
            }
            $searcherList->setOrderKey($sort);
        }
        if ($this->getParam("dir")) {
            $searcherList->setOrder($this->getParam("dir"));
        }



        $hits = $searcherList->load();

        $elements=array();
        foreach ($hits as $hit) {

            $element = Element\Service::getElementById($hit->getId()->getType(), $hit->getId()->getId());
            if ($element->isAllowed("list")) {
                if ($element instanceof Object\AbstractObject) {
                    $data = Object\Service::gridObjectData($element, $fields);
                } else if ($element instanceof Document) {
                    $data = Document\Service::gridDocumentData($element);
                } else if ($element instanceof Asset) {
                    $data = Asset\Service::gridAssetData($element);
                }

                $elements[] = $data;
            } else {
                //TODO: any message that view is blocked?
                //$data = Element\Service::gridElementData($element);
            }

        }

        // only get the real total-count when the limit parameter is given otherwise use the default limit
        if($this->getParam("limit")) {
            $totalMatches = $searcherList->getTotalCount();
        } else {
            $totalMatches = count($elements);
        }

        $this->_helper->json(array("data" => $elements, "success" => true, "total" => $totalMatches));

        $this->removeViewRenderer();
    }
}
