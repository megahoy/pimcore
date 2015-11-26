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

namespace Pimcore\Controller\Plugin\Frontend;

use Pimcore\Version;
use Pimcore\ExtensionManager;
use Pimcore\Config;
use Pimcore\Model\Document;

class Editmode extends \Zend_Controller_Plugin_Abstract {

    /**
     * @var \Pimcore\Controller\Action\Frontend
     */
    protected $controller;

    /**
     * @param \Pimcore\Controller\Action\Frontend $controller
     */
    public function __construct(\Pimcore\Controller\Action\Frontend $controller) {
        $this->controller = $controller;
    }

    /**
     * @param \Zend_Controller_Request_Abstract $request
     */
    public function postDispatch(\Zend_Controller_Request_Abstract $request) {
        $conf = Config::getSystemConfig();

        // add scripts to editmode

        if (\Pimcore\Tool\Admin::isExtJS6()) {
            $editmodeLibraries = array(
                "/pimcore/static6/js/pimcore/namespace.js",
                "/pimcore/static6/js/lib/prototype-light.js",
                "/pimcore/static6/js/lib/jquery.min.js",
                "/pimcore/static6/js/lib/ext/ext-all.js",
                "/pimcore/static6/js/lib/ckeditor/ckeditor.js"
            );

            $editmodeScripts = array(
                "/pimcore/static6/js/pimcore/functions.js",
                "/pimcore/static6/js/pimcore/element/tag/imagehotspotmarkereditor.js",
                "/pimcore/static6/js/pimcore/element/tag/imagecropper.js",
                "/pimcore/static6/js/pimcore/document/edit/helper.js",
                "/pimcore/static6/js/pimcore/document/edit/dnd.js",
                "/pimcore/static6/js/pimcore/document/tag.js",
                "/pimcore/static6/js/pimcore/document/tags/block.js",
                "/pimcore/static6/js/pimcore/document/tags/date.js",
                "/pimcore/static6/js/pimcore/document/tags/href.js",
                "/pimcore/static6/js/pimcore/document/tags/multihref.js",
                "/pimcore/static6/js/pimcore/document/tags/checkbox.js",
                "/pimcore/static6/js/pimcore/document/tags/image.js",
                "/pimcore/static6/js/pimcore/document/tags/input.js",
                "/pimcore/static6/js/pimcore/document/tags/link.js",
                "/pimcore/static6/js/pimcore/document/tags/select.js",
                "/pimcore/static6/js/pimcore/document/tags/snippet.js",
                "/pimcore/static6/js/pimcore/document/tags/textarea.js",
                "/pimcore/static6/js/pimcore/document/tags/numeric.js",
                "/pimcore/static6/js/pimcore/document/tags/wysiwyg.js",
                "/pimcore/static6/js/pimcore/document/tags/renderlet.js",
                "/pimcore/static6/js/pimcore/document/tags/table.js",
                "/pimcore/static6/js/pimcore/document/tags/video.js",
                "/pimcore/static6/js/pimcore/document/tags/multiselect.js",
                "/pimcore/static6/js/pimcore/document/tags/areablock.js",
                "/pimcore/static6/js/pimcore/document/tags/area.js",
                "/pimcore/static6/js/pimcore/document/tags/pdf.js",
                "/pimcore/static6/js/pimcore/document/edit/helper.js"
            );

            $editmodeStylesheets = array(
                "/pimcore/static6/css/icons.css",
                "/pimcore/static6/css/editmode.css?_dc=" . time()
            );

        } else {
            $editmodeLibraries = array(
                "/pimcore/static/js/pimcore/namespace.js",

                "/pimcore/static/js/lib/prototype-light.js",
                "/pimcore/static/js/lib/jquery.min.js",
                "/pimcore/static/js/lib/ext/adapter/jquery/ext-jquery-adapter-debug.js",

                "/pimcore/static/js/lib/ext/ext-all-debug.js",
                "/pimcore/static/js/lib/ext-plugins/ux/Spinner.js",
                "/pimcore/static/js/lib/ext-plugins/ux/SpinnerField.js",
                "/pimcore/static/js/lib/ext-plugins/ux/MultiSelect.js",
                "/pimcore/static/js/lib/ext-plugins/GridRowOrder/roworder.js",
                "/pimcore/static/js/lib/ckeditor/ckeditor.js",
                "/pimcore/static/js/pimcore/libfixes.js"
            );

            $editmodeScripts = array(
                "/pimcore/static/js/pimcore/functions.js",
                "/pimcore/static/js/pimcore/element/tag/imagehotspotmarkereditor.js",
                "/pimcore/static/js/pimcore/element/tag/imagecropper.js",
                "/pimcore/static/js/pimcore/document/edit/helper.js",
                "/pimcore/static/js/pimcore/document/edit/dnd.js",
                "/pimcore/static/js/pimcore/document/tag.js",
                "/pimcore/static/js/pimcore/document/tags/block.js",
                "/pimcore/static/js/pimcore/document/tags/date.js",
                "/pimcore/static/js/pimcore/document/tags/href.js",
                "/pimcore/static/js/pimcore/document/tags/multihref.js",
                "/pimcore/static/js/pimcore/document/tags/checkbox.js",
                "/pimcore/static/js/pimcore/document/tags/image.js",
                "/pimcore/static/js/pimcore/document/tags/input.js",
                "/pimcore/static/js/pimcore/document/tags/link.js",
                "/pimcore/static/js/pimcore/document/tags/select.js",
                "/pimcore/static/js/pimcore/document/tags/snippet.js",
                "/pimcore/static/js/pimcore/document/tags/textarea.js",
                "/pimcore/static/js/pimcore/document/tags/numeric.js",
                "/pimcore/static/js/pimcore/document/tags/wysiwyg.js",
                "/pimcore/static/js/pimcore/document/tags/renderlet.js",
                "/pimcore/static/js/pimcore/document/tags/table.js",
                "/pimcore/static/js/pimcore/document/tags/video.js",
                "/pimcore/static/js/pimcore/document/tags/multiselect.js",
                "/pimcore/static/js/pimcore/document/tags/areablock.js",
                "/pimcore/static/js/pimcore/document/tags/area.js",
                "/pimcore/static/js/pimcore/document/tags/pdf.js",
                "/pimcore/static/js/pimcore/document/edit/helper.js"
            );

            $editmodeStylesheets = array(
                /*"/pimcore/static/js/lib/ext/resources/css/ext-all.css",
                "/pimcore/static/js/lib/ext/resources/css/xtheme-gray.css",
                "/pimcore/static/js/lib/ext-plugins/ux/css/Spinner.css",
                "/pimcore/static/js/lib/ext-plugins/ux/css/MultiSelect.css",
                "/pimcore/static/css/ext-admin-overwrite.css",*/
                "/pimcore/static/css/icons.css",
                "/pimcore/static/css/editmode.css?asd=" . time(),
            );

        }

        //add plugin editmode JS and CSS
        try {
            $pluginConfigs = ExtensionManager::getPluginConfigs();
            $jsPaths = array();
            $cssPaths = array();

            if (!empty($pluginConfigs)) {
                //registering plugins
                foreach ($pluginConfigs as $p) {

                    $pluginJsPaths = array();
                    if(array_key_exists("pluginDocumentEditmodeJsPaths", $p['plugin'])
                        && is_array($p['plugin']['pluginDocumentEditmodeJsPaths'])
                        && isset($p['plugin']['pluginDocumentEditmodeJsPaths']['path'])) {
                        if (is_array($p['plugin']['pluginDocumentEditmodeJsPaths']['path'])) {
                            $pluginJsPaths = $p['plugin']['pluginDocumentEditmodeJsPaths']['path'];
                        }
                        else if ($p['plugin']['pluginDocumentEditmodeJsPaths']['path'] != null) {
                            $pluginJsPaths[] = $p['plugin']['pluginDocumentEditmodeJsPaths']['path'];
                        }
                    }

                    //manipulate path for frontend
                    if (is_array($pluginJsPaths) and count($pluginJsPaths) > 0) {
                        for ($i = 0; $i < count($pluginJsPaths); $i++) {
                            if (is_file(PIMCORE_PLUGINS_PATH . $pluginJsPaths[$i])) {
                                $jsPaths[] = "/plugins" . $pluginJsPaths[$i];
                            }
                        }
                    }


                    $pluginCssPaths = array();
                    if(array_key_exists("pluginDocumentEditmodeCssPaths", $p['plugin'])
                        && is_array($p['plugin']['pluginDocumentEditmodeCssPaths'])
                        && isset($p['plugin']['pluginDocumentEditmodeCssPaths']['path'])) {
                        if (is_array($p['plugin']['pluginDocumentEditmodeCssPaths']['path'])) {
                            $pluginCssPaths = $p['plugin']['pluginDocumentEditmodeCssPaths']['path'];
                        }
                        else if ($p['plugin']['pluginDocumentEditmodeCssPaths']['path'] != null) {
                            $pluginCssPaths[] = $p['plugin']['pluginDocumentEditmodeCssPaths']['path'];
                        }
                    }
                    //manipulate path for frontend
                    if (is_array($pluginCssPaths) and count($pluginCssPaths) > 0) {
                        for ($i = 0; $i < count($pluginCssPaths); $i++) {
                            if (is_file(PIMCORE_PLUGINS_PATH . $pluginCssPaths[$i])) {
                                $cssPaths[] = "/plugins" . $pluginCssPaths[$i];
                            }
                        }
                    }

                }
            }

            $editmodeScripts=array_merge($editmodeScripts,$jsPaths);
            $editmodeStylesheets=array_merge($editmodeStylesheets,$cssPaths);

        }
        catch (\Exception $e) {
            \Logger::alert("there is a problem with the plugin configuration");
            \Logger::alert($e);
        }

        $editmodeHeadHtml = "\n\n\n<!-- pimcore editmode -->\n";

        // include stylesheets
        foreach ($editmodeStylesheets as $sheet) {
            $editmodeHeadHtml .= '<link rel="stylesheet" type="text/css" href="' . $sheet . '?_dc=' . Version::$revision . '" />';
            $editmodeHeadHtml .= "\n";
        }

        $editmodeHeadHtml .= "\n\n";

        $editmodeHeadHtml .= '<script type="text/javascript">var jQueryPreviouslyLoaded = (typeof jQuery == "undefined") ? false : true;</script>' . "\n";

        // include script libraries
        foreach ($editmodeLibraries as $script) {
            $editmodeHeadHtml .= '<script type="text/javascript" src="' . $script . '?_dc=' . Version::$revision . '"></script>';
            $editmodeHeadHtml .= "\n";
        }

        // combine the pimcore scripts in non-devmode
        if($conf->general->devmode) {
            foreach ($editmodeScripts as $script) {
                $editmodeHeadHtml .= '<script type="text/javascript" src="' . $script . '?_dc=' . Version::$revision . '"></script>';
                $editmodeHeadHtml .= "\n";
            }
        }
        else {
            $scriptContents = "";
            foreach ($editmodeScripts as $scriptUrl) {
                $scriptContents .= file_get_contents(PIMCORE_DOCUMENT_ROOT.$scriptUrl) . "\n\n\n";
            }
            $editmodeHeadHtml .= '<script type="text/javascript" src="' . \Pimcore\Tool\Admin::getMinimizedScriptPath($scriptContents) . '?_dc=' . Version::$revision . '"></script>'."\n";
        }

        $user = \Pimcore\Tool\Authentication::authenticateSession();
        $lang = $user->getLanguage();

        $editmodeHeadHtml .= '<script type="text/javascript" src="/admin/misc/json-translations-system/language/'.$lang.'/?_dc=' . Version::$revision . '"></script>'."\n";
        $editmodeHeadHtml .= '<script type="text/javascript" src="/admin/misc/json-translations-admin/language/'.$lang.'/?_dc=' . Version::$revision . '"></script>'."\n";


        $editmodeHeadHtml .= "\n\n";

        // set var for editable configurations which is filled by Document\Tag::admin()
        $editmodeHeadHtml .= '<script type="text/javascript">
            var editableConfigurations = new Array();
            var pimcore_document_id = ' . $request->getParam("document")->getId() . ';

            if(jQueryPreviouslyLoaded) {
                jQuery.noConflict( true );
            }
        </script>';

        $editmodeHeadHtml .= "\n\n<!-- /pimcore editmode -->\n\n\n";


        // add scripts in html header for pages in editmode
        if ($this->controller->editmode && Document\Service::isValidType($this->controller->document->getType()) ) { //ckogler
            include_once("simple_html_dom.php");
            $body = $this->getResponse()->getBody();

            $html = str_get_html($body);
            if($html) {
                $htmlElement = $html->find("html", 0);
                $head = $html->find("head", 0);
                $bodyElement = $html->find("body", 0);

                // if there's no head and no body, create a wrapper including these elements
                // add html headers for snippets in editmode, so there is no problem with javascript
                if(!$head && !$bodyElement && !$htmlElement) {
                    $body = "<!DOCTYPE html>\n<html>\n<head></head><body>" . $body . "</body></html>";
                    $html = str_get_html($body);

                    // get them again with the updated html markup
                    $htmlElement = $html->find("html", 0);
                    $head = $html->find("head", 0);
                    $bodyElement = $html->find("body", 0);
                }

                if($head && $bodyElement && $htmlElement) {
                    $head->innertext = $head->innertext . "\n\n" . $editmodeHeadHtml;
                    $bodyElement->onunload = "pimcoreOnUnload();";
                    if (\Pimcore\Tool\Admin::isExtJS6()) {
                        $bodyElement->innertext = $bodyElement->innertext . "\n\n" . '<script type="text/javascript" src="/pimcore/static6/js/pimcore/document/edit/startup.js?_dc=' . Version::$revision . '"></script>' . "\n\n";
                    } else {
                        $bodyElement->innertext = $bodyElement->innertext . "\n\n" . '<script type="text/javascript" src="/pimcore/static/js/pimcore/document/edit/startup.js?_dc=' . Version::$revision . '"></script>' . "\n\n";
                    }

                    $body = $html->save();
                    $this->getResponse()->setBody($body);
                } else {
                    $this->getResponse()->setBody('<div style="font-size:30px; font-family: Arial; font-weight:bold; color:red; text-align: center; margin: 40px 0">You have to define a &lt;html&gt;, &lt;head&gt;, &lt;body&gt;<br />HTML-tag in your view/layout markup!</div>');
                }

                $html->clear();
                unset($html);
            }
        }

        // IE compatibility
        //$this->getResponse()->setHeader("X-UA-Compatible", "IE=8; IE=9", true);
    }

    /**
     *
     */
    public function dispatchLoopShutdown() {
        $this->getResponse()->setHeader("X-Frame-Options", "SAMEORIGIN", true);
    }
}
