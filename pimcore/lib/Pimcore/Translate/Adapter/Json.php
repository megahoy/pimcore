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

namespace Pimcore\Translate\Adapter;

class Json extends \Zend_Translate_Adapter
{
    private $_data    = array();

    /**
     * Load translation data
     *
     * @param  string|array  $filename  Filename and full path to the translation source
     * @param  string        $locale    Locale/Language to add data for, identical with locale identifier,
     *                                  see Zend_Locale for more information
     * @param  array         $option    OPTIONAL Options to use
     * @return array
     */
    protected function _loadTranslationData($filename, $locale, array $options = array())
    {
        $this->_data = [];
        $options     = $options + $this->_options;


        if(!isset($this->_data[$locale])) {
            $this->_data[$locale] = [];
        }

        $rawTranslations = json_decode(file_get_contents($filename), true);
        foreach ($rawTranslations as $entry) {
            if(!isset($translations[$entry["term"]])) {
                $this->_data[$locale][$entry["term"]] = $entry["definition"];
            }
        }

        return $this->_data;
    }

    /**
     * returns the adapters name
     *
     * @return string
     */
    public function toString()
    {
        return "Json";
    }
}
