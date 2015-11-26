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

use Pimcore\Model\User;

echo "\n";

function prompt_silent($prompt = "Enter new password:") {
  if (preg_match('/^win/i', PHP_OS)) {
    $vbscript = sys_get_temp_dir() . 'prompt_password.vbs';
    file_put_contents(
      $vbscript, 'wscript.echo(InputBox("'
      . addslashes($prompt)
      . '", "", "password here"))');
    $command = "cscript //nologo " . escapeshellarg($vbscript);
    $password = rtrim(shell_exec($command));
    unlink($vbscript);
    return $password;
  } else {
    $command = "/usr/bin/env bash -c 'echo OK'";
    if (rtrim(shell_exec($command)) !== 'OK') {
      trigger_error("Can't invoke bash");
      return;
    }
    $command = "/usr/bin/env bash -c 'read -s -p \""
      . addslashes($prompt)
      . "\" mypassword && echo \$mypassword'";
    $password = rtrim(shell_exec($command));
    echo "\n";
    return $password;
  }
}

if ($argc <= 1) { // if no arguments
    echo 'Usage: ' . $argv[0] . ' user ID or name. ';
    echo "\nExample: php reset-password.php myusername";
    echo "\n";
    exit;
}

$method = is_numeric($argv[1]) ? 'getById' : 'getByName';
/** @var User $user */
$user = User::$method($argv[1]);

if(!$user) {
    echo sprintf("User with name '%s' could not be found. Exiting.\n", $argv[1]);
    exit;
}

$plainPassword = false;
while (empty($plainPassword)) {
    $plainPassword = prompt_silent();
}

$password = Pimcore\Tool\Authentication::getPasswordHash($user->getName(), $plainPassword);
$user->setPassword($password);
$user->save();

echo sprintf("Password for user '%s' reset successfully.\n", $user->getName());
