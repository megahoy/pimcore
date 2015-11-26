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

class Listing extends Model\Listing\AbstractListing implements \Zend_Paginator_Adapter_Interface, \Zend_Paginator_AdapterAggregate, \Iterator {

    /**
     * List of assets
     *
     * @var array
     */
    public $assets = null;

    /**
     * List of valid order keys
     *
     * @var array
     */
    public $validOrderKeys = array(
        "creationDate",
        "modificationDate",
        "id",
        "filename",
        "type",
        "parentId",
        "path",
        "mimetype"
    );

    /**
     * Test if the passed key is valid
     *
     * @param string $key
     * @return boolean
     */
    public function isValidOrderKey($key) {
        return true;
    }

    /**
     * @return array
     */
    public function getAssets() {
        if ($this->assets === null) {
            $this->load();
        }
        return $this->assets;
    }

    /**
     * @param string $assets
     * @return void
     */
    public function setAssets($assets) {
        $this->assets = $assets;
        return $this;
    }
    
    
    /**
     *
     * Methods for \Zend_Paginator_Adapter_Interface
     */

    public function count() {
        return $this->getTotalCount();
    }

    public function getItems($offset, $itemCountPerPage) {
        $this->setOffset($offset);
        $this->setLimit($itemCountPerPage);
        return $this->load();
    }

    public function getPaginatorAdapter() {
        return $this;
    }
    

    /**
     * Methods for Iterator
     */

    public function rewind() {
        $this->getAssets();
        reset($this->assets);
    }

    public function current() {
        $this->getAssets();
        $var = current($this->assets);
        return $var;
    }

    public function key() {
        $this->getAssets();
        $var = key($this->assets);
        return $var;
    }

    public function next() {
        $this->getAssets();
        $var = next($this->assets);
        return $var;
    }

    public function valid() {
        $this->getAssets();
        $var = $this->current() !== false;
        return $var;
    }
}
