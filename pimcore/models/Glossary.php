<?php
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @category   Pimcore
 * @package    Glossary
 * @copyright  Copyright (c) 2009-2015 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */

namespace Pimcore\Model;

class Glossary extends AbstractModel {

    /**
     * @var integer
     */
    public $id;

    /**
     * @var string
     */
    public $text;

    /**
     * @var string
     */
    public $link;

    /**
     * @var string
     */
    public $abbr;

    /**
     * @var string
     */
    public $acronym;

    /**
     * @var string
     */
    public $language;

    /**
     * @var bool
     */
    public $casesensitive;

    /**
     * @var bool
     */
    public $exactmatch;

    /**
     * @var int
     */
    public $site;

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
     * @return Glossary
     */
    public static function getById($id) {

        $glossary = new self();
        $glossary->setId(intval($id));
        $glossary->getDao()->getById();

        return $glossary;
    }

    /**
     * @return Glossary
     */
    public static function create() {
        $glossary = new self();
        $glossary->save();

        return $glossary;
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
     * @param string $text
     * @return void
     */
    public function setText($text) {
        $this->text = $text;
        return $this;
    }

    /**
     * @return string
     */
    public function getText() {
        return $this->text;
    }

    /**
     * @param string $link
     * @return void
     */
    public function setLink($link) {
        $this->link = $link;
        return $this;
    }

    /**
     * @return string
     */
    public function getLink() {
        return $this->link;
    }


    /**
     * @param string $abbr
     * @return void
     */
    public function setAbbr($abbr) {
        $this->abbr = $abbr;
        return $this;
    }

    /**
     * @return string
     */
    public function getAbbr() {
        return $this->abbr;
    }


    /**
     * @param string $acronym
     * @return void
     */
    public function setAcronym($acronym) {
        $this->acronym = $acronym;
        return $this;
    }

    /**
     * @return string
     */
    public function getAcronym() {
        return $this->acronym;
    }


    /**
     * @param string $language
     * @return void
     */
    public function setLanguage($language) {
        $this->language = $language;
        return $this;
    }

    /**
     * @return string
     */
    public function getLanguage() {
        return $this->language;
    }

    /**
     * @param $casesensitive
     * @return $this
     */
    public function setCasesensitive($casesensitive)
    {
        $this->casesensitive = (bool) $casesensitive;
        return $this;
    }

    /**
     * @return boolean
     */
    public function getCasesensitive()
    {
        return $this->casesensitive;
    }

    /**
     * @param $exactmatch
     * @return $this
     */
    public function setExactmatch($exactmatch)
    {
        $this->exactmatch = (bool) $exactmatch;
        return $this;
    }

    /**
     * @return boolean
     */
    public function getExactmatch()
    {
        return $this->exactmatch;
    }

    /**
     * @param $site
     * @return $this
     */
    public function setSite($site)
    {
        if($site instanceof Site) {
            $site = $site->getId();
        }
        $this->site = (int) $site;
        return $this;
    }

    /**
     * @return int
     */
    public function getSite()
    {
        return $this->site;
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
