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

namespace Pimcore\Model\Asset;

use Pimcore\Model\Cache;
use Pimcore\Model;
use Pimcore\Tool;

class Document extends Model\Asset {

    /**
     * @var string
     */
    public $type = "document";

    protected function update() {

        $this->clearThumbnails();

        if($this->getDataChanged()) {
            $tmpFile = $this->getTemporaryFile(true);

            try {
                $pageCount = $this->readPageCount($tmpFile);
                if($pageCount !== null && $pageCount > 0) {
                    $this->setCustomSetting("document_page_count", $pageCount);
                }
            } catch (\Exception $e) {

            }

            unlink($tmpFile);
        }

        parent::update();
    }

    protected function readPageCount($path = null)  {
        $pageCount = null;
        if(!$path) {
            $path = $this->getFileSystemPath();
        }

        if(!\Pimcore\Document::isAvailable()) {
            \Logger::error("Couldn't create image-thumbnail of document " . $this->getFullPath() . " no document adapter is available");
            return null;
        }

        try {
            $converter = \Pimcore\Document::getInstance();
            $converter->load($path);

            // read from blob here, because in $this->update() (see above) $this->getFileSystemPath() contains the old data
            $pageCount = $converter->getPageCount();
            return $pageCount;
        } catch (\Exception $e) {
            \Logger::error($e);
        }

        return $pageCount;
    }

    public function getPageCount() {
        if(!$pageCount = $this->getCustomSetting("document_page_count")) {
            $pageCount = $this->readPageCount();
        }
        return $pageCount;
    }

    /**
     * @param $thumbnailName
     * @param int $page
     * @param bool $deferred $deferred deferred means that the image will be generated on-the-fly (details see below)
     * @return mixed|string
     */
    public function getImageThumbnail($thumbnailName, $page = 1, $deferred = false) {

        // just 4 testing
        //$this->clearThumbnails(true);

        if(!\Pimcore\Document::isAvailable()) {
            \Logger::error("Couldn't create image-thumbnail of document " . $this->getFullPath() . " no document adapter is available");
            return "/pimcore/static/img/filetype-not-supported.png";
        }

        $thumbnail = Image\Thumbnail\Config::getByAutoDetect($thumbnailName);
        $thumbnail->setName("document_" . $thumbnail->getName()."-".$page);

        try {
            if(!$deferred) {
                $converter = \Pimcore\Document::getInstance();
                $converter->load($this->getFileSystemPath());
                $path = PIMCORE_TEMPORARY_DIRECTORY . "/document-image-cache/document_" . $this->getId() . "__thumbnail_" .  $page . ".png";
                if(!is_dir(dirname($path))) {
                    \Pimcore\File::mkdir(dirname($path));
                }

                $lockKey = "document-thumbnail-" . $this->getId() . "-" . $page;

                if(!is_file($path) && !Model\Tool\Lock::isLocked($lockKey)) {
                    Model\Tool\Lock::lock($lockKey);
                    $converter->saveImage($path, $page);
                    Model\Tool\Lock::release($lockKey);
                } else if(Model\Tool\Lock::isLocked($lockKey)) {
                    return "/pimcore/static/img/please-wait.png";
                }
            }

            if($thumbnail) {
                $path = Image\Thumbnail\Processor::process($this, $thumbnail, $path, $deferred);
            }

            return preg_replace("@^" . preg_quote(PIMCORE_DOCUMENT_ROOT) . "@", "", $path);
        } catch (\Exception $e) {
            \Logger::error("Couldn't create image-thumbnail of document " . $this->getFullPath());
            \Logger::error($e);
        }

        return "/pimcore/static/img/filetype-not-supported.png";
    }

    public function getText($page = null) {
        if(\Pimcore\Document::isAvailable() && \Pimcore\Document::isFileTypeSupported($this->getFilename())) {
            $cacheKey = "asset_document_text_" . $this->getId() . "_" . ($page ? $page : "all");
            if(!$text = Cache::load($cacheKey)) {
                $document = \Pimcore\Document::getInstance();
                $text = $document->getText($page, $this->getFileSystemPath());
                Cache::save($text, $cacheKey, $this->getCacheTags(), null, 99, true); // force cache write
            }
            return $text;
        } else {
            \Logger::error("Couldn't get text out of document " . $this->getFullPath() . " no document adapter is available");
        }

        return null;
    }

    /**
     * @return void
     */
    public function clearThumbnails($force = false) {

        if($this->_dataChanged || $force) {
            // video thumbnails and image previews
            $files = glob(PIMCORE_TEMPORARY_DIRECTORY . "/document-image-cache/document_" . $this->getId() . "__*");
            if(is_array($files)) {
                foreach ($files as $file) {
                    unlink($file);
                }
            }

            recursiveDelete($this->getImageThumbnailSavePath());
        }
    }
}
