<?php
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2015 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */

chdir(__DIR__);

include_once("startup.php");

use Pimcore\Model\Search;

// clear all data
$db = \Pimcore\Db::get();
$db->query("TRUNCATE `search_backend_data`;");

$elementsPerLoop = 100;
$types = array("asset","document","object");

foreach ($types as $type) {
    $listClassName = "\\Pimcore\\Model\\" . ucfirst($type) . "\\Listing";
    $list = new $listClassName();
    if(method_exists($list, "setUnpublished")) {
        $list->setUnpublished(true);
    }

    $elementsTotal = $list->getTotalCount();

    for($i=0; $i<(ceil($elementsTotal/$elementsPerLoop)); $i++) {
        $list->setLimit($elementsPerLoop);
        $list->setOffset($i*$elementsPerLoop);

        echo "Processing " .$type . ": " . ($list->getOffset()+$elementsPerLoop) . "/" . $elementsTotal . "\n";

        $elements = $list->load();
        foreach ($elements as $element) {
            try {
                $searchEntry = Search\Backend\Data::getForElement($element);
                if($searchEntry instanceof Search\Backend\Data and $searchEntry->getId() instanceof Search\Backend\Data_Id ) {
                    $searchEntry->setDataFromElement($element);
                } else {
                    $searchEntry = new Search\Backend\Data($element);
                }

                $searchEntry->save();
            } catch (Exception $e) {
                \Logger::err($e);
            }
        }
        \Pimcore::collectGarbage();
    }
}

$db->query("OPTIMIZE TABLE search_backend_data;");

