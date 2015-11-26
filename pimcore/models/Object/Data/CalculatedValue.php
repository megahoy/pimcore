<?php
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @category   Pimcore
 * @package    Object|Class
 * @copyright  Copyright (c) 2009-2015 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */

namespace Pimcore\Model\Object\Data;

use Pimcore\Model;


class CalculatedValue {

    /** @var  string */
    public $fieldname;

    /** @var  string */
    public $ownerType = "object";

    /** @var  string */
    public $ownerName;

    /** @var int */
    public $index;

    /** @var  string */
    public $position;

    /** @var int */
    public $groupId;

    /** @var int */
    public $keyId;


    public $keyDefinition;

    public function __construct($fieldname) {
        $this->fieldname = $fieldname;
    }

    /** Sets contextual information.
     * @param $ownerType
     * @param $ownerName
     * @param $index
     * @param $position
     * @param null $groupId
     * @param null $keyId
     * @param null $keyDefinition
     */
    public function setContextualData($ownerType, $ownerName, $index, $position, $groupId = null, $keyId = null, $keyDefinition = null) {
        $this->ownerType = $ownerType;
        $this->ownerName = $ownerName;
        $this->index = $index;
        $this->position = $position;
        $this->groupId = $groupId;
        $this->keyId = $keyId;
        $this->keyDefinition = $keyDefinition;
    }

    /**
     * @return string
     */
    public function getFieldname()
    {
        return $this->fieldname;
    }

    /**
     * @return int
     */
    public function getIndex()
    {
        return $this->index;
    }

    /**
     * @return string
     */
    public function getOwnerName()
    {
        return $this->ownerName;
    }

    /**
     * @return string
     */
    public function getOwnerType()
    {
        return $this->ownerType;
    }

    /**
     * @return string
     */
    public function getPosition()
    {
        return $this->position;
    }

    /**
     * @return int
     */
    public function getGroupId()
    {
        return $this->groupId;
    }

    /**
     * @return mixed
     */
    public function getKeyDefinition()
    {
        return $this->keyDefinition;
    }

    /**
     * @return int
     */
    public function getKeyId()
    {
        return $this->keyId;
    }




}
