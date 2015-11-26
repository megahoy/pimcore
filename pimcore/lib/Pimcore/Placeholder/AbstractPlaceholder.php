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

namespace Pimcore\Placeholder;

use Pimcore\Model\Document;

abstract class AbstractPlaceholder
{
    /**
     * The placeholder string e.g "%Object(object_id,{"method" : "getName"})"
     *
     * @var string
     */
    protected $placeholderString = null;

    /**
     * The placeholder key passed to determine the dynamic parameter
     *
     * @var string
     */
    protected $placeholderKey = null;

    /**
     * The config object passed from the placeholder
     * If no config object was passed a empty \Zend_Config_Json is passed
     *
     * @var \Zend_Config_Json
     */
    protected $placeholderConfig = null;

    /**
     * The passed Document Object
     *
     * @var Document | null
     */
    protected $document = null;

    /**
     * All dynamic parameters which are passed to the Placeholder
     *
     * @var array
     */
    protected $params = array();

    /**
     * The Content as string
     *
     * @var string
     */
    protected $contentString = null;

    /**
     * @var \Zend_Locale
     */
    protected $locale = null;

    /**
     * @param $string
     * @return $this
     */
    public function setPlaceholderString($string)
    {
        $this->placeholderString = $string;
        return $this;
    }

    /**
     * @return string
     */
    public function getPlaceholderString()
    {
        return $this->placeholderString;
    }

    /**
     * @param $key
     * @return $this
     */
    public function setPlaceholderKey($key)
    {
        $this->placeholderKey = $key;
        return $this;
    }

    /**
     * Returns the Placehodler key
     *
     * @return string
     */
    public function getPlaceholderKey()
    {
        return $this->placeholderKey;
    }

    /**
     * @param \Zend_Config_Json $config
     * @return $this
     */
    public function setPlaceholderConfig(\Zend_Config_Json $config)
    {
        $this->placeholderConfig = $config;
        return $this;
    }

    /**
     * Returns the Placeholder config object
     *
     * @return \Zend_Config_Json
     */
    public function getPlaceholderConfig()
    {
        return $this->placeholderConfig;
    }

    /**
     * @param $params
     * @return $this
     */
    public function setParams($params)
    {
        if (is_array($params)) {
            $this->params = $params;
        }
        return $this;
    }

    /**
     * Returns the Parameters ob the Placeholder object
     *
     * @return array
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * Returns a specific parameter
     *
     * @param string $key
     * @return mixed
     */
    public function getParam($key)
    {
        if(array_key_exists($key, $this->params)) {
            return $this->params[$key];
        }
        return null;
    }

    /**
     * @param $contentString
     * @return $this
     */
    public function setContentString($contentString)
    {
        if (is_string($contentString)) {
            $this->contentString = $contentString;
        }
        return $this;
    }

    /**
     * returns the full content string
     *
     * @return null|string
     */
    public function getContentString()
    {
        return $this->contentString;
    }

    /**
     * Returns the the value of the current Placeholder parameter
     *
     * @return mixed
     */
    public function getValue()
    {
        return $this->getParam($this->getPlaceholderKey());
    }

    /**
     * @param $document
     * @return $this
     */
    public function setDocument($document)
    {
        if ($document instanceof Document) {
            $this->document = $document;
        }
        return $this;
    }

    /**
     * Returns the Document
     *
     * @return Document|null
     */
    public function getDocument()
    {
        return $this->document;
    }

    /**
     * Returns the current locale
     *
     * @return \Zend_Locale
     */
    public function getLocale()
    {
        if (is_null($this->locale)) {
            $this->setLocale();
        }
        return $this->locale;
    }

    /**
     * Try to set the locale from different sources
     *
     * @param $locale
     * @return void
     */
    public function setLocale($locale = null)
    {
        if ($locale instanceof \Zend_Locale) {
            $this->locale = $locale;
        } elseif (is_string($locale)) {
            $this->locale = new \Zend_Locale($locale);
        } elseif ($this->getParam('locale') || $this->getParam('language')) {
            $this->setLocale(($this->getParam('locale')) ? $this->getParam('locale') : $this->getParam('language'));
        } else {
            $document = $this->getDocument();
            if ($document instanceof Document && $document->getProperty("language")) {
                $this->setLocale($document->getProperty("language"));
            }

            if (is_null($this->locale)) { //last chance -> get it from registry or use the first Language defined in the system settings
                if(\Zend_Registry::isRegistered("Zend_Locale")) {
                    $this->locale = \Zend_Registry::get("Zend_Locale");
                } else {
                    list($language) = \Pimcore\Tool::getValidLanguages();
                    $this->locale = new \Zend_Locale($language);
                }
            }
        }
        return $this;
    }

    /**
     * Returns the current language
     *
     * @return string
     */
    public function getLanguage()
    {
        return (string) $this->getLocale();
    }

    /**
     * Will be used as replacement if the passed parameter is empty
     *
     * @return string
     */
    public function getEmptyValue()
    {
        return '';
    }


    /**
     * Has to return an appropriate value for a test replacement
     *
     * @abstract
     * @return string
     */
    abstract function getTestValue();

    /**
     * Has to return the placeholder with the corresponding value
     *
     * @abstract
     * @return string
     */
    abstract function getReplacement();

}