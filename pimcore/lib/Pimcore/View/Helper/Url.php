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

namespace Pimcore\View\Helper;

use Pimcore\Config; 
use Pimcore\Model\Site;
use Pimcore\Model\Staticroute;
use Pimcore\Tool;

class Url extends \Zend_View_Helper_Url {

    /**
     * @param array $urlOptions
     * @param null $name
     * @param bool $reset
     * @param bool $encode
     * @return string|void
     * @throws \Exception
     */
    public function url(array $urlOptions = array(), $name = null, $reset = false, $encode = true)
    {
        if(!$urlOptions) {
            $urlOptions = array();
        }

        // when using $name = false we don't use the default route (happens when $name = null / ZF default behavior)
        // but just the query string generation using the given parameters
        // eg. $this->url(["foo" => "bar"], false) => /?foo=bar
        if($name === null) {
            if(Staticroute::getCurrentRoute() instanceof Staticroute) {
                $name = Staticroute::getCurrentRoute()->getName();
            }
        }

        $siteId = null;
        if(Site::isSiteRequest()) {
            $siteId = Site::getCurrentSite()->getId();
        }

        // check for a site in the options, if valid remove it from the options
        $hostname = null;
        if(isset($urlOptions["site"])) {
            $config = Config::getSystemConfig();
            $site = $urlOptions["site"];
            if(!empty($site)) {
                try {
                    $site = Site::getBy($site);
                    unset($urlOptions["site"]);
                    $hostname = $site->getMainDomain();
                    $siteId = $site->getId();
                } catch (\Exception $e) {
                    \Logger::warn("passed site doesn't exists");
                    \Logger::warn($e);
                }
            } else if ($config->general->domain) {
                $hostname = $config->general->domain;
            }
        }

        if($name && $route = Staticroute::getByName($name, $siteId)) {

            // assemble the route / url in Staticroute::assemble()
            $url = $route->assemble($urlOptions, $reset, $encode);

            // if there's a site, prepend the host to the generated URL
            if($hostname && !preg_match("/^http/i", $url)) {
                $url = "//" . $hostname . $url;
            }

            if(Config::getSystemConfig()->documents->allowcapitals == 'no'){
                $urlParts = parse_url($url);
                $url = str_replace($urlParts["path"], strtolower($urlParts["path"]), $url);
            }
            return $url;
        }


        // this is to add support for arrays as values for the default \Zend_View_Helper_Url
        $unset = array(); 
        foreach ($urlOptions as $optionName => $optionValues) {
            if (is_array($optionValues)) {
                foreach ($optionValues as $key => $value) {
                    $urlOptions[$optionName . "[" . $key . "]"] = $value;
                }
                $unset[] = $optionName;
            }
        }
        foreach ($unset as $optionName) {
            unset($urlOptions[$optionName]);
        }

        
        try {
            return parent::url($urlOptions, $name, $reset, $encode);
        } catch (\Exception $e) {
            if(Tool::isFrontentRequestByAdmin()) {
                // routes can be site specific, so in editmode it's possible that we don't get
                // the right route (sites are not registered in editmode), so we cannot throw exceptions there
                return "ERROR_IN_YOUR_URL_CONFIGURATION:~ROUTE--" . $name . "--DOES_NOT_EXIST";
            }

            throw new \Exception("Route '".$name."' for building the URL not found");
        }
    }    
}
