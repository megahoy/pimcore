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

namespace Pimcore\Model\Document\Listing;

use Pimcore\Model;
use Pimcore\Model\Document;

class Dao extends Model\Listing\Dao\AbstractDao {

    /**
     * Loads a list of objects (all are an instance of Document) for the given parameters an return them
     *
     * @return array
     */
    public function load() {
        $documents = array();
        $documentsData = $this->db->fetchAll("SELECT id,type FROM documents" . $this->getCondition() . $this->getOrder() . $this->getOffsetLimit(), $this->model->getConditionVariables());

        foreach ($documentsData as $documentData) {
            if($documentData["type"]) {
                if($doc = Document::getById($documentData["id"])) {
                    $documents[] = $doc;
                }
            }
        }

        $this->model->setDocuments($documents);
        return $documents;
    }

    /**
     * Loads a list of document ids for the specicifies parameters, returns an array of ids
     *
     * @return array
     */
    public function loadIdList() {
        $documentIds = $this->db->fetchCol("SELECT id FROM documents" . $this->getCondition() . $this->getOrder() . $this->getOffsetLimit(), $this->model->getConditionVariables());
        return $documentIds;
    }

    public function loadIdPathList() {
        $documentIds = $this->db->fetchAll("SELECT id, CONCAT(path,`key`) as path FROM documents" . $this->getCondition() . $this->getOrder() . $this->getOffsetLimit(), $this->model->getConditionVariables());
        return $documentIds;
    }

    protected function getCondition() {
        if ($cond = $this->model->getCondition()) {
            if (Document::doHideUnpublished() && !$this->model->getUnpublished()) {
                return " WHERE (" . $cond . ") AND published = 1";
            }
            return " WHERE " . $cond . " ";
        }
    else if (Document::doHideUnpublished() && !$this->model->getUnpublished()) {
            return " WHERE published = 1";
        }
        return "";
    }

    public function getCount() {
        $amount = (int) $this->db->fetchOne("SELECT COUNT(*) as amount FROM documents" . $this->getCondition() . $this->getOffsetLimit(), $this->model->getConditionVariables());
        return $amount;
    }

    public function getTotalCount() {
        $amount = (int) $this->db->fetchOne("SELECT COUNT(*) as amount FROM documents" . $this->getCondition(), $this->model->getConditionVariables());
        return $amount;
    }
}
