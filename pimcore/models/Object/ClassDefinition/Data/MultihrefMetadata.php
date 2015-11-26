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

namespace Pimcore\Model\Object\ClassDefinition\Data;


use Pimcore\Model;
use Pimcore\Model\Object;
use Pimcore\Model\Asset;
use Pimcore\Model\Document;
use Pimcore\Model\Element;
use Pimcore\Tool;
use Pimcore\Resource;

class MultihrefMetadata extends Model\Object\ClassDefinition\Data\Multihref {

    /**
     * @var
     */
    public $columns;

    /**
     * Static type of this element
     *
     * @var string
     */
    public $fieldtype = "multihrefMetadata";

    /**
     * Type for the generated phpdoc
     *
     * @var string
     */
    public $phpdocType = "\\Pimcore\\Model\\Object\\Data\\ElemenentMetadata[]";


    /**
     * @see Object\ClassDefinition\Data::getDataForResource
     * @param array $data
     * @param null|Model\Object\AbstractObject $object
     * @return array
     */
    public function getDataForResource($data, $object = null) {

        $return = array();

        if (is_array($data) && count($data) > 0) {
            $counter = 1;
            foreach ($data as $metaObject) {
                $element = $metaObject->getElement();
                if ($element instanceof Element\ElementInterface) {
                    $return[] = array(
                        "dest_id" => $element->getId(),
                        "type" => Element\Service::getElementType($element),
                        "fieldname" => $this->getName(),
                        "index" => $counter
                    );
                }
                $counter++;
            }
            return $return;
        } else if (is_array($data) and count($data)===0) {
            //give empty array if data was not null
            return array();
        } else {
            //return null if data was null - this indicates data was not loaded
            return null;
        }
    }

    /**
     * @see Object\ClassDefinition\Data::getDataFromResource
     * @param array $data
     * @return array
     */
    public function getDataFromResource($data) {
        $list = array();

        if (is_array($data) && count($data) > 0) {
            foreach ($data as $element) {
                $destination = null;
                $source = Object::getById($element["src_id"]);


                if ($element["type"] == "object") {
                    $destination = Object::getById($element["dest_id"]);
                }
                else if ($element["type"] == "asset") {
                    $destination = Asset::getById($element["dest_id"]);
                }
                else if ($element["type"] == "document") {
                    $destination = Document::getById($element["dest_id"]);
                }

                if ($destination instanceof Element\ElementInterface) {
                    $className = Tool::getModelClassMapping('\Pimcore\Model\Object\Data\ElementMetadata'); // the name for the class mapping is still with underscores
                    $metaData = new $className($this->getName(), $this->getColumnKeys(), $destination);

                    $ownertype = $element["ownertype"] ? $element["ownertype"] : "";
                    $ownername = $element["ownername"] ? $element["ownername"] : "";
                    $position = $element["position"] ? $element["position"] : "0";
                    $type = $element["type"];


                    $metaData->load($source, $destination, $this->getName(), $ownertype, $ownername, $position, $type);
                    $objects[] = $metaData;

                    $list[] = $metaData;
                }
            }
        }
        //must return array - otherwise this means data is not loaded
        return $list;
    }

    /**
     * @param $data
     * @param null $object
     * @throws \Exception
     */
    public function getDataForQueryResource($data, $object = null) {

        //return null when data is not set
        if(!$data) return null;

        $ids = array();

        if (is_array($data) && count($data) > 0) {
            foreach ($data as $metaObject) {
                $element = $metaObject->getElement();
                if ($element instanceof Element\ElementInterface) {
                    $elementType = Element\Service::getElementType($element);
                    $d[] = $elementType . "|" . $element->getId();
                }
            }
            return "," . implode(",", $ids) . ",";
        } else if (is_array($data) && count($data) === 0){
            return "";
        } else {
            throw new \Exception("invalid data passed to getDataForQueryResource - must be array");
        }
    }

