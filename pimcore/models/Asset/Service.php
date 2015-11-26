<?php
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @category   Pimcore
 * @package    Asset
 * @copyright  Copyright (c) 2009-2015 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */

namespace Pimcore\Model\Asset;

use Pimcore\Model;
use Pimcore\Model\Asset;
use Pimcore\Model\Element;

class Service extends Model\Element\Service {

    /**
     * @var Model\User
     */
    protected $_user;
    /**
     * @var array
     */
    protected $_copyRecursiveIds;

    /**
     * @param  Model\User $user
     */
    public function __construct($user = null) {
        $this->_user = $user;
    }

    /**
     * @param  Model\Asset $target
     * @param  Model\Asset $source
     * @return Model\Asset copied asset
     */
    public function copyRecursive($target, $source) {

        // avoid recursion
        if (!$this->_copyRecursiveIds) {
            $this->_copyRecursiveIds = array();
        }
        if (in_array($source->getId(), $this->_copyRecursiveIds)) {
            return;
        }

        $source->getProperties();

        $new = clone $source;
        $new->id = null;
        if($new instanceof Asset\Folder){
            $new->setChilds(null);
        }

        $new->setFilename(Element\Service::getSaveCopyName("asset", $new->getFilename(), $target));
        $new->setParentId($target->getId());
        $new->setUserOwner($this->_user->getId());
        $new->setUserModification($this->_user->getId());
        $new->setDao(null);
        $new->setLocked(false);
        $new->setCreationDate(time());
        $new->setStream($source->getStream());
        $new->save();

        // add to store
        $this->_copyRecursiveIds[] = $new->getId();

        foreach ($source->getChilds() as $child) {
            $this->copyRecursive($new, $child);
        }

        if($target instanceof Asset\Folder){
            $this->updateChilds($target,$new);
        }


        return $new;
    }

    /**
     * @param  Asset $target
     * @param  Asset $source
     * @return Asset copied asset
     */
    public function copyAsChild($target, $source) {

        $source->getProperties();

        $new = clone $source;
        $new->id = null;

        if($new instanceof Asset\Folder){
            $new->setChilds(null);
        }
        $new->setFilename(Element\Service::getSaveCopyName("asset", $new->getFilename(), $target));
        $new->setParentId($target->getId());
        $new->setUserOwner($this->_user->getId());
        $new->setUserModification($this->_user->getId());
        $new->setDao(null);
        $new->setLocked(false);
        $new->setCreationDate(time());
        $new->setStream($source->getStream());
        $new->save();

        if($target instanceof Asset\Folder){
            $this->updateChilds($target,$new);
        }

        return $new;
    }

    /**
     * @param $target
     * @param $source
     * @return mixed
     * @throws \Exception
     */
    public function copyContents($target, $source) {

        // check if the type is the same
        if (get_class($source) != get_class($target)) {
            throw new \Exception("Source and target have to be the same type");
        }

        if (!$source instanceof Asset\Folder) {
            $target->setStream($source->getStream());
            $target->setCustomSettings($source->getCustomSettings());
        }

        $target->setUserModification($this->_user->getId());
        $target->setProperties($source->getProperties());
        $target->save();

        return $target;
    }


    /**
     * @param  Asset $asset
     * @return void
     */
    public static function gridAssetData($asset) {

        $data = Element\Service::gridElementData($asset);
        return $data;
    }

    /**
     * @static
     * @param $path
     * @return bool
     */
    public static function pathExists ($path, $type = null) {

        $path = Element\Service::correctPath($path);

        try {
            $asset = new Asset();

            if (\Pimcore\Tool::isValidPath($path)) {
                $asset->getDao()->getByPath($path);
                return true;
            }
        }
        catch (\Exception $e) {

        }

        return false;
    }

    /**
     * @static
     * @param Element\ElementInterface $element
     * @return Element\ElementInterface
     */
    public static function loadAllFields (Element\ElementInterface $element) {

        $element->getProperties();
        return $element;
    }

    /**
     * Rewrites id from source to target, $rewriteConfig contains
     * array(
     *  "document" => array(
     *      SOURCE_ID => TARGET_ID,
     *      SOURCE_ID => TARGET_ID
     *  ),
     *  "object" => array(...),
     *  "asset" => array(...)
     * )
     * @param $asset
     * @param $rewriteConfig
     * @return Asset
     */
    public static function rewriteIds($asset, $rewriteConfig) {

        // rewriting properties
        $properties = $asset->getProperties();
        foreach ($properties as &$property) {
            $property->rewriteIds($rewriteConfig);
        }
        $asset->setProperties($properties);

        return $asset;
    }

    /**
     * @param $metadata
     * @return array
     */
    public static function minimizeMetadata($metadata) {
        if (!is_array($metadata)) {
            return $metadata;
        }

        $result = array();
        foreach ($metadata as $item) {
            $type = $item["type"];
            switch ($type) {
                case "document":
                case "asset":
                case "object":
                    {
                        $element = Element\Service::getElementByPath($type, $item["data"]);
                        if ($element) {
                            $item["data"] = $element->getId();
                        } else {
                            $item["data"] = "";
                        }
                    }

                    break;
                default:
                    //nothing to do
            }
            $result[] = $item;
        }
        return $result;
    }

    /**
     * @param $metadata
     * @return array
     */
    public static function expandMetadataForEditmode($metadata) {
        if (!is_array($metadata)) {
            return $metadata;
        }

        $result = array();
        foreach ($metadata as $item) {
            $type = $item["type"];
            switch ($type) {
                case "document":
                case "asset":
                case "object":
                {
                    $element = $item["data"];
                    if (is_numeric($item["data"])) {
                        $element = Element\Service::getElementById($type, $item["data"]);
                    }
                    if ($element instanceof Element\ElementInterface) {
                        $item["data"] = $element->getFullPath();
                    } else {
                        $item["data"] = "";
                    }
                }

                    break;
                default:
                    //nothing to do
            }

            //get the config from an predefined property-set (eg. select)
            $predefined = Model\Metadata\Predefined::getByName($item['name']);
            if ($predefined && $predefined->getType() == $item['type'] && $predefined->getConfig()) {
                $item['config'] = $predefined->getConfig();
            }

            $result[] = $item;
        }
        return $result;
    }
}