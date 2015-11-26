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

namespace Pimcore\Model\Object\ClassDefinition\CustomLayout;

use Pimcore\Model;

class Listing extends Model\Listing\AbstractListing {

    /**
     * Contains the results of the list. They are all an instance of Object\ClassDefinition\CustomLayout\Listing
     *
     * @var array
     */
    public $layoutDefinitions;

    /**
     * @param $key
     * @return bool
     */
    public function isValidOrderKey($key) {
        return true;
    }

    /**
     * @param array $layoutDefinitions
     */
    public function setLayoutDefinitions($layoutDefinitions)
    {
        $this->layoutDefinitions = $layoutDefinitions;
    }

    /**
     * @return array
     */
    public function getLayoutDefinitions()
    {
        return $this->layoutDefinitions;
    }
}
