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

namespace Pimcore\Model\Object\Classificationstore\KeyGroupRelation;

use Pimcore\Model;

class Listing extends Model\Listing\AbstractListing {

    /**
     * Contains the results of the list.
     *
     * @var array
     */
    public $list = array();

    /**
     * Tests if the given key is an valid order key to sort the results
     *
     * @todo remove the dummy-always-true rule
     * @return boolean
     */
    public function isValidOrderKey($key) {
        return true;
    }

    /**
     * @return array
     */
    public function getList() {
        return $this->list;
    }

    /**
     * @param array
     * @return void
     */
    public function setList($theList) {
        $this->list = $theList;
        return $this;
    }
}
