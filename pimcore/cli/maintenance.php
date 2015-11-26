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

use Pimcore\Log;
use Pimcore\Model\Schedule;

try {
    $optsConfig = array(
        'job|j=s' => 'call just a specific job(s), use "," (comma) to execute more than one job (valid options: scheduledtasks, cleanupcache, logmaintenance, sanitycheck, cleanuplogfiles, versioncleanup, versioncompress, redirectcleanup, cleanupbrokenviews, usagestatistics, downloadmaxminddb, tmpstorecleanup and plugin classes if you want to call a plugin maintenance)',
        'manager|m=s' => 'force a specific manager (valid options: procedural, daemon)',
        'ignore-maintenance-mode' => 'forces the script execution even when the maintenance mode is activated',
        'verbose|v' => 'show detailed information during the maintenance (for debug, ...)',
        "force|f" => "run the jobs, regardless if they're locked or not",
        'help|h' => 'display this help'
    );

    // parse existing valid arguments => needed to do not add them twice => see below (dynamic add)
    $existingParams = array();
    foreach ($optsConfig as $key => $value) {
        foreach(explode("|",$key) as $v) {
            $existingParams[] = $v;
        }
    }

    // dynamically add non recognized options to avoid error messages
    $arguments = $_SERVER['argv'];
    array_shift($arguments);
    foreach ($arguments as $arg) {
        $arg = preg_match("/\-\-([a-zA-Z0-9]+)?(=| )?/", $arg, $matches);
        if(array_key_exists(1, $matches) && !in_array($matches[1], $existingParams)) {
            $optsConfig[$matches[1]] = "custom parameter";
        }
    }

    $opts = new \Zend_Console_Getopt($optsConfig);

} catch (\Exception $e) {
    echo $e->getMessage();
}

try {
    $opts->parse();
} catch (\Zend_Console_Getopt_Exception $e) {
    echo $e->getMessage();
}


// display help message
if($opts->getOption("help")) {
    echo $opts->getUsageMessage();
    exit;
}

if($opts->getOption("verbose")) {
    $writer = new \Zend_Log_Writer_Stream('php://stdout');
    $logger = new \Zend_Log($writer);
    \Logger::addLogger($logger);

    // set all priorities
    \Logger::setVerbosePriorities();
}

$forceType = null;
if($opts->getOption("manager")) {
    $forceType = $opts->getOption("manager");
}

$validJobs = array();
if($opts->getOption("job")) {
    $validJobs = explode(",",$opts->getOption("job"));
}

// create manager
$manager = Schedule\Manager\Factory::getManager("maintenance.pid", $forceType);
$manager->setValidJobs($validJobs);
$manager->setForce((bool) $opts->getOption("force"));

// register scheduled tasks
$manager->registerJob(new Schedule\Maintenance\Job("scheduledtasks", new Schedule\Task\Executor(), "execute"));
$manager->registerJob(new Schedule\Maintenance\Job("logmaintenance", new \Pimcore\Log\Maintenance(), "mail"));
$manager->registerJob(new Schedule\Maintenance\Job("cleanuplogfiles", new \Pimcore\Log\Maintenance(), "cleanupLogFiles"));
$manager->registerJob(new Schedule\Maintenance\Job("httperrorlog", new \Pimcore\Log\Maintenance(), "httpErrorLogCleanup"));
$manager->registerJob(new Schedule\Maintenance\Job("usagestatistics", new \Pimcore\Log\Maintenance(), "usageStatistics"));
$manager->registerJob(new Schedule\Maintenance\Job("checkErrorLogsDb", new \Pimcore\Log\Maintenance(), "checkErrorLogsDb"));
$manager->registerJob(new Schedule\Maintenance\Job("archiveLogEntries", new \Pimcore\Log\Maintenance(), "archiveLogEntries"));
$manager->registerJob(new Schedule\Maintenance\Job("sanitycheck", "\\Pimcore\\Model\\Element\\Service", "runSanityCheck"));
$manager->registerJob(new Schedule\Maintenance\Job("versioncleanup", new \Pimcore\Model\Version(), "maintenanceCleanUp"));
$manager->registerJob(new Schedule\Maintenance\Job("versioncompress", new \Pimcore\Model\Version(), "maintenanceCompress"));
$manager->registerJob(new Schedule\Maintenance\Job("redirectcleanup", "\\Pimcore\\Model\\Redirect", "maintenanceCleanUp"));
$manager->registerJob(new Schedule\Maintenance\Job("cleanupbrokenviews", "\\Pimcore\\Db", "cleanupBrokenViews"));
$manager->registerJob(new Schedule\Maintenance\Job("downloadmaxminddb", "\\Pimcore\\Update", "updateMaxmindDb"));
$manager->registerJob(new Schedule\Maintenance\Job("cleanupcache", "\\Pimcore\\Model\\Cache", "maintenance"));
$manager->registerJob(new Schedule\Maintenance\Job("tmpstorecleanup", "\\Pimcore\\Model\\Tool\\TmpStore", "cleanup"));
$manager->registerJob(new Schedule\Maintenance\Job("imageoptimize", "\\Pimcore\\Model\\Asset\\Image\\Thumbnail\\Processor", "processOptimizeQueue"));


\Pimcore::getEventManager()->trigger("system.maintenance", $manager);

$manager->run();

\Logger::info("All maintenance-jobs finished!");
