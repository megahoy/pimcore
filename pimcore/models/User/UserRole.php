<?php
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @category   Pimcore
 * @package    User
 * @copyright  Copyright (c) 2009-2015 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */

namespace Pimcore\Model\User;

use Pimcore\Model;

class UserRole extends AbstractUser {

    /**
     * @var array
     */
    public $permissions = array();

    /**
     * @var array
     */
    public $workspacesAsset = array();

    /**
     * @var array
     */
    public $workspacesObject = array();

    /**
     * @var array
     */
    public $workspacesDocument = array();

    /**
     * @var array
     */
    public $classes = array();

    /**
     * @var array
     */
    public $docTypes = array();

    /**
     *
     */
    public function update () {
        $this->getDao()->update();

        // save all workspaces
        $this->getDao()->emptyWorkspaces();

        foreach ($this->getWorkspacesAsset() as $workspace) {
            $workspace->save();
        }
        foreach ($this->getWorkspacesDocument() as $workspace) {
            $workspace->save();
        }
        foreach ($this->getWorkspacesObject() as $workspace) {
            $workspace->save();
        }
    }

    /**
     *
     */
    public function setAllAclToFalse() {
        $this->permissions = array();
        return $this;
    }

    /**
     * @param $permissionName
     * @param null $value
     * @return $this
     */
    public function setPermission($permissionName, $value = null) {

        if(!in_array($permissionName, $this->permissions) && $value) {
            $this->permissions[] = $permissionName;
        } else if (in_array($permissionName, $this->permissions) && !$value) {
            $position = array_search($permissionName, $this->permissions);
            array_splice($this->permissions, $position, 1);
        }
        return $this;
    }

    /**
     * @return array
     */
    public function getPermissions() {
        return $this->permissions;
    }

    /**
     * @param $permissionName
     * @return bool
     */
    public function getPermission($permissionName) {

        if(in_array($permissionName, $this->permissions)) {
            return true;
        }

        return false;
    }

    /**
     * Generates the permission list required for frontend display
     *
     * @return void
     */
    public function generatePermissionList() {
        $permissionInfo = null;

        $list = new Permission\Definition\Listing();
        $definitions = $list->load();

        foreach ($definitions as $definition) {
            $permissionInfo[$definition->getKey()] = $this->getPermission($definition->getKey());
        }

        return $permissionInfo;
    }

    /**
     * @param $permissions
     * @return $this
     */
    public function setPermissions($permissions)
    {
        if(is_string($permissions)) {
            $this->permissions = explode(",", $permissions);
        } else if (is_array($permissions)) {
            $this->permissions = $permissions;
        }
        return $this;
    }

    /**
     * @param $workspacesAsset
     * @return $this
     */
    public function setWorkspacesAsset($workspacesAsset)
    {
        $this->workspacesAsset = $workspacesAsset;
        return $this;
    }

    /**
     * @return array
     */
    public function getWorkspacesAsset()
    {
        return $this->workspacesAsset;
    }

    /**
     * @param $workspacesDocument
     * @return $this
     */
    public function setWorkspacesDocument($workspacesDocument)
    {
        $this->workspacesDocument = $workspacesDocument;
        return $this;
    }

    /**
     * @return array
     */
    public function getWorkspacesDocument()
    {
        return $this->workspacesDocument;
    }

    /**
     * @param $workspacesObject
     * @return $this
     */
    public function setWorkspacesObject($workspacesObject)
    {
        $this->workspacesObject = $workspacesObject;
        return $this;
    }

    /**
     * @return array
     */
    public function getWorkspacesObject()
    {
        return $this->workspacesObject;
    }

    /**
     * @param array $classes
     */
    public function setClasses($classes)
    {
        if (is_string($classes)) {
            if (strlen($classes)) {
                $classes = explode(",", $classes);
            }
        }

        if(empty($classes)) {
            $classes = array();
        }
        $this->classes = $classes;
    }

    /**
     * @return array
     */
    public function getClasses()
    {
        return $this->classes;
    }

    /**
     * @param array $docTypes
     */
    public function setDocTypes($docTypes)
    {
        if (is_string($docTypes)) {
            if (strlen($docTypes)) {
                $docTypes = explode(",", $docTypes);
            }
        }

        if(empty($docTypes)) {
            $docTypes = array();
        }

        $this->docTypes = $docTypes;
    }

    /**
     * @return array
     */
    public function getDocTypes()
    {
        return $this->docTypes;
    }
}
