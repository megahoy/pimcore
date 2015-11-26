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

use Pimcore\Model\Object;
use Pimcore\Model\Document;

// determines if we're in Pimcore\Console mode
$pimcoreConsole = (defined('PIMCORE_CONSOLE') && true === PIMCORE_CONSOLE);

$workingDirectory = getcwd();
chdir(__DIR__);
include_once("../config/startup.php");
chdir($workingDirectory);

// CLI \Zend_Controller_Front Setup, this is required to make it possible to make use of all rendering features
// this includes $this->action() in templates, ...
$front = \Zend_Controller_Front::getInstance();
Pimcore::initControllerFront($front);

$request = new \Zend_Controller_Request_Http();
$request->setModuleName(PIMCORE_FRONTEND_MODULE);
$request->setControllerName("default");
$request->setActionName("default");
$front->setRequest($request);
$front->setResponse(new \Zend_Controller_Response_Cli());

// generic pimcore setup
\Pimcore::setSystemRequirements();
\Pimcore::initAutoloader();
\Pimcore::initConfiguration();
\Pimcore::setupFramework();
\Pimcore::initLogger();
\Pimcore::initModules();
\Pimcore::initPlugins();

//Activate Inheritance for cli-scripts
\Pimcore::unsetAdminMode();
Document::setHideUnpublished(true);
Object\AbstractObject::setHideUnpublished(true);
Object\AbstractObject::setGetInheritedValues(true);
Object\Localizedfield::setGetFallbackValues(true);

// CLI has no memory/time limits
@ini_set('memory_limit', -1);
@ini_set('max_execution_time', -1);
@ini_set('max_input_time', -1);

// Error reporting is enabled in CLI
@ini_set("display_errors", "On");
@ini_set("display_startup_errors", "On");
error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT);

// Pimcore\Console handles maintenance mode through the AbstractCommand
if (!$pimcoreConsole) {
    // skip if maintenance mode is on and the flag is not set
    // we cannot use \Zend_Console_Getopt here because it doesn't allow to be called twice (unrecognized parameter, ...)
    if(\Pimcore\Tool\Admin::isInMaintenanceMode() && !in_array("--ignore-maintenance-mode", $_SERVER['argv'])) {
        die("in maintenance mode -> skip\nset the flag --ignore-maintenance-mode to force execution \n");
    }
}
