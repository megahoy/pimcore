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

namespace Pimcore\Controller\Plugin;

class Less extends \Zend_Controller_Plugin_Abstract {

    /**
     * @var bool
     */
    protected $enabled = true;

    /**
     * @var
     */
    protected $conf;

    /**
     * @param \Zend_Controller_Request_Abstract $request
     * @return bool|void
     */
    public function routeStartup(\Zend_Controller_Request_Abstract $request) {

        $this->conf = \Pimcore\Config::getSystemConfig();

        if($request->getParam('disable_less_compiler') || $_COOKIE["disable_less_compiler"]){
            return $this->disable();
        }

        if (!$this->conf->outputfilters) {
            return $this->disable();
        }

        if (!$this->conf->outputfilters->less) {
            return $this->disable();
        }

    }

    /**
     * @return bool
     */
    public function disable() {
        $this->enabled = false;
        return true;
    }

    /**
     *
     */
    public function dispatchLoopShutdown() {

        if(!\Pimcore\Tool::isHtmlResponse($this->getResponse())) {
            return;
        }
        
        if ($this->enabled) {

            include_once("simple_html_dom.php");

            $body = $this->getResponse()->getBody();
            $body = \Pimcore\Tool\Less::processHtml($body);
            $this->getResponse()->setBody($body);
        }
    }
}

