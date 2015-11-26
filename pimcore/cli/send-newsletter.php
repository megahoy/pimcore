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

use Pimcore\Model;

$newsletter = Model\Tool\Newsletter\Config::getByName($argv[1]);
if($newsletter) {

    $pidFile = $newsletter->getPidFile();

    if(file_exists($pidFile)) {
        \Logger::alert("Cannot send newsletters because there's already one active sending process");
        exit;
    }

    $elementsPerLoop = 10;
    $objectList = "\\Pimcore\\Model\\Object\\" . ucfirst($newsletter->getClass()) . "\\Listing";
    $list = new $objectList();

    $conditions = array("(newsletterActive = 1 AND newsletterConfirmed = 1)");
    if($newsletter->getObjectFilterSQL()) {
        $conditions[] = $newsletter->getObjectFilterSQL();
    }
    if($newsletter->getPersonas()) {
        $class = Model\Object\ClassDefinition::getByName($newsletter->getClass());
        if($class && $class->getFieldDefinition("persona")) {
            $personas = array();
            $p = explode(",", $newsletter->getPersonas());

            if($class->getFieldDefinition("persona") instanceof \Pimcore\Model\Object\ClassDefinition\Data\Persona) {
                foreach ($p as $value) {
                    if(!empty($value)) {
                        $personas[] = $list->quote($value);
                    }
                }
                $conditions[] = "persona IN (" . implode(",", $personas) . ")";
            } else if ($class->getFieldDefinition("persona") instanceof \Pimcore\Model\Object\ClassDefinition\Data\Personamultiselect) {
                $personasCondition = array();
                foreach ($p as $value) {
                    $personasCondition[] = "persona LIKE " . $list->quote("%," . $value .  ",%");

                }
                $conditions[] = "(" . implode(" OR ", $personasCondition). ")";
            }
        }
    }

    $list->setCondition(implode(" AND ", $conditions));
    $list->setOrderKey("email");
    $list->setOrder("ASC");

    $elementsTotal = $list->getTotalCount();
    $count = 0;

    $pidContents = array(
        "start" => time(),
        "lastUpdate" => time(),
        "newsletter" => $newsletter->getName(),
        "total" => $elementsTotal,
        "current" => $count
    );

    writePid($pidFile, $pidContents);

    for($i=0; $i<(ceil($elementsTotal/$elementsPerLoop)); $i++) {
        $list->setLimit($elementsPerLoop);
        $list->setOffset($i*$elementsPerLoop);

        $objects = $list->load();

        foreach ($objects as $object) {

            try {
                $count++;
                \Logger::info("Sending newsletter " . $count . " / " . $elementsTotal. " [" . $newsletter->getName() . "]");

                \Pimcore\Tool\Newsletter::sendMail($newsletter, $object, null, $argv[2]);

                $note = new Model\Element\Note();
                $note->setElement($object);
                $note->setDate(time());
                $note->setType("newsletter");
                $note->setTitle("sent newsletter: '" . $newsletter->getName() . "'");
                $note->setUser(0);
                $note->setData(array());
                $note->save();

                \Logger::info("Sent newsletter to: " . obfuscateEmail($object->getEmail()) . " [" . $newsletter->getName() . "]");
            } catch (\Exception $e) {
                \Logger::err($e);
            }
        }

        // check if pid exists
        if(!file_exists($pidFile)) {
            \Logger::alert("Newsletter PID not found, cancel sending process");
            exit;
        }

        // update pid
        $pidContents["lastUpdate"] = time();
        $pidContents["current"] = $count;
        writePid($pidFile, $pidContents);

        \Pimcore::collectGarbage();
    }

    // remove pid
    @unlink($pidFile);

} else {
    \Logger::emerg("Newsletter '" . $argv[1] . "' doesn't exist");
}



function obfuscateEmail($email) {
    $email = substr_replace($email, ".xxx", strrpos($email, "."));
    return $email;
}

function writePid ($file, $content) {
    \Pimcore\File::put($file, serialize($content));
}
