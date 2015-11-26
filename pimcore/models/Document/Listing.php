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

namespace Pimcore\Model\Document;

use Pimcore\Model;

class Listing extends Model\Listing\AbstractListing implements \Zend_Paginator_Adapter_Interface, \Zend_Paginator_AdapterAggregate, \Iterator {

    /**
     * Return all documents as Type Document. eg. for trees an so on there isn't the whole data required
     *
     * @var boolean
     */
    public $objectTypeDocument = false;

    /**
     * Contains the results of the list
     *
     * @var array
     */
    public $documents = null;
    
    /**
     * @var boolean
     */
    public $unpublished = false;
    
    /**
     * Valid order keys
     *
     * @var array
     */
    public $validOrderKeys = array(
        "creationDate",
        "modificationDate",
        "id",
        "key",
        "index"
    );

    /**
     * Tests if the given key is an valid order key to sort the results
     *
     * @return boolean
     */
    public function isValidOrderKey($key) {
        return true;
    }

    /**
     * @return array
     */
    public function getDocuments() {
        if ($this->documents === null) {
            $this->load();
        }
        return $this->documents;
    }

    /**
     * @param array $documents
     * @return void
     */
    public function setDocuments($documents) {
        $this->documents = $documents;
        return $this;
    }
    
    /**
     * @return bool
     */
    public function getUnpublished() {
        return $this->unpublished;
    }
    
    /**
     * @return bool
     */
    public function setUnpublished($unpublished) {
        $this->unpublished = (bool) $unpublished;
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
        $this->getDocuments();
        reset($this->documents);
    }

    public function current() {
        $this->getDocuments();
        $var = current($this->documents);
        return $var;
    }

    public function key() {
        $this->getDocuments();
        $var = key($this->documents);
        return $var;
    }

    public function next() {
        $this->getDocuments();
        $var = next($this->documents);
        return $var;
    }

    public function valid() {
        $this->getDocuments();
        $var = $this->current() !== false;
        return $var;
    }
}
