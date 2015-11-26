<?php 
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @category   Pimcore
 * @package    Asset
 * @copyright  Copyright (c) 2009-2015 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */

namespace Pimcore\Model\Asset\WebDAV;

use Sabre\DAV;
use Pimcore\Tool\Admin as AdminTool;
use Pimcore\Model\Asset;

class File extends DAV\File {

    /**
     * @var Asset
     */
    private $asset;

    /**
     * @param $asset
     */
    function __construct($asset) {
        $this->asset = $asset;
    }

    /**
     * @return string
     */
    function getName() {
        return $this->asset->getFilename();
    }

    /**
     * @param string $name
     * @return $this|void
     * @throws DAV\Exception\Forbidden
     * @throws \Exception
     */
    function setName($name) {

        if($this->asset->isAllowed("rename")) {
            $user = AdminTool::getCurrentUser();
            $this->asset->setUserModification($user->getId());

            $this->asset->setFilename(\Pimcore\File::getValidFilename($name));
            $this->asset->save();
        } else {
            throw new DAV\Exception\Forbidden();
        }

        return $this;
    }

    /**
     * @throws DAV\Exception\Forbidden
     * @throws \Exception
     */
    function delete() {

        if($this->asset->isAllowed("delete")) {
            Asset\Service::loadAllFields($this->asset);
            $this->asset->delete();

            // add the asset to the delete history, this is used so come over problems with programs like photoshop (delete, create instead of replace => move)
            // for details see Asset\WebDAV\Tree::move()
            $log = Asset\WebDAV\Service::getDeleteLog();

            $this->asset->_fulldump = true;
            $log[$this->asset->getFullpath()] = array(
                "id" => $this->asset->getId(),
                "timestamp" => time(),
                "data" => \Pimcore\Tool\Serialize::serialize($this->asset)
            );

            unset($this->asset->_fulldump);

            Asset\WebDAV\Service::saveDeleteLog($log);
        } else {
            throw new DAV\Exception\Forbidden();
        }
    }

    /**
     * @return integer
     */
    function getLastModified() {
        return $this->asset->getModificationDate();
    }

    /**
     * @param resource $data
     * @throws DAV\Exception\Forbidden
     * @throws \Exception
     */
    function put($data) {

        if($this->asset->isAllowed("publish")) {
            // read from resource -> default for SabreDAV
            $tmpFile = PIMCORE_SYSTEM_TEMP_DIRECTORY . "/asset-dav-tmp-file-" . uniqid();
            file_put_contents($tmpFile, $data);
            $file = fopen($tmpFile, "r+");

            $user = AdminTool::getCurrentUser();
            $this->asset->setUserModification($user->getId());

            $this->asset->setStream($file);
            $this->asset->save();

            fclose($file);
            unlink($tmpFile);
        } else {
            throw new DAV\Exception\Forbidden();
        }
    }

    /**
     * @return mixed|void
     * @throws DAV\Exception\Forbidden
     */
    function get() {
        if($this->asset->isAllowed("view")) {
            return fopen($this->asset->getFileSystemPath(), "r");
        } else {
            throw new DAV\Exception\Forbidden();
        }
    }

    /**
     * Get a hash of the file for an unique identifier
     *
     * @return string
     */
    function getETag() {
        return md5_file($this->asset->getFileSystemPath());
    }

    /**
     * Returns the mimetype of the asset
     *
     * @return string
     */
    function getContentType() {
        return $this->asset->getMimetype();
    }

    /**
     * Get size of file in bytes
     *
     * @return integer
     */
    function getSize() {
        return filesize($this->asset->getFileSystemPath());
    }

}
