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

namespace Pimcore\Model\Document\PageSnippet;

use Pimcore\Model;
use Pimcore\Model\Version;
use Pimcore\Model\Document;

abstract class Dao extends Model\Document\Dao {

    /**
     * Delete all elements containing the content (tags) from the database
     *
     * @return void
     */
    public function deleteAllElements() {
        $this->db->delete("documents_elements", $this->db->quoteInto("documentId = ?", $this->model->getId() ));
    }

    /**
     * Get all elements containing the content (tags) from the database
     *
     * @return void
     */
    public function getElements() {
        $elementsRaw = $this->db->fetchAll("SELECT * FROM documents_elements WHERE documentId = ?", $this->model->getId());

        $elements = array();

        foreach ($elementsRaw as $elementRaw) {
            $class = "\\Pimcore\\Model\\Document\\Tag\\" . ucfirst($elementRaw["type"]);

            // this is the fallback for custom document tags using prefixes
            // so we need to check if the class exists first
            if(!\Pimcore\Tool::classExists($class)) {
                $oldStyleClass = "\\Document_Tag_" . ucfirst($elementRaw["type"]);
                if(\Pimcore\Tool::classExists($oldStyleClass)) {
                    $class = $oldStyleClass;
                }
            }

            $element = new $class();
            $element->setName($elementRaw["name"]);
            $element->setDocumentId($this->model->getId());
            $element->setDataFromResource($elementRaw["data"]);

            $elements[$elementRaw["name"]] = $element;
            $this->model->setElement($elementRaw["name"], $element);
        }
        return $elements;
    }

    /**
     * Get available versions fot the object and return an array of them
     *
     * @return array
     */
    public function getVersions() {
        $versionIds = $this->db->fetchCol("SELECT id FROM versions WHERE cid = ? AND ctype='document' ORDER BY `id` DESC", $this->model->getId());

        $versions = array();
        foreach ($versionIds as $versionId) {
            $versions[] = Version::getById($versionId);
        }

        $this->model->setVersions($versions);

        return $versions;
    }
    
    
    /**
     * Get latest available version, using $force always returns a version no matter if it is the same as the published one
     * @param bool $force
     * @return array
     */
    public function getLatestVersion($force = false) {
        $versionData = $this->db->fetchRow("SELECT id,date FROM versions WHERE cid = ? AND ctype='document' ORDER BY `id` DESC LIMIT 1", $this->model->getId());
        
        if(($versionData["id"] && $versionData["date"] > $this->model->getModificationDate()) || $force) {
            $version = Version::getById($versionData["id"]);
            return $version;  
        }
        return;
    }
    

    /**
     * Delete the object from database
     *
     * @throws \Exception
     */
    public function delete() {
        try {
            parent::delete();
            $this->db->delete("documents_elements", $this->db->quoteInto("documentId = ?", $this->model->getId() ));
        }
        catch (\Exception $e) {
            throw $e;
        }
    }

}
