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

namespace Pimcore\Model;

use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\Element\Service;

class Property extends AbstractModel {

    /**
     * @var string
     */
    public $name;

    /**
     * @var mixed
     */
    public $data;

    /**
     * @var string
     */
    public $type;

    /**
     * @var string
     */
    public $ctype;

    /**
     * @var string
     */
    public $cpath;

    /**
     * @var integer
     */
    public $cid;

    /**
     * @var boolean
     */
    public $inheritable;

    /**
     * @var boolean
     */
    public $inherited = false;

    /**
     * Takes data from editmode and convert it to internal objects
     *
     * @param mixed $data
     * @return void
     */
    public function setDataFromEditmode($data) {
        // IMPORTANT: if you use this method be sure that the type of the property is already set

        if(in_array($this->getType(), array("document","asset","object"))) {
            $el = Element\Service::getElementByPath($this->getType(), $data);
            $this->data = null;
            if($el) {
                $this->data = $el->getId();
            }
        } else if ($this->type == "date") {
            $this->data = new \Zend_Date($data);
        }
        else if ($this->type == "bool") {
            $this->data = false;
            if (!empty($data)) {
                $this->data = true;
            }
        }
        else {
            // plain text
            $this->data = $data;
        }
        return $this;
    }

    /**
     * Takes data from resource and convert it to internal objects
     *
     * @param mixed $data
     * @return static
     */
    public function setDataFromResource($data) {
        // IMPORTANT: if you use this method be sure that the type of the property is already set
        // do not set data for object, asset and document here, this is loaded dynamically when calling $this->getData();
        if ($this->type == "date") {
            $this->data = \Pimcore\Tool\Serialize::unserialize($data);
        }
        else if ($this->type == "bool") {
            $this->data = false;
            if (!empty($data)) {
                $this->data = true;
            }
        }
        else {
            // plain text
            $this->data = $data;
        }
        return $this;
    }

    /**
     * @return integer
     */
    public function getCid() {
        return $this->cid;
    }

    /**
     * @return string
     */
    public function getCtype() {
        return $this->ctype;
    }

    /**
     * @return mixed
     */
    public function getData() {

        // lazy-load data of type asset, document, object
        if(in_array($this->getType(), array("document","asset","object")) && !$this->data instanceof ElementInterface && is_numeric($this->data)) {
            return Element\Service::getElementById($this->getType(), $this->data);
        }

        return $this->data;
    }

    /**
     * @return string
     */
    public function getName() {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getType() {
        return $this->type;
    }

    /**
     * @param integer $cid
     * @return static
     */
    public function setCid($cid) {
        $this->cid = (int) $cid;
        return $this;
    }

    /**
     * @param string $ctype
     * @return static
     */
    public function setCtype($ctype) {
        $this->ctype = $ctype;
        return $this;
    }

    /**
     * @param mixed $data
     * @return static
     */
    public function setData($data) {

        if($data instanceof ElementInterface) {
            $this->setType(Service::getElementType($data));
            $data = $data->getId();
        }

        $this->data = $data;
        return $this;
    }

    /**
     * @param string $name
     * @return static
     */
    public function setName($name) {
        $this->name = $name;
        return $this;
    }

    /**
     * @param string $type
     * @return static
     */
    public function setType($type) {
        $this->type = $type;
        return $this;
    }

    /**
     * @return string
     */
    public function getCpath() {
        return $this->cpath;
    }

    /**
     * @return boolean
     */
    public function getInherited() {
        return $this->inherited;
    }

    /**
     * Alias for getInherited()
     *
     * @return boolean
     */
    public function isInherited() {
        return $this->getInherited();
    }

    /**
     * @param string $cpath
     * @return static
     */
    public function setCpath($cpath) {
        $this->cpath = $cpath;
        return $this;
    }

    /**
     * @param boolean $inherited
     * @return static
     */
    public function setInherited($inherited) {
        $this->inherited = (bool) $inherited;
        return $this;
    }

    /**
     * @return boolean
     */
    public function getInheritable() {
        return $this->inheritable;
    }

    /**
     * @param boolean $inheritable
     * @return static
     */
    public function setInheritable($inheritable) {
        $this->inheritable = (bool) $inheritable;
        return $this;
    }
    
    /**
     * @return array
     */
    public function resolveDependencies () {
        
        $dependencies = array();
        
        if ($this->getData() instanceof ElementInterface) {
            $elementType = Element\Service::getElementType($this->getData());
            $key = $elementType . "_" . $this->getData()->getId();
            $dependencies[$key] = array(
                "id" => $this->getData()->getId(),
                "type" => $elementType
            );
        }
        
        return $dependencies;
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
     * @param array $idMapping
     */
    public function rewriteIds($idMapping) {
        if(!$this->isInherited()) {
            if(array_key_exists($this->getType(), $idMapping)) {
                if($this->getData() instanceof ElementInterface) {
                    if(array_key_exists((int) $this->getData()->getId(), $idMapping[$this->getType()])) {
                        $this->setData(Element\Service::getElementById($this->getType(), $idMapping[$this->getType()][$this->getData()->getId()]));
                    }
                }
            }
        }
    }
}
