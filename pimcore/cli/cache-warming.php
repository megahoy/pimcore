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

use Pimcore\Cache\Tool\Warming as Warmer;

try {
    $opts = new \Zend_Console_Getopt(array(
        'types|t=s' => 'perform warming only for this types of elements (comma separated), valid arguments: document,asset,object (default: all types)',
        "documentTypes|dt=s" => "only for these types of documents (comma separated), valid arguments: page,snippet,folder,link (default: all types)",
        "assetTypes|at=s" => "only for these types of assets (comma separated), valid arguments: folder,image,text,audio,video,document,archive,unknown (default: all types)",
        "objectTypes|ot=s" => "only for these types of objects (comma separated), valid arguments: object,folder,variant (default: all types)",
        "classes|c=s" => "this is only for objects! filter by class (comma separated), valid arguments: class-names of your classes defined in pimcore",
        "maintenanceMode|m" => "enable maintenance mode during cache warming",
        'verbose|v' => 'show detailed information during the maintenance (for debug, ...)',
        'help|h' => 'display this help'
    ));
} catch (Exception $e) {
    echo $e->getMessage();
}


// display help message
if($opts->getOption("help")) {
    echo $opts->getUsageMessage();
    exit;
}

// enable maintenance mode if requested
if($opts->getOption("maintenanceMode")) {
    \Pimcore\Tool\Admin::activateMaintenanceMode("cache-warming-dummy-session-id");

    // set the timeout between each iteration to 0 if maintenance mode is on, because we don't have to care about the load on the server
    Warmer::setTimoutBetweenIteration(0);
}

if($opts->getOption("verbose")) {
    $writer = new \Zend_Log_Writer_Stream('php://output');
    $logger = new \Zend_Log($writer);
    \Logger::addLogger($logger);

    // set all priorities
    \Logger::setVerbosePriorities();
}

// get valid types (default all types)
$types = array("document","asset","object");
if($opts->getOption("types")) {
    $types = explode(",", $opts->getOption("types"));
}

if(in_array("document", $types)) {

    $docTypes = null;
    if($opts->getOption("documentTypes")) {
        $docTypes = explode(",", $opts->getOption("documentTypes"));
    }
    Warmer::documents($docTypes);
}

if(in_array("asset", $types)) {

    $assetTypes = null;
    if($opts->getOption("assetTypes")) {
        $assetTypes = explode(",", $opts->getOption("assetTypes"));
    }

    Warmer::assets($assetTypes);
}

if(in_array("object", $types)) {

    $objectTypes = null;
    if($opts->getOption("objectTypes")) {
        $objectTypes = explode(",", $opts->getOption("objectTypes"));
    }

    $classes = null;
    if($opts->getOption("classes")) {
        $classes = explode(",", $opts->getOption("classes"));
    }

    Warmer::objects($objectTypes, $classes);
}




// disable maintenance mode if requested
if($opts->getOption("maintenanceMode")) {
    \Pimcore\Tool\Admin::deactivateMaintenanceMode();
}
