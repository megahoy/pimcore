<?php
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @category   Pimcore
 * @package    User
 * @copyright  Copyright (c) 2009-2015 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */

namespace Pimcore\Model\User\Permission\Definition;

use Pimcore\Model;

class Dao extends Model\Dao\AbstractDao {

    /**
     *
     */
    public function save() {
        try {
            $this->db->insert("users_permission_definitions", array(
                "key" => $this->model->getKey()
            ));
        } catch (\Exception $e) {
            \Logger::warn($e);
        }
    }
}
