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

use Pimcore\Model\Element\Recyclebin;
use Pimcore\Model\Element;

class Admin_RecyclebinController extends \Pimcore\Controller\Action\Admin {

    public function init() {

        parent::init();

        // recyclebin actions might take some time (save & restore)
        $timeout = 600; // 10 minutes
        @ini_set("max_execution_time", $timeout);
        set_time_limit($timeout);

        // check permissions
        $notRestrictedActions = array("add");
        if (!in_array($this->getParam("action"), $notRestrictedActions)) {
            $this->checkPermission("recyclebin");
        }
    }

    public function listAction () {
        
        if($this->getParam("xaction") == "destroy") {
            $item = Recyclebin\Item::getById(\Pimcore\Admin\Helper\QueryParams::getRecordIdForGridRequest($this->getParam("data")));
            $item->delete();
 
            $this->_helper->json(array("success" => true, "data" => array()));
        }
        else {
            $list = new Recyclebin\Item\Listing();
            $list->setLimit($this->getParam("limit"));
            $list->setOffset($this->getParam("start"));

            $list->setOrderKey("date");
            $list->setOrder("DESC");

            $sortingSettings = \Pimcore\Admin\Helper\QueryParams::extractSortingSettings($this->getAllParams());
            if($sortingSettings['orderKey']) {
                $list->setOrderKey($sortingSettings['orderKey']);
                $list->setOrder($sortingSettings['order']);
            }


            $conditionFilters = array();

            if($this->getParam("filterFullText")) {
                $conditionFilters[] = "path LIKE " . $list->quote("%".$this->getParam("filterFullText")."%");
            }

            $filters = $this->getParam("filter");
            if($filters) {
                $filters = \Zend_Json::decode($filters);

                foreach ($filters as $filter) {

                    $operator = "=";

                    if($filter["type"] == "string") {
                        $operator = "LIKE";
                    } else if ($filter["type"] == "numeric") {
                        if($filter["comparison"] == "lt") {
                            $operator = "<";
                        } else if($filter["comparison"] == "gt") {
                            $operator = ">";
                        } else if($filter["comparison"] == "eq") {
                            $operator = "=";
                        }
                    } else if ($filter["type"] == "date") {
                        if($filter["comparison"] == "lt") {
                            $operator = "<";
                        } else if($filter["comparison"] == "gt") {
                            $operator = ">";
                        } else if($filter["comparison"] == "eq") {
                            $operator = "=";
                        }
                        $filter["value"] = strtotime($filter["value"]);
                    } else if ($filter["type"] == "list") {
                        $operator = "=";
                    } else if ($filter["type"] == "boolean") {
                        $operator = "=";
                        $filter["value"] = (int) $filter["value"];
                    }
                    // system field
                    $value = $filter["value"];
                    if ($operator == "LIKE") {
                        $value = "%" . $value . "%";
                    }

                    $field = "`" . $filter["field"] . "` ";
                    if($filter["field"] == "fullpath") {
                        $field = "CONCAT(path,filename)";
                    }

                    $conditionFilters[] =  $field . $operator . " '" . $value . "' ";
                }
            }

            if(!empty($conditionFilters)) {
                $condition = implode(" AND ", $conditionFilters);
                $list->setCondition($condition);
            }

            $items = $list->load();
            
            $this->_helper->json(array("data" => $items, "success" => true, "total" => $list->getTotalCount()));
        }
    }
    
    public function restoreAction () {
        $item = Recyclebin\Item::getById($this->getParam("id"));
        $item->restore();
 
        $this->_helper->json(array("success" => true));
    }
 
    public function flushAction () {
        $bin = new Element\Recyclebin();
        $bin->flush();
        
        $this->_helper->json(array("success" => true)); 
    }

    public function addAction () {

        $element = Element\Service::getElementById($this->getParam("type"), $this->getParam("id"));

        if($element) {

            $type = Element\Service::getElementType($element);
            $listClass = "\\Pimcore\\Model\\" . ucfirst($type) . "\\Listing";
            $list = new $listClass();
            $list->setCondition( (($type == "object") ? "o_" : "") . "path LIKE '" . $element->getFullPath() . "/%'");
            $children = $list->getTotalCount();

            if($children <= 100) {
                Recyclebin\Item::create($element, $this->getUser());
            }

            $this->_helper->json(array("success" => true));
        } else {
            $this->_helper->json(array("success" => false));
        }

    }
}
