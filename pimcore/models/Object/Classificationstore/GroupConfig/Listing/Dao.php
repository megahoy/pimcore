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

namespace Pimcore\Model\Object\Classificationstore\GroupConfig\Listing;

use Pimcore\Model;
use Pimcore\Model\Object;

class Dao extends Model\Listing\Dao\AbstractDao {

    /**
     * Loads a list of Classificationstore group configs for the specifies parameters, returns an array of config elements
     *
     * @return array
     */
    public function load() {
        $sql = "SELECT id FROM " . Object\Classificationstore\GroupConfig\Dao::TABLE_NAME_GROUPS . $this->getCondition() . $this->getOrder() . $this->getOffsetLimit();
        $configsData = $this->db->fetchCol($sql,  $this->model->getConditionVariables());

        $configData = array();
        foreach ($configsData as $config) {
            $configData[] = Object\Classificationstore\GroupConfig::getById($config);
        }

        $this->model->setList($configData);
        return $configData;
    }

    /**
     * @return array
     */
    public function getDataArray() {
        $configsData = $this->db->fetchAll("SELECT * FROM " . Object\Classificationstore\GroupConfig\Dao::TABLE_NAME_GROUPS . $this->getCondition() . $this->getOrder() . $this->getOffsetLimit(), $this->model->getConditionVariables());
        return $configsData;
    }

    /**
     * @return int
     */
    public function getTotalCount() {

        try {
            $amount = (int) $this->db->fetchOne("SELECT COUNT(*) as amount FROM " . Object\Classificationstore\GroupConfig\Dao::TABLE_NAME_GROUPS . " ". $this->getCondition(), $this->model->getConditionVariables());
        } catch (\Exception $e) {

        }

        return $amount;
    }
}
