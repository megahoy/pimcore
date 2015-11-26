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

pimcore.registerNS("pimcore.extensionmanager.settings");
pimcore.extensionmanager.settings = Class.create({

    id: null,
    type: null,

    initialize: function (id, type, iframeSrc) {

        this.id = id;
        this.type = type;

        if (!this.panel) {

            var tabPanel = Ext.getCmp("pimcore_panel_tabs");
            var height = tabPanel.getHeight();
            height = height - 40;

            this.panel = new Ext.Panel({
                id: "pimcore_extension_" + id + "_" + type,
                title: t('settings') + ' - ' + id,
                border: false,
                layout: "fit",
                closable:true,
                bodyStyle: "-webkit-overflow-scrolling:touch;",
                html: '<iframe frameborder="0" style="width:100%; height: ' + height
                                                            + 'px" src="' + iframeSrc + '"></iframe>'
            });


            tabPanel.add(this.panel);
            tabPanel.activate("pimcore_extension_" + id + "_" + type);

            this.panel.on("destroy", function () {
                pimcore.globalmanager.remove("extension_settings_" + this.id + "_" + this.type);
            }.bind(this));
            pimcore.layout.refresh();
        }
    },

    activate: function () {
        var tabPanel = Ext.getCmp("pimcore_panel_tabs");
        tabPanel.activate("pimcore_extension_" + this.id + "_" + type);
    }
});