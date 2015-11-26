<?php
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @category   Pimcore
 * @package    Element
 * @copyright  Copyright (c) 2009-2015 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */

namespace Pimcore\Model\Element\Import;

use Pimcore\Model;
use Pimcore\Tool;
use Pimcore\Model\Webservice;
use Pimcore\Model\Element;
use Pimcore\Model\Asset;
use Pimcore\Model\Object;
use Pimcore\Model\Document;

class Service
{

    /**
     * @var Webservice\Service
     */
    protected $webService;

    /**
     * @var array
     */
    protected $importInfo;

    /**
     * @var User
     */
    protected $user;


    public function __construct($user)
    {
        $this->webService = new Webservice\Service();
        $this->importInfo = array();
        $this->user = $user;
    }


    public function getWebservice()
    {
        return $this->webService;
    }

    /**
     * @return array
     */
    public function getImportInfo()
    {
        return $this->importInfo;
    }

    /**
     * @throws \Exception
     * @param  $rootElement
     * @param  $apiKey
     * @param  $path
     * @param  $apiElement
     * @param  bool $overwrite
     * @param  $elementCounter
     * @return Element\ElementInterface
     */
    public function create($rootElement, $apiKey, $path, $apiElement, $overwrite, $elementCounter)
    {


        //correct relative path
        if (strpos($path, "/") !== 0) {
            $path = $rootElement->getFullPath() . "/" . $path;
        }

        $type = $apiElement->type;

        if ($apiElement instanceof Webservice\Data\Asset) {
            $className = "\\Pimcore\\Model\\Asset\\" . ucfirst($type);
            $parentClassName = "\\Pimcore\\Model\\Asset";
            $maintype = "asset";
            $fullPath = $path . $apiElement->filename;
        } else if ($apiElement instanceof Webservice\Data\Object) {
            $maintype = "object";
            if ($type == "object") {
                $className = "\\Pimcore\\Model\\Object\\" . ucfirst($apiElement->className);
                if (!Tool::classExists($className)) {
                    throw new \Exception("Unknown class [ " . $className . " ]");
                }
            } else {
                $className = "\\Pimcore\\Model\\Object\\" . ucfirst($type);
            }
            $parentClassName = "\\Pimcore\\Model\\Object";
            $fullPath = $path . $apiElement->key;
        } else if ($apiElement instanceof Webservice\Data\Document) {
            $maintype = "document";
            $className = "\\Pimcore\\Model\\Document\\" . ucfirst($type);
            $parentClassName = "\\Pimcore\\Model\\Document";
            $fullPath = $path . $apiElement->key;
        } else {
            throw new \Exception("Unknown import element");
        }

        $existingElement = $className::getByPath($fullPath);
        if ($overwrite && $existingElement) {
            $apiElement->parentId = $existingElement->getParentId();
            return $existingElement;
        }

        $element = new $className();
        $element->setId(null);
        $element->setCreationDate(time());
        if ($element instanceof Asset) {
            $element->setFilename($apiElement->filename);
            $element->setData(base64_decode($apiElement->data));
        } else if ($element instanceof Object\Concrete) {
            $element->setKey($apiElement->key);
            $element->setClassName($apiElement->className);
            $class = Object\ClassDefinition::getByName($apiElement->className);
            if (!$class instanceof Object\ClassDefinition) {
                throw new \Exception("Unknown object class [ " . $apiElement->className . " ] ");
            }
            $element->setClassId($class->getId());

        } else {
            $element->setKey($apiElement->key);
        }

        $this->setModificationParams($element, true);
        $key = $element->getKey();
        if (empty($key) and $apiElement->id == 1) {
            if ($element instanceof Asset) {
                $element->setFilename("home_" . uniqid());
            } else {
                $element->setKey("home_" . uniqid());
            }
        } else if (empty($key)) {
            throw new \Exception ("Cannot create element without key ");
        }

        $parent = $parentClassName::getByPath($path);

        if (Element\Service::getType($rootElement) == $maintype and $parent) {
            $element->setParentId($parent->getId());
            $apiElement->parentId = $parent->getId();
            $existingElement = $parentClassName::getByPath($parent->getFullPath() . "/" . $element->getKey());
            if ($existingElement) {
                //set dummy key to avoid duplicate paths
                if ($element instanceof Asset) {
                    $element->setFilename(str_replace("/", "_", $apiElement->path) . uniqid() . "_" . $elementCounter . "_" . $element->getFilename());
                } else {
                    $element->setKey(str_replace("/", "_", $apiElement->path) . uniqid() . "_" . $elementCounter . "_" . $element->getKey());
                }
            }
        } else if (Element\Service::getType($rootElement) != $maintype) {
            //this is a related element - try to import it to it's original path or set the parent to home folder
            $potentialParent = $parentClassName::getByPath($path);

            //set dummy key to avoid duplicate paths
            if ($element instanceof Asset) {
                $element->setFilename(str_replace("/", "_", $apiElement->path) . uniqid() . "_" . $elementCounter . "_" . $element->getFilename());
            } else {
                $element->setKey(str_replace("/", "_", $apiElement->path) . uniqid() . "_" . $elementCounter . "_" . $element->getKey());
            }

            if ($potentialParent) {
                $element->setParentId($potentialParent->getId());
                //set actual id and path for second run
                $apiElements[$apiKey]["path"] = $potentialParent->getFullPath();
                $apiElement->parentId = $potentialParent->getId();
            } else {
                $element->setParentId(1);
                //set actual id and path for second run
                $apiElements[$apiKey]["path"] = "/";
                $apiElement->parentId = 1;
            }
        } else {
            $element->setParentId($rootElement->getId());
            //set actual id and path for second run
            $apiElements[$apiKey]["path"] = $rootElement->getFullPath();
            $apiElement->parentId = $rootElement->getId();

            //set dummy key to avoid duplicate paths
            if ($element instanceof Asset) {
                $element->setFilename(str_replace("/", "_", $apiElement->path) . uniqid() . "_" . $elementCounter . "_" . $element->getFilename());
            } else {
                $element->setKey(str_replace("/", "_", $apiElement->path) . uniqid() . "_" . $elementCounter . "_" . $element->getKey());
            }

        }

        //if element exists, make temp key permanent by setting it in apiElement
        if ($parentClassName::getByPath($fullPath)) {

            if ($element instanceof Asset) {
                $apiElement->filename = $element->getFilename();
            } else {
                $apiElement->key = $element->getKey();
            }
        }

        $element->save();

        //todo save type and id for later rollback
        $this->importInfo[Element\Service::getType($element) . "_" . $element->getId()] = array("id" => $element->getId(), "type" => Element\Service::getType($element), "fullpath" => $element->getFullPath());


        return $element;

    }