    /**
     * @see Object\ClassDefinition\Data::getDataForEditmode
     * @param array $data
     * @param null|Model\Object\AbstractObject $object
     * @return array
     */
    public function getDataForEditmode($data, $object = null)
    {
        $return = array();

        if (is_array($data) && count($data) > 0) {
            foreach ($data as $metaObject) {
                $element = $metaObject->getElement();

                $itemData = null;

                if ($element instanceof Object\Concrete) {
                    $itemData = array("id" => $element->getId(), "path" => $element->getFullPath(), "type" => "object", "subtype" => $element->getClassName());
                } else if ($element instanceof Object\AbstractObject) {
                    $itemData = array("id" => $element->getId(), "path" => $element->getFullPath(), "type" => "object",  "subtype" => "folder");
                } else if ($element instanceof Asset) {
                    $itemData = array("id" => $element->getId(), "path" => $element->getFullPath(), "type" => "asset",  "subtype" => $element->getType());
                } else if ($element instanceof Document) {
                    $itemData= array("id" => $element->getId(), "path" => $element->getFullPath(), "type" => "document", "subtype" => $element->getType());
                }

                if (!$itemData) {
                    continue;
                }


                foreach($this->getColumns() as $c) {
                    $getter = "get" . ucfirst($c['key']);
                    $itemData[$c['key']] = $metaObject->$getter();
                }
                $return[] = $itemData;

            }
            if (empty ($return)) {
                $return = false;
            }
            return $return;
        }
    }


    /**
     * @see Model\Object\ClassDefinition\Data::getDataFromEditmode
     * @param array $data
     * @param null|Model\Object\AbstractObject $object
     * @return array
     */
    public function getDataFromEditmode($data, $object = null) {
        //if not set, return null
        if($data === null or $data === FALSE){ return null; }

        $multihrefMetadata = array();
        if (is_array($data) && count($data) > 0) {

            foreach ($data as $element) {

                if ($element["type"] == "object") {
                    $e = Object::getById($element["id"]);
                }

                else if ($element["type"] == "asset") {
                    $e = Asset::getById($element["id"]);
                }
                else if ($element["type"] == "document") {
                    $e = Document::getById($element["id"]);
                }

                if ($e instanceof Element\ElementInterface) {
                    $className = Tool::getModelClassMapping('\Pimcore\Model\Object\Data\ElementMetadata');
                    $metaData = new $className($this->getName(), $this->getColumnKeys(), $e);
                    foreach($this->getColumns() as $columnConfig) {
                        $key = $columnConfig["key"];
                        $setter = "set" . ucfirst($key);
                        $value = $element[$key];
                        $metaData->$setter($value);
                    }
                    $multihrefMetadata[] = $metaData;

                    $elements[] = $e;
                }
            }

        }

        //must return array if data shall be set
        return $multihrefMetadata;
    }

    /**
     * @param $data
     * @param null $object
     * @return array
     */
    public function getDataForGrid($data, $object = null) {
        if (is_array($data)) {
            $pathes = array();
            foreach ($data as $metaObject) {
                $eo = $metaObject->getElement();
                if ($eo instanceof Element\ElementInterface) {
                    $pathes[] = $eo->getFullPath();
                }
            }
            return $pathes;
        }
    }

    /**
     * @see Object\ClassDefinition\Data::getVersionPreview
     * @param array $data
     * @return string
     */
    public function getVersionPreview($data) {

        if (is_array($data) && count($data) > 0) {
            foreach ($data as $metaObject) {
                $o = $metaObject->getElement();
                $pathes[] = Element\Service::getElementType($o) . " " . $o->getFullPath();
            }
            return implode("<br />", $pathes);
        }
    }

