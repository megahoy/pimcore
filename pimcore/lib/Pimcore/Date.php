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

namespace Pimcore;

class Date extends \Zend_Date {

    /**
     *
     */
    const MYSQL_DATETIME = 'YYYY-MM-dd HH:mm:ss';

    /**
     *
     */
    const MYSQL_DATE     = 'YYYY-MM-dd';

    /**
     * @throws \Zend_Date_Exception
     */
    public function __wakeup () {
        $this->setLocale(null);
    }
}
