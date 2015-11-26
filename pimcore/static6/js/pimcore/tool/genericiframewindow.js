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

pimcore.registerNS("pimcore.tool.genericiframewindow");
pimcore.tool.genericiframewindow = Class.create({

    initialize: function (id, src, iconCls, title) {

        this.id = id;
        this.src = src;
        this.iconCls = iconCls;
        this.title = title;

        this.getTabPanel();
    },

    activate: function () {
        var tabPanel = Ext.getCmp("pimcore_panel_tabs");
        tabPanel.setActiveItem("pimcore_iframe_" + this.id);
    },

    getTabPanel: function () {

        this.reloadButton = new Ext.Button({
            text: t("reload"),
            iconCls: "pimcore_icon_reload",
            handler: this.reload.bind(this)
        });

        var toolbar = Ext.create('Ext.Toolbar', {
            cls: 'main-toolbar',
            items: [this.reloadButton]
        });

        if (!this.panel) {
            this.panel = new Ext.Panel({
                id: "pimcore_iframe_" + this.id,
                title: this.title,
                iconCls: this.iconCls,
                border: false,
                layout: "fit",
                closable:true,
                bodyStyle: "-webkit-overflow-scrolling:touch;",
                html: '<iframe src="about:blank" frameborder="0" width="100%" id="pimcore_iframe_frame_'
                                    + this.id + '"></iframe>',
                tbar: toolbar
            });

            this.panel.on("resize", this.onLayoutResize.bind(this));
            this.panel.on("afterrender", this.reload.bind(this));

            var tabPanel = Ext.getCmp("pimcore_panel_tabs");
            tabPanel.add(this.panel);
            tabPanel.setActiveItem("pimcore_iframe_" + this.id);

            this.panel.on("destroy", function () {
                pimcore.globalmanager.remove(this.id);
            }.bind(this));

            pimcore.layout.refresh();
        }

        return this.panel;
    },

    onLayoutResize: function (el, width, height, rWidth, rHeight) {
        this.setLayoutFrameDimensions(width, height);
    },

    setLayoutFrameDimensions: function (width, height) {
        Ext.get("pimcore_iframe_frame_" + this.id).setStyle({
            height: (height - 50) + "px"
        });
    },

    reload: function () {
        try {
            var d = new Date();
            Ext.get("pimcore_iframe_frame_" + this.id).dom.src = this.src;
        }
        catch (e) {
            console.log(e);
        }
    }

});