    /**
     * Checks if data is valid for current data field
     *
     * @param mixed $data
     * @param boolean $omitMandatoryCheck
     * @throws \Exception
     */
    public function checkValidity($data, $omitMandatoryCheck = false){

        if(!$omitMandatoryCheck and $this->getMandatory() and empty($data)){
            throw new \Exception("Empty mandatory field [ ".$this->getName()." ]");
        }

        if (is_array($data)) {
            foreach ($data as $elementMetadata) {
                if (!($elementMetadata instanceof Object\Data\ElementMetadata)) {
                    throw new \Exception("Expected Object\\Data\\ElementMetadata");
                }

                $d = $elementMetadata->getElement();

                if ($d instanceof Document) {
                    $allow = $this->allowDocumentRelation($d);
                } else if ($d instanceof Asset) {
                    $allow = $this->allowAssetRelation($d);
                } else if ($d instanceof Object\AbstractObject) {
                    $allow = $this->allowObjectRelation($d);
                } else if (empty($d)) {
                    $allow = true;
                } else {
                    $allow = false;
                }
                if (!$allow) {
                    throw new \Exception("Invalid multihref relation", null, null);
                }
            }
        }
    }

    /**
     * converts object data to a simple string value or CSV Export
     * @abstract
     * @param Model\Object\AbstractObject $object
     * @return string
     */
    public function getForCsvExport($object) {
        $data = $this->getDataFromObjectParam($object);
        if (is_array($data)) {
            $paths = array();
            foreach ($data as $metaObject) {
                $eo = $metaObject->getElement();
                if ($eo instanceof Element\ElementInterface) {
                    $paths[] = Element\Service::getType($eo) . ":" . $eo->getFullPath();
                }
            }
            return implode(",", $paths);
        } else return null;
    }

    /**
     * @param $importValue
     * @return array|mixed
     */
    public function getFromCsvImport($importValue) {
        $values = explode(",", $importValue);

        $value = array();
        foreach ($values as $element) {

            $tokens = explode(":", $element);

            $type = $tokens[0];
            $path = $tokens[1];
            $el = Element\Service::getElementByPath($type, $path);

            if ($el) {
                $className = Tool::getModelClassMapping('\Pimcore\Model\Object\Data\ElementMetadata');
                $metaObject = new $className($this->getName(), $this->getColumnKeys(), $el);

                $value[] = $metaObject;
            }

        }

        return $value;

    }


    /**
     * This is a dummy and is mostly implemented by relation types
     *
     * @param mixed $data
     * @param array $tags
     * @return array
     */
    public function getCacheTags ($data, $tags = array()) {

        $tags = is_array($tags) ? $tags : array();

        if (is_array($data) && count($data) > 0) {
            foreach ($data as $metaObject) {
                $element = $metaObject->getElement();
                if ($element instanceof Element\ElementInterface && !array_key_exists($element->getCacheTag(), $tags)) {
                    $tags = $element->getCacheTags($tags);
                }
            }
        }
        return $tags;
    }


    /**
     * @param Object\AbstractObject $object
     * @return array|mixed|null
     */
    public function getForWebserviceExport ($object) {
        $data = $this->getDataFromObjectParam($object);
        if (is_array($data)) {
            $items = array();
            foreach ($data as $metaObject) {
                $eo = $metaObject->getElement();
                if ($eo instanceof Element\ElementInterface) {
                    $item = array();
                    $item["type"] = Element\Service::getType($eo);
                    $item["id"] = $eo->getId();

                    foreach($this->getColumns() as $c) {
                        $getter = "get" . ucfirst($c['key']);
                        $item[$c['key']] = $metaObject->$getter();
                    }
                    $items[] = $item;
                }
            }
            return $items;
        } else return null;
    }


