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

namespace Pimcore\Tool;

class XmlWriter extends \Zend_Config_Writer_Xml {

    /**
     * name of the root element
     *
     * @var string
     */
    protected $rootElementName = 'data';

    /**
     * Attributes for the root element
     *
     * @var array
     */
    protected $rootElementAttributes = array();

    /**
     * @var string
     */
    protected $encoding = 'utf-8';

    /**
     * array of options to set
     *
     * @param array $options
     */
    public function __construct($options = array()){
        foreach($options as $key => $value){
            $setter = "set" . ucfirst($key);
            if(method_exists($this,$setter)){
                $this->$setter($value);
            }
        }
    }

    /**
     * @var bool
     */
    protected $formatOutput = true;

    /**
     * @return array
     */
    public function getRootElementAttributes()
    {
        return $this->rootElementAttributes;
    }

    /**
     * @param array $rootElementAttributes
     *
     * @return $this
     */
    public function setRootElementAttributes($rootElementAttributes)
    {
        $this->rootElementAttributes = $rootElementAttributes;
        return $this;
    }

    /**
     * @param string $name
     *
     * @return $this
     */
    public function setRootElementName($name){
        $this->rootElementName = $name;
        return $this;
    }

    /**
     * @param \Zend_Config $config
     *
     * @return $this|\Zend_Config_Writer
     */
    public function setConfig($config)
    {
        if(is_array($config)){
            $config = new \Zend_Config($config);
        }
        $this->_config = $config;

        return $this;
    }

    /**
     * @return string
     */
    public function getRootElementName(){
        return $this->rootElementName;
    }

    /**
     * @param $encoding
     *
     * @return $this
     */
    public function setEncoding($encoding){
        $this->encoding  = $encoding;
        return $this;
    }

    /**
     * @return string
     */
    public function getEncoding(){
        return $this->encoding;
    }

    /**
     * @return boolean
     */
    public function getFormatOutput()
    {
        return $this->formatOutput;
    }

    /**
     * @param boolean $formatOutput
     *
     * @return $this
     */
    public function setFormatOutput($formatOutput)
    {
        $this->formatOutput = $formatOutput;
        return $this;
    }

    protected function addChildConsiderCdata($xml,$key,$data){
        $sData = (string) $data;
        $child = @$xml->addChild($key, $sData);

        if((string)$child != $sData){
            $child = $xml->$key->addCData((string) $data);
        }
        return $child;
    }

    /**
     * returns the XML string
     *
     * @return string
     * @throws \Zend_Config_Exception
     */
    public function render()
    {

        $xml         = new SimpleXMLExtended('<'.$this->getRootElementName().' />');
        if($this->_config){
            $extends     = $this->_config->getExtends();
            $sectionName = $this->_config->getSectionName();
            if (is_string($sectionName)) {
                $child = $xml->addChild($sectionName);

                $this->_addBranch($this->_config, $child, $xml);
            } else {
                foreach ($this->_config as $sectionName => $data) {
                    if (!($data instanceof \Zend_Config)) {
                        $this->addChildConsiderCdata($xml,$sectionName,$data);
                    } else {
                        $child = $xml->addChild($sectionName);
                        if (isset($extends[$sectionName])) {
                            $child->addAttribute('zf:extends', $extends[$sectionName], \Zend_Config_Xml::XML_NAMESPACE);
                        }
                        $this->_addBranch($data, $child, $xml);
                    }
                }
            }
        }

        $dom = dom_import_simplexml($xml)->ownerDocument;
        if($encoding = $this->getEncoding()){
            $dom->encoding = $encoding;
        }
        
        $dom->formatOutput = $this->getFormatOutput();

        $xmlString = $dom->saveXML();

        return $xmlString;
    }

    /**
     * Add a branch to an XML object recursively
     *
     * @param  \Zend_Config      $config
     * @param  \SimpleXMLElement $xml
     * @param  \SimpleXMLElement $parent
     * @return void
     */
    protected function _addBranch(\Zend_Config $config, \SimpleXMLElement $xml, \SimpleXMLElement $parent)
    {
        $branchType = null;

        foreach ($config as $key => $value) {
            if ($branchType === null) {
                if (is_numeric($key)) {
                    $branchType = 'numeric';
                    $branchName = $xml->getName();
                    $xml        = $parent;

                    unset($parent->{$branchName});
                } else {
                    $branchType = 'string';
                }
            } else if ($branchType !== (is_numeric($key) ? 'numeric' : 'string')) {
                // require_once 'Zend/Config/Exception.php';
                throw new \Zend_Config_Exception('Mixing of string and numeric keys is not allowed');
            }

            if ($branchType === 'numeric') {
                if ($value instanceof \Zend_Config) {
                    $child = $parent->addChild($branchName);

                    $this->_addBranch($value, $child, $parent);
                } else {
                    $parent->addChild($branchName, (string) $value);
                }
            } else {
                if ($value instanceof \Zend_Config) {
                    $child = $xml->addChild($key);

                    $this->_addBranch($value, $child, $xml);
                } else {
                    $this->addChildConsiderCdata($xml,$key,$value);
                }
            }
        }
    }



    /**
     *  displays the rendered XML
     */
    public function displayXML(){
       # header("Content-Type: application/xml");
        die($this->render());
    }
}