    /**
     * @param Webservice\Data $apiElement
     * @param string $type
     * @param array $idMapping
     * @return void
     */
    public function correctElementIdRelations($apiElement, $type, $idMapping)
    {

        //correct id
        $apiElement->id = $idMapping[$type][$apiElement->id];

        //correct properties
        if ($apiElement->properties) {
            foreach ($apiElement->properties as $property) {
                if (in_array($property->type, array("asset", "object", "document"))) {
                    $property->data = $idMapping[$property->type][$property->data];
                }
            }
        }

    }

    /**
     * @param  Webservice\Data\Document\PageSnippet $apiElement
     * @param  array $idMapping
     * @return void
     */
    public function correctDocumentRelations($apiElement, $idMapping)
    {
        if ($apiElement->elements) {
            foreach ($apiElement->elements as $el) {

                if ($el->type == "href" and is_object($el->value) and $el->value->id) {
                    $el->value->id = $idMapping[$el->value->type][$el->value->id];
                } else if ($el->type == "image" and is_object($el->value) and $el->value->id) {
                    $el->value->id = $idMapping["asset"][$el->value->id];
                } else if ($el->type == "wysiwyg" and is_object($el->value) and $el->value->text) {
                    $el->value->text = Tool\Text::replaceWysiwygTextRelationIds($idMapping, $el->value->text);
                } else if ($el->type == "link" and is_object($el->value) and is_array($el->value->data) and $el->value->data["internalId"]) {
                    $el->value->data["internalId"] = $idMapping[$el->value->data["internalType"]][$el->value->data["internalId"]];
                } else if ($el->type == "video" and is_object($el->value) and $el->value->type == "asset") {
                    $el->value->id = $idMapping[$el->value->type][$el->value->id];
                } else if ($el->type == "snippet" and is_object($el->value) and $el->value->id) {
                    $el->value->id = $idMapping["document"][$el->value->id];
                } else if ($el->type == "renderlet" and is_object($el->value) and $el->value->id) {
                    $el->value->id = $idMapping[$el->value->type][$el->value->id];
                }
            }
        }
    }