    /**
     * @param mixed $value
     * @param null $relatedObject
     * @param null $idMapper
     * @return mixed|void
     * @throws \Exception
     */
    public function getFromWebserviceImport($value, $relatedObject = null, $idMapper = null)
    {

        if (empty($value)) {
            return null;
        } else if (is_array($value)) {
            $hrefs = array();
            foreach ($value as $href) {
                // cast is needed to make it work for both SOAP and REST
                $href = (array) $href;
                if (is_array($href) and array_key_exists("id", $href) and array_key_exists("type", $href)) {
                    $type = $href["type"];
                    $id = $href["id"];
                    if ($idMapper) {
                        $id = $idMapper->getMappedId($type, $id);
                    }

                    $e = null;
                    if ($id) {
                        $e = Element\Service::getElementById($type, $id);
                    }

                    if ($e instanceof Element\ElementInterface) {
                        $elMeta = new Object\Data\ElementMetadata($this->getName(), $this->getColumnKeys(), $e);

                        foreach($this->getColumns() as $c) {
                            $setter = "set" . ucfirst($c['key']);
                            $elMeta->$setter($href[$c['key']]);
                        }


                        $hrefs[] = $elMeta;
                    } else {
                        if (!$idMapper || !$idMapper->ignoreMappingFailures()) {
                            throw new \Exception("cannot get values from web service import - unknown element of type [ " . $href["type"] . " ] with id [" . $href["id"] . "] is referenced");
                        } else {
                            $idMapper->recordMappingFailure("object", $relatedObject->getId(), $type, $href["id"]);
                        }
                    }
                }
            }
            return $hrefs;
        } else {
            throw new \Exception("cannot get values from web service import - invalid data");
        }
    }


    /**
     * @param Object\Concrete $object
     * @return void
     */
    public function save($object, $params = array()) {

        $multihrefMetadata = $this->getDataFromObjectParam($object, $params);

        $classId = null;
        $objectId = null;

        if($object instanceof Object\Concrete) {
            $objectId = $object->getId();
        } else if($object instanceof Object\Fieldcollection\Data\AbstractData) {
            $objectId = $object->getObject()->getId();
        } else if ($object instanceof Object\Localizedfield) {
            $objectId = $object->getObject()->getId();
        } else if ($object instanceof Object\Objectbrick\Data\AbstractData) {
            $objectId = $object->getObject()->getId();
        }

        if ($object instanceof Object\Localizedfield) {
            $classId = $object->getClass()->getId();
        } else if ($object instanceof Object\Objectbrick\Data\AbstractData || $object instanceof Object\Fieldcollection\Data\AbstractData) {
            $classId = $object->getObject()->getClassId();
        } else {
            $classId = $object->getClassId();
        }

        $table = "object_metadata_" . $classId;
        $db = Resource::get();

        $this->enrichRelation($object, $params, $classId, $relation);

        $position = (isset($relation["position"]) && $relation["position"]) ? $relation["position"] : "0";

        $sql = $db->quoteInto("o_id = ?", $objectId) . " AND " . $db->quoteInto("fieldname = ?", $this->getName())
            . " AND " . $db->quoteInto("position = ?", $position);



        $db->delete($table, $sql);

        if(!empty($multihrefMetadata)) {

            if ($object instanceof Object\Localizedfield || $object instanceof Object\Objectbrick\Data\AbstractData
                || $object instanceof Object\Fieldcollection\Data\AbstractData) {
                $objectConcrete = $object->getObject();
            } else {
                $objectConcrete = $object;
            }

            foreach($multihrefMetadata as $meta) {
                $ownerName = isset($relation["ownername"]) ? $relation["ownername"] : null;
                $ownerType = isset($relation["ownertype"]) ? $relation["ownertype"] : null;
                $meta->save($objectConcrete, $ownerType, $ownerName, $position);
            }
        }

        parent::save($object, $params);
    }

    public function preGetData ($object, $params = array()) {

        $data = null;
        if($object instanceof Object\Concrete) {
            $data = $object->{$this->getName()};
            if($this->getLazyLoading() and !in_array($this->getName(), $object->getO__loadedLazyFields())){
                //$data = $this->getDataFromResource($object->getRelationData($this->getName(),true,null));
                $data = $this->load($object, array("force" => true));

                $setter = "set" . ucfirst($this->getName());
                if(method_exists($object, $setter)) {
                    $object->$setter($data);
                }
            }
        } else if ($object instanceof Object\Localizedfield) {
            $data = $params["data"];
        } else if ($object instanceof Object\Fieldcollection\Data\AbstractData) {
            $data = $object->{$this->getName()};
        } else if ($object instanceof Object\Objectbrick\Data\AbstractData) {
            $data = $object->{$this->getName()};
        }

        if(Object\AbstractObject::doHideUnpublished() and is_array($data)) {
            $publishedList = array();
            foreach($data as $listElement){

                if(Element\Service::isPublished($listElement->getObject())){
                    $publishedList[] = $listElement;
                }
            }
            return $publishedList;
        }

        return $data;
    }

