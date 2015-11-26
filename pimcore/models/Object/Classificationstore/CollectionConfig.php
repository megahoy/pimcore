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

namespace Pimcore\Model\Object\Classificationstore;

use Pimcore\Model;

class CollectionConfig extends Model\AbstractModel {

    /** Group id.
     * @var integer
     */
    public $id;


    /** The group name.
     * @var string
     */
    public $name;

    /** The group description.
     * @var
     */
    public $description;

    /**
     * @var integer
     */
    public $creationDate;

    /**
     * @var integer
     */
    public $modificationDate;


    /**
     * @param integer $id
     * @return Model\Object\Classificationstore\CollectionConfig
     */
    public static function getById($id) {
        try {

            $config = new self();
            $config->setId(intval($id));
            $config->getDao()->getById();

            return $config;
        } catch (\Exception $e) {

        }
    }

    /**
     * @param $name
     * @return CollectionConfig
     */
    public static function getByName ($name) {
        try {
            $config = new self();
            $config->setName($name);
            $config->getDao()->getByName();

            return $config;
        } catch (\Exception $e) {

        }
    }

    /**
     * @return Model\Object\Classificationstore\CollectionConfig
     */
    public static function create() {
        $config = new self();
        $config->save();

        return $config;
    }

    /**
     * @param integer $id
     * @return void
     */
    public function setId($id) {
        $this->id = (int) $id;
        return $this;
    }

    /**
     * @return integer
     */
    public function getId() {
        return $this->id;
    }


    /**
     * @param string name
     * @return void
     */
    public function setName($name) {
        $this->name = $name;
        return $this;
    }

    /**
     * @return string
     */
    public function getName() {
        return $this->name;
    }

    /** Returns the description.
     * @return mixed
     */
    public function getDescription() {
        return $this->description;
    }

    /** Sets the description.
     * @param $description
     * @return Model\Object\Classificationstore\CollectionConfig
     */
    public function setDescription($description) {
        $this->description = $description;
        return $this;
    }

    /**
     * Deletes the key value group configuration
     */
    public function delete() {
        \Pimcore::getEventManager()->trigger("object.Classificationstore.collectionConfig.preDelete", $this);
        parent::delete();
        \Pimcore::getEventManager()->trigger("object.Classificationstore.collectionConfig.postDelete", $this);
    }

    /**
     * Saves the collection config
     */
    public function save() {
        $isUpdate = false;

        if ($this->getId()) {
            $isUpdate = true;
            \Pimcore::getEventManager()->trigger("object.Classificationstore.collectionConfig.preUpdate", $this);
        } else {
            \Pimcore::getEventManager()->trigger("object.Classificationstore.collectionConfig.preAdd", $this);
        }

        $model = parent::save();

        if ($isUpdate) {
            \Pimcore::getEventManager()->trigger("object.Classificationstore.collectionConfig.postUpdate", $this);
        } else {
            \Pimcore::getEventManager()->trigger("object.Classificationstore.collectionConfig.postAdd", $this);
        }
        return $model;
    }

    /**
     * @param $modificationDate
     * @return $this
     */
    public function setModificationDate($modificationDate)
    {
        $this->modificationDate = (int) $modificationDate;
        return $this;
    }

    /**
     * @return int
     */
    public function getModificationDate()
    {
        return $this->modificationDate;
    }

    /**
     * @param $creationDate
     * @return $this
     */
    public function setCreationDate($creationDate)
    {
        $this->creationDate = (int) $creationDate;
        return $this;
    }

    /**
     * @return int
     */
    public function getCreationDate()
    {
        return $this->creationDate;
    }



}