    /**
     * @param  Webservice\Data\Object\Concrete $apiElement
     * @return void
     */
    public function correctObjectRelations($apiElement, $idMapping)
    {
        if ($apiElement->elements) {
            foreach ($apiElement->elements as $el) {
                if ($el->type == "href" and $el->value["id"]) {
                    $el->value["id"] = $idMapping[$el->value["type"]][$el->value["id"]];
                } else if ($el->type == "image" and $el->value) {
                    $el->value = $idMapping["asset"][$el->value];
                } else if ($el->type == "link" and $el->value["internal"]) {
                    $el->value["internal"] = $idMapping[$el->value["internalType"]][$el->value["internal"]];
                } else if ($el->type == "multihref") {
                    if (is_array($el->value)) {
                        for ($i = 0; $i < count($el->value); $i++) {
                            $el->value[$i]["id"] = $idMapping[$el->value[$i]["type"]][$el->value[$i]["id"]];
                        }
                    }

                } else if ($el->type == "objects") {
                    if (is_array($el->value)) {
                        for ($i = 0; $i < count($el->value); $i++) {
                            $el->value[$i]["id"] = $idMapping["object"][$el->value[$i]["id"]];
                        }
                    }

                } else if ($el->type == "wysiwyg") {
                    $el->value = Tool\Text::replaceWysiwygTextRelationIds($idMapping, $el->value);
                } else if ($el->type == "fieldcollections") {
                    if ($el instanceof Webservice\Data\Object\Element and is_array($el->value)) {
                        foreach ($el->value as $fieldCollectionEl) {
                            if (is_array($fieldCollectionEl->value)) {
                                foreach ($fieldCollectionEl->value as $collectionItem) {
                                    if ($collectionItem->type == "image") {
                                        $collectionItem->value = $idMapping["asset"][$collectionItem->value];
                                    } else if ($collectionItem->type == "wysiwyg") {
                                        $collectionItem->value = Tool\Text::replaceWysiwygTextRelationIds($idMapping, $collectionItem->value);
                                    } else if ($collectionItem->type == "link" and $collectionItem->value["internalType"]) {
                                        $collectionItem->value["internal"] = $idMapping[$collectionItem->value["internalType"]][$collectionItem->value["internal"]];
                                    } else if ($collectionItem->type == "href" and $collectionItem->value["id"]){
                                        $collectionItem->value["id"] = $idMapping[$collectionItem->value["type"]][$collectionItem->value["id"]];
                                    } else if (($collectionItem->type == "objects" or $collectionItem->type == "multihref") and is_array($collectionItem->value) and count($collectionItem->value)>0){
                                        for($i=0; $i < count($collectionItem->value);$i++){
                                            if($collectionItem->value[$i]["id"]){
                                                $collectionItem->value[$i]["id"] = $idMapping[$collectionItem->value[$i]["type"]][$collectionItem->value[$i]["id"]];
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }


                } else if ($el->type == "localizedfields") {
                    if (is_array($el->value)) {
                        foreach ($el->value as $localizedDataEl) {

                            if ($localizedDataEl->type == "image") {
                                $localizedDataEl->value = $idMapping["asset"][$localizedDataEl->value];
                            } else if ($localizedDataEl->type == "wysiwyg") {
                                $localizedDataEl->value = Tool\Text::replaceWysiwygTextRelationIds($idMapping, $localizedDataEl->value);
                            } else if ($localizedDataEl->type == "link" and $localizedDataEl->value["internalType"]) {
                                $localizedDataEl->value["internal"] = $idMapping[$localizedDataEl->value["internalType"]][$localizedDataEl->value["internal"]];
                            }

                        }
                    }
                }
            }
        }
    }

    /**
     * @param $element
     * @param bool $creation
     * @return $this
     * @throws \Exception
     */
    public function setModificationParams($element, $creation = false)
    {
        $user = $this->user;
        if (!$user instanceof Model\User) {
            throw new \Exception("No user present");
        }
        if ($creation) {
            $element->setUserOwner($user->getId());
        }
        $element->setUserModification($user->getId());
        $element->setModificationDate(time());
        return $this;
    }
}