    /**
     * @param Object\Concrete $object
     * @return void
     */
    public function delete($object) {
        $db = Resource::get();
        $db->delete("object_metadata_" . $object->getClassId(), $db->quoteInto("o_id = ?", $object->getId()) . " AND " . $db->quoteInto("fieldname = ?", $this->getName()));
    }

    /**
     * @param $columns
     * @return $this
     */
    public function setColumns($columns) {
        if(isset($columns['key'])) {
            $columns = array($columns);
        }
        usort($columns, array($this, 'sort'));

        $this->columns = array();
        $this->columnKeys = array();
        foreach($columns as $c) {
            $c['key'] = strtolower($c['key']);
            $this->columns[] = $c;
            $this->columnKeys[] = $c['key'];
        }
        return $this;
    }

    /**
     * @return mixed
     */
    public function getColumns() {
        return $this->columns;
    }

    /**
     * @return array
     */
    public function getColumnKeys() {
        $this->columnKeys = array();
        foreach($this->columns as $c) {
            $this->columnKeys[] = $c['key'];
        }
        return $this->columnKeys;
    }

    /**
     * @param $a
     * @param $b
     * @return int
     */
    public function sort($a, $b) {
        if(is_array($a) && is_array($b)) {
            return $a['position'] - $b['position'];
        }
        return strcmp($a, $b);
    }

    /**
     * @return void
     */
    public function classSaved($class) {
        $className = Tool::getModelClassMapping('\Pimcore\Model\Object\Data\ElementMetadata');
        $temp = new $className(null);
        $temp->getDao()->createOrUpdateTable($class);
    }

    /**
     * Rewrites id from source to target, $idMapping contains
     * array(
     *  "document" => array(
     *      SOURCE_ID => TARGET_ID,
     *      SOURCE_ID => TARGET_ID
     *  ),
     *  "object" => array(...),
     *  "asset" => array(...)
     * )
     * @param mixed $object
     * @param array $idMapping
     * @param array $params
     * @return Element\ElementInterface
     */
    public function rewriteIds($object, $idMapping, $params = array()) {
        $data = $this->getDataFromObjectParam($object, $params);

        if (is_array($data)) {
            foreach ($data as &$metaObject) {
                $eo = $metaObject->getObject();
                if ($eo instanceof Element\ElementInterface) {
                    $id = $eo->getId();
                    $type = Element\Service::getElementType($eo);

                    if(array_key_exists($type, $idMapping) && array_key_exists($id, $idMapping[$type])) {
                        $newElement = Element\Service::getElementById($type, $idMapping[$type][$id]);
                        $metaObject->setObject($newElement);
                    }
                }
            }
        }

        return $data;
    }

    /**
     * @param Object\ClassDefinition\Data $masterDefinition
     */
    public function synchronizeWithMasterDefinition(Object\ClassDefinition\Data $masterDefinition) {
        parent::synchronizeWithMasterDefinition($masterDefinition);
        $this->columns = $masterDefinition->columns;
    }

    /**
     *
     */
    public function enrichLayoutDefinition($object) {
        // nothing to do
    }

    /**
     * @param $data
     * @return array
     */
    public function resolveDependencies ($data) {

        $dependencies = array();

        if (is_array($data) && count($data) > 0) {
            foreach ($data as $metaElement) {
                $o = $metaElement->getElement();
                if ($o instanceof Object\AbstractObject) {
                    $dependencies["object_" . $o->getId()] = array(
                        "id" => $o->getId(),
                        "type" => "object"
                    );
                }
            }
        }
        return $dependencies;
    }

}
