<?php
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @category   Pimcore
 * @package    Schedule
 * @copyright  Copyright (c) 2009-2015 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */

namespace Pimcore\Model\Schedule\Task;

use Pimcore\Model;

class Listing extends Model\Listing\AbstractListing {

    /**
     * Contains the results of the list. They are all an instance of Schedule\Task
     *
     * @var array
     */
    public $tasks = array();

    /**
     * Tests if the given key is an valid order key to sort the results
     *
     * @todo remove the dummy-always-true rule
     * @return boolean
     */
    public function isValidOrderKey($key) {
        return true;
    }

    /**
     * @return array
     */
    public function getTasks() {
        return $this->tasks;
    }

    /**
     * @param array $tasks
     * @return void
     */
    public function setTasks($tasks) {
        $this->tasks = $tasks;
        return $this;
    }
}
