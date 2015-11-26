<?php
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @category   Pimcore
 * @package    Element
 * @copyright  Copyright (c) 2009-2015 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */

namespace Pimcore\Model\Element\Sanitycheck;

use Pimcore\Model;

class Dao extends Model\Dao\AbstractDao {

    /**
     * Save to database
     *
     * @return void
     */
    public function save() {

        $sanityCheck = get_object_vars($this->model);

        foreach ($sanityCheck as $key => $value) {
            if (in_array($key, $this->getValidTableColumns("sanitycheck"))) {
                $data[$key] = $value;
            }
        }

        try {
            $this->db->insert("sanitycheck", $data);
        }
        catch (\Exception $e) {
           //probably duplicate
        }

        return true;
    }

    /**
     * Deletes object from database
     *
     * @return void
     */
    public function delete() {
        $this->db->delete("sanitycheck", $this->db->quoteInto("id = ?", $this->model->getId()) . " AND " . $this->db->quoteInto("type = ?", $this->model->getType()));
    }

    public  function getNext(){

        $data = $this->db->fetchRow("SELECT * FROM sanitycheck LIMIT 1");
        if (is_array($data)) {
            $this->assignVariablesToModel($data);
        }  


    }

}