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

namespace Pimcore\Model\Object\Data;

use Pimcore\Model;
use Pimcore\Model\Object;

class ObjectMetadata extends Model\AbstractModel {

    /**
     * @var Object\Concrete
     */
    protected $object;

    /**
     * @var string
     */
    protected $fieldname;

    /**
     * @var array
     */
    protected $columns = array();

    /**
     * @var array
     */
    protected $data = array();

    /**
     * @param $fieldname
     * @param array $columns
     * @param null $object
     * @throws \Exception
     */
    public function __construct($fieldname, $columns = array(), $object = null) {
        $this->fieldname = $fieldname;
        $this->object = $object;
        $this->columns = $columns;
    }

    /**
     * @param $name
     * @param $arguments
     * @return mixed|void
     * @throws \Exception
     */
    public function __call($name, $arguments) {

        if(substr($name, 0, 3) == "get") {
            $key = strtolower(substr($name, 3, strlen($name)-3));

            if(in_array($key, $this->columns)) {
                return $this->data[$key];
            }

            throw new \Exception("Requested data $key not available");
        }

        if(substr($name, 0, 3) == "set") {
            $key = strtolower(substr($name, 3, strlen($name)-3));
            if(in_array($key, $this->columns)) {
                $this->data[$key] = $arguments[0];
            } else {
                throw new \Exception("Requested data $key not available");
            }
        }
    }

    /**
     * @param $object
     * @param string $ownertype
     * @param $ownername
     * @param $position
     */
    public function save($object, $ownertype = "object", $ownername, $position) {
        $this->getDao()->save($object, $ownertype, $ownername, $position);
    }

    /**
     * @param Object\Concrete $source
     * @param $destination
     * @param $fieldname
     * @param $ownertype
     * @param $ownername
     * @param $position
     * @return mixed
     */
    public function load(Object\Concrete $source, $destination, $fieldname, $ownertype, $ownername, $position) {
        return $this->getDao()->load($source, $destination, $fieldname, $ownertype, $ownername, $position);
    }

    /**
     * @param $fieldname
     * @return $this
     */
    public function setFieldname($fieldname) {
        $this->fieldname = $fieldname;
        return $this;
    }

    /**
     * @return string
     */
    public function getFieldname() {
        return $this->fieldname;
    }

    /**
     * @param $object
     * @return $this
     */
    public function setObject($object) {
        $this->object = $object;
        return $this;
    }

    /**
     * @return Object\Concrete
     */
    public function getObject() {
        return $this->object;
    }

    /**
     * @param $object
     * @return $this
     */
    public function setElement($element) {
        return $this->setObject($element);
    }

    /**
     * @return Object\Concrete
     */
    public function getElement() {
        return $this->getObject();
    }

    /**
     * @param $columns
     * @return $this
     */
    public function setColumns($columns) {
        $this->columns = $columns;
        return $this;
    }

    /**
     * @return array
     */
    public function getColumns() {
        return $this->columns;
    }

    /**
     * @return mixed
     */
    public function __toString() {
        return $this->getObject()->__toString();
    }
}
