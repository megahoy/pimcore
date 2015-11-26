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

class Video {

    /**
     * @param null $adapter
     * @return bool|null|Video\Adapter
     * @throws \Exception
     */
    public static function getInstance ($adapter = null) {
        try {
            if($adapter) {
                $adapterClass = "\\Pimcore\\Video\\Adapter\\" . $adapter;
                if(Tool::classExists($adapterClass)) {
                    return new $adapterClass();
                } else {
                    throw new \Exception("Video-transcode adapter `" . $adapter . "´ does not exist.");
                }
            } else {
                if($adapter = self::getDefaultAdapter()) {
                    return $adapter;
                }
            }
        } catch (\Exception $e) {
            \Logger::crit("Unable to load video adapter: " . $e->getMessage());
            throw $e;
        }

        return null;
    }

    /**
     * @return bool
     */
    public static function isAvailable () {
        if(self::getDefaultAdapter()) {
            return true;
        }
        return false;
    }

    /**
     * @return bool
     */
    public static function getDefaultAdapter () {

        $adapters = array("Ffmpeg");

        foreach ($adapters as $adapter) {
            $adapterClass = "\\Pimcore\\Video\\Adapter\\" . $adapter;
            if(Tool::classExists($adapterClass)) {
                try {
                    $adapter = new $adapterClass();
                    if($adapter->isAvailable()) {
                        return $adapter;
                    }
                } catch (\Exception $e) {
                    \Logger::warning($e);
                }
            }
        }

        return null;
    }
}
