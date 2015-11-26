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

namespace Pimcore\Model;

class WebsiteSetting extends AbstractModel {

    /**
     * @var integer
     */
    public $id;
    
    /**
     * @var string
     */
    public $name;

    /**
     * @var
     */
    public $type;

    /**
     * @var
     */
    public $data;

    /**
     * @var
     */
    public $siteId;

    /**
     * @var
     */
    public $creationDate;

    /**
     * @var
     */
    public $modificationDate;



    /**
     * @param integer $id
     * @return WebsiteSetting
     */
    public static function getById($id) {
        $setting = new self();

        $setting->setId(intval($id));
        $setting->getDao()->getById();
        return $setting;
    }
    
    /**
     * @param string $name
     * @return WebsiteSetting
     */
    public static function getByName($name, $siteId = null) {

        // create a tmp object to obtain the id
        $setting = new self();

        try {
            $setting->getDao()->getByName($name, $siteId);
        } catch (\Exception $e) {
            \Logger::error($e);
            return null;
        }
        return $setting;
    }

    /**
     * @return integer
     */
    public function getId() {
        return $this->id;
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
     * @param string $name
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
     * @return mixed
     */
    public function getCreationDate()
    {
        return $this->creationDate;
    }

    /**
     * @param $data
     * @return $this
     */
    public function setData($data)
    {
        $this->data = $data;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
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
     * @return mixed
     */
    public function getModificationDate()
    {
        return $this->modificationDate;
    }

    /**
     * @param $siteId
     * @return $this
     */
    public function setSiteId($siteId)
    {
        $this->siteId = (int) $siteId;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getSiteId()
    {
        return (int) $this->siteId;
    }

    /**
     * @param $type
     * @return $this
     */
    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return void
     */
    public function clearDependentCache() {
        Cache::clearTag("website_config");
    }
}
