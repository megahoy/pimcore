<?php
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @category   Pimcore
 * @package    Object|Class
 * @copyright  Copyright (c) 2009-2015 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */

class Admin_QuantityValueController extends Pimcore_Controller_Action_Admin {

    public function unitProxyAction() {

        if ($this->_getParam("data")) {
            if ($this->_getParam("xaction") == "destroy") {
                $data = Zend_Json::decode($this->_getParam("data"));
                $id = $data["id"];
                $unit = \Pimcore\Model\Object\QuantityValue\Unit::getById($id);
                if(!empty($unit)) {
                    $unit->delete();
                    $this->_helper->json(array("data" => array(), "success" => true));
                } else {
                    throw new Exception("Unit with id " . $id . " not found.");
                }
            }
            else if ($this->_getParam("xaction") == "update") {

                $data = Zend_Json::decode($this->_getParam("data"));
                $unit = Pimcore\Model\Object\QuantityValue\Unit::getById($data['id']);
                if(!empty($unit)) {
                    $unit->setValues($data);
                    $unit->save();
                    $this->_helper->json(array("data" => get_object_vars($unit), "success" => true));
                } else {
                    throw new Exception("Unit with id " . $data['id'] . " not found.");
                }
            } else if ($this->_getParam("xaction") == "create") {
                $data = Zend_Json::decode($this->_getParam("data"));
                unset($data['id']);
                $unit = new Pimcore\Model\Object\QuantityValue\Unit();
                $unit->setValues($data);
                $unit->save();
                $this->_helper->json(array("data" => get_object_vars($unit), "success" => true));
            }
        } else {

            $list = new Pimcore\Model\Object\QuantityValue\Unit\Listing();
            $list->setOrder("asc");
            $list->setOrderKey("abbreviation");

            if($this->_getParam("dir")) {
                $list->setOrder($this->_getParam("dir"));
            }
            if($this->_getParam("sort")) {
                $list->setOrderKey($this->_getParam("sort"));
            }

            $list->setLimit($this->_getParam("limit"));
            $list->setOffset($this->_getParam("start"));

            $condition = "1 = 1";
            if($this->_getParam("filter")) {
                $filterString = $this->_getParam("filter");
                $filters = json_decode($filterString);
                $db = \Pimcore\Db::get();
                foreach($filters as $f) {
                    if($f->type == "string") {
                        $condition .= " AND " . $db->getQuoteIdentifierSymbol() . $f->field . $db->getQuoteIdentifierSymbol() . " LIKE " . $db->quote("%" . $f->value . "%");
                    } else if($f->type == "numeric") {
                        $operator = $this->getOperator($f->comparison);
                        $condition .= " AND " . $db->getQuoteIdentifierSymbol() . $f->field . $db->getQuoteIdentifierSymbol() . " " . $operator . " " . $db->quote($f->value);
                    }

                }
                $list->setCondition($condition);
            }
            $list->load();

            $units = array();
            foreach ($list->getUnits() as $u) {
                $units[] = get_object_vars($u);
            }

            $this->_helper->json(array("data" => $units, "success" => true, "total" => $list->getTotalCount()));
        }
    }

    private function getOperator($comparison) {
        $mapper = array(
            "lt" => "<",
            "gt" => ">",
            "eq" => "="
        );

        return $mapper[$comparison]; 
    }


    public function unitListAction() {
        $list = new \Pimcore\Model\Object\QuantityValue\Unit\Listing();
        if($this->_getParam("filter")) {
            $array = explode(",", $this->_getParam("filter"));
            $quotedArray = array();
            $db = \Pimcore\Db::get();
            foreach($array as $a) {
                $quotedArray[] = $db->quote($a);
            }
            $string = implode(",", $quotedArray);
            $list->setCondition("id IN (" . $string . ")");
        }
        
        $units = $list->getUnits();
        $this->_helper->json(array("data" => $units, "success" => true, "total" => $list->getTotalCount()));
    }
}
