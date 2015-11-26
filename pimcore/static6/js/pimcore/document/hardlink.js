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

pimcore.registerNS("pimcore.document.hardlink");
pimcore.document.hardlink = Class.create(pimcore.document.document, {

    initialize: function(id) {

        this.id = intval(id);
        this.setType("hardlink");
        this.addLoadingPanel();

        pimcore.plugin.broker.fireEvent("preOpenDocument", this, "link");
        this.getData();
    },

    init: function () {
        if (this.isAllowed("properties")) {
            this.properties = new pimcore.document.properties(this, "document");
        }

        if (this.isAllowed("settings")) {
            this.notes = new pimcore.element.notes(this, "document");
        }

        this.dependencies = new pimcore.element.dependencies(this, "document");
    },

    getSaveData : function () {
        var parameters = {};

        parameters.id = this.id;
        var values = this.panel.getForm().getFieldValues();
        values.published = this.data.published;
        parameters.data = Ext.encode(values);

        if (this.isAllowed("properties")) {
            // properties
            try {
                parameters.properties = Ext.encode(this.properties.getValues());
            }
            catch (e) {
                //console.log(e);
            }
        }

        return parameters;
    },

    addTab: function () {

        var tabTitle = this.data.key;
        this.tabPanel = Ext.getCmp("pimcore_panel_tabs");
        var tabId = "document_" + this.id;
        this.tab = new Ext.Panel({
            id: tabId,
            title: tabTitle,
            closable:true,
            layout: "border",
            items: [
                this.getLayoutToolbar(),
                this.getTabPanel()
            ],
            iconCls: "pimcore_icon_" + this.data.type,
            document: this
        });

        this.tab.on("beforedestroy", function () {
            Ext.Ajax.request({
                url: "/admin/element/unlock-element",
                params: {
                    id: this.data.id,
                    type: "document"
                }
            });
        }.bind(this));

        // remove this instance when the panel is closed
        this.tab.on("destroy", function () {
            pimcore.globalmanager.remove("document_" + this.id);
            pimcore.helpers.forgetOpenTab("document_" + this.id + "_hardlink");
        }.bind(this));

        this.tab.on("activate", function () {
            this.tab.updateLayout();
            pimcore.layout.refresh();
        }.bind(this));

        this.tab.on("afterrender", function (tabId) {
            this.tabPanel.setActiveItem(tabId);
            pimcore.plugin.broker.fireEvent("postOpenDocument", this, "link");
        }.bind(this, tabId));

        this.removeLoadingPanel();

        this.tabPanel.add(this.tab);

        // recalculate the layout
        pimcore.layout.refresh();
    },

    getLayoutToolbar : function () {

        if (!this.toolbar) {

            this.toolbarButtons = {};

            this.toolbarButtons.publish = new Ext.Button({
                text: t('save_and_publish'),
                iconCls: "pimcore_icon_publish_medium",
                scale: "small",
                handler: this.publish.bind(this)
            });


            this.toolbarButtons.unpublish = new Ext.Button({
                text: t('unpublish'),
                iconCls: "pimcore_icon_unpublish_medium",
                scale: "small",
                handler: this.unpublish.bind(this)
            });

            this.toolbarButtons.remove = new Ext.Button({
                text: t('delete'),
                iconCls: "pimcore_icon_delete_medium",
                scale: "small",
                handler: this.remove.bind(this)
            });


            var buttons = [];

            if (this.isAllowed("publish")) {
                buttons.push(this.toolbarButtons.publish);
            }
            if (this.isAllowed("unpublish") && !this.data.locked) {
                buttons.push(this.toolbarButtons.unpublish);
            }


            if(this.isAllowed("delete") && !this.data.locked) {
                buttons.push(this.toolbarButtons.remove);
            }

            buttons.push("-");

            this.toolbarButtons.reload = new Ext.Button({
                text: t('reload'),
                iconCls: "pimcore_icon_reload_medium",
                scale: "small",
                handler: this.reload.bind(this)
            });
            buttons.push(this.toolbarButtons.reload);

            buttons.push({
                text: t('show_in_tree'),
                iconCls: "pimcore_icon_download_showintree",
                scale: "small",
                handler: this.selectInTree.bind(this)
            });

            buttons.push("-");
            buttons.push({
                xtype: 'tbtext',
                text: this.data.id,
                scale: "small"
            });

            this.toolbar = new Ext.Toolbar({
                id: "document_toolbar_" + this.id,
                region: "north",
                border: false,
                cls: "main-toolbar",
                items: buttons
            });

            this.toolbar.on("afterrender", function () {
                window.setTimeout(function () {
                    // it's not possible to delete the root-node
                    if (this.id == 1) {
                        this.toolbarButtons.remove.hide();
                    }

                    if (!this.data.published) {
                        this.toolbarButtons.unpublish.hide();
                    }
                }.bind(this), 500);
            }.bind(this));
        }

        return this.toolbar;
    },

    getTabPanel: function () {

        var items = [];

        items.push(this.getLayoutForm());

        if (this.isAllowed("properties")) {
            items.push(this.properties.getLayout());
        }

        items.push(this.dependencies.getLayout());

        if (this.isAllowed("settings")) {
            items.push(this.notes.getLayout());
        }

        this.tabbar = new Ext.TabPanel({
            tabPosition: "top",
            region:'center',
            deferredRender:true,
            enableTabScroll:true,
            border: false,
            items: items,
            activeTab: 0
        });

        return this.tabbar;
    },

    getLayoutForm: function () {

        if (!this.panel) {

            var path = "";
            if (this.data.sourcePath) {
                path = this.data.sourcePath;
            }

            var pathField = new Ext.form.TextField({
                name: "sourcePath",
                fieldLabel: t("source_path"),
                value: path,
                fieldCls: "input_drop_target"
            });

            pathField.on("render", function (el) {
                var dd = new Ext.dd.DropZone(el.getEl().dom.parentNode.parentNode, {
                    ddGroup: "element",

                    getTargetFromEvent: function(e) {
                        return this.getEl();
                    },

                    onNodeOver : function(target, dd, e, data) {
                        return Ext.dd.DropZone.prototype.dropAllowed;
                    },

                    onNodeDrop : function(target, dd, e, data) {
                        data = data.records[0].data;
                        this.setValue(data.path);
                        return true;
                    }.bind(this)
                });
            });

            this.panel = new Ext.form.FormPanel({
                title: t('hardlink_properties'),
                autoHeight:true,
                labelWidth: 120,
                defaultType: 'textfield',
                defaults: {width:500},
                bodyStyle:'padding:10px;',
                region: "center",
                items :[pathField,{
                    xtype:"checkbox",
                    name: "propertiesFromSource",
                    fieldLabel: t("properties_from_source"),
                    checked: this.data.propertiesFromSource
                },{
                    xtype:"checkbox",
                    name: "childsFromSource",
                    fieldLabel: t("childs_from_source"),
                    checked: this.data.childsFromSource
                }]
            });
        }

        return this.panel;
    }
});

