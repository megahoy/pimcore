<?php
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @category   Pimcore
 * @package    Document
 * @copyright  Copyright (c) 2009-2015 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */

namespace Pimcore\Model\Document\Hardlink;

use Pimcore\Model;
use Pimcore\Model\Document;

trait Wrapper {

    /**
     * @var Document\Hardlink
     */
    protected $hardLinkSource;

    /**
     * @var Document
     */
    protected $sourceDocument;

    // OVERWRITTEN METHODS
    public function save() {
        $this->raiseHardlinkError();
    }

    protected function update() {
        $this->raiseHardlinkError();
    }

    public function delete() {
        $this->raiseHardlinkError();
    }

    public function getProperties() {

        if($this->properties == null) {

            if($this->getHardLinkSource()->getPropertiesFromSource()) {
                $sourceProperties = $this->getDao()->getProperties();
            } else {
                $sourceProperties = array();
            }

            if($this->getSourceDocument()) {
                // if we have a source document, that means that this document is not directly linked, it's a
                // child of a hardlink that uses "childFromSource", so in this case we use the source properties
                // this is especially important for the navigation, otherwise all children will have the same
                // navigation_name as the source hardlink, which doesn't make sense at all
                $sourceProperties = $this->getSourceDocument()->getProperties();
            }

            $hardLinkProperties = array();
            $hardLinkSourceProperties = $this->getHardLinkSource()->getProperties();
            foreach ($hardLinkSourceProperties as $key => $prop) {
                $prop = clone $prop;
                $prop->setInherited(true);

                // if the property doesn't exist in the source-properties just add it
                if(!array_key_exists($key, $sourceProperties)) {
                    $hardLinkProperties[$key] = $prop;
                } else {
                    // if the property does exist in the source properties but it is inherited, then overwrite it with the hardlink property
                    if($sourceProperties[$key]->isInherited()) {
                        $hardLinkProperties[$key] = $prop;
                    }
                }
            }


            $properties = array_merge($sourceProperties, $hardLinkProperties);
            $this->setProperties($properties);
        }

        return $this->properties;
    }

    public function getChilds() {

        if ($this->childs === null) {
            $hardLink = $this->getHardLinkSource();
            $childs = array();

            if($hardLink->getChildsFromSource() && $hardLink->getSourceDocument() && !\Pimcore::inAdmin()) {
                foreach(parent::getChilds() as $c) {
                    $sourceDocument = $c;
                    $c = Service::wrap($c);

                    if($c) {
                        $c->setHardLinkSource($hardLink);
                        $c->setSourceDocument($sourceDocument);
                        $c->setPath(preg_replace("@^" . preg_quote($hardLink->getSourceDocument()->getRealFullpath()) . "@", $hardLink->getRealFullpath(), $c->getRealPath()));

                        $childs[] = $c;
                    }
                }
            }

            $this->setChilds($childs);
        }

        return $this->childs;
    }

    public function hasChilds() {
        $hardLink = $this->getHardLinkSource();

        if($hardLink->getChildsFromSource() && $hardLink->getSourceDocument() && !\Pimcore::inAdmin()) {
            return parent::hasChilds();
        }

        return false;
    }

    /**
     * @throws \Exception
     * @return void
     */
    protected function raiseHardlinkError () {
        throw new \Exception("Method no supported by hardlinked documents");
    }

    /**
     * @param Document\Hardlink $hardLinkSource
     * @return $this
     */
    public function setHardLinkSource($hardLinkSource)
    {
        $this->hardLinkSource = $hardLinkSource;
        return $this;
    }

    /**
     * @return Document\Hardlink
     */
    public function getHardLinkSource()
    {
        return $this->hardLinkSource;
    }

    /**
     * @return Document
     */
    public function getSourceDocument()
    {
        return $this->sourceDocument;
    }

    /**
     * @param Document $sourceDocument
     */
    public function setSourceDocument($sourceDocument)
    {
        $this->sourceDocument = $sourceDocument;
    }
}
