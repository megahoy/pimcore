<?php
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @category   Pimcore
 * @package    Object|Class
 * @copyright  Copyright (c) 2009-2015 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */

namespace Pimcore\Model\Object\ClassDefinition\Data;

use Pimcore\Model;
use Pimcore\Tool; 

class Languagemultiselect extends Model\Object\ClassDefinition\Data\Multiselect {

    /**
     * Static type of this element
     *
     * @var string
     */
    public $fieldtype = "languagemultiselect";


    /**
     * @var bool
     */
    public $onlySystemLanguages = false;

    /**
     *
     */
    public function configureOptions () {

        $validLanguages = (array) Tool::getValidLanguages();
        $locales = Tool::getSupportedLocales();
        $options = array();

        foreach ($locales as $short => $translation) {

            if($this->getOnlySystemLanguages()) {
                if(!in_array($short, $validLanguages)) {
                    continue;
                }
            }

            $options[] = array(
                "key" => $translation,
                "value" => $short
            );
        }

        $this->setOptions($options);
    }

    /**
     * @return bool
     */
    public function getOnlySystemLanguages () {
        return $this->onlySystemLanguages;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setOnlySystemLanguages ($value) {
        $this->onlySystemLanguages = (bool) $value;
        return $this;
    }

    /**
     *
     */
    public function __wakeup () {
        $this->configureOptions();
    }
}
