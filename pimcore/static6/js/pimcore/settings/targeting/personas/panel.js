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

pimcore.registerNS("pimcore.settings.targeting.personas.panel");
pimcore.settings.targeting.personas.panel= Class.create({

    initialize: function() {
        this.treeDataUrl = '/admin/reports/targeting/persona-list/';
    },


    getLayout: function () {

        if (this.layout == null) {
            this.layout = new Ext.Panel({
                title: t('personas'),
                layout: "border",
                closable: true,
                border: false,
                iconCls: "pimcore_icon_personas",
                items: [this.getTree(), this.getTabPanel()]
            });
        }

        return this.layout;
    },

    getTree: function () {
        if (!this.tree) {
            var store = Ext.create('Ext.data.TreeStore', {
                autoLoad: false,
                autoSync: true,
                proxy: {
                    type: 'ajax',
                    url: this.treeDataUrl,
                    reader: {
                        type: 'json'
                    }
                }
            });

            this.tree = new Ext.tree.TreePanel({
                store: store,
                region: "west",
                useArrows:true,
                autoScroll:true,
                animate:true,
                containerScroll: true,
                width: 200,
                split: true,
                root: {
                    id: '0'
                },
                listeners: this.getTreeNodeListeners(),
                rootVisible: false,
                tbar: {
                    items: [
                        {
                            text: t("add_persona"),
                            iconCls: "pimcore_icon_add",
                            handler: this.addPersona.bind(this)
                        }
                    ]
                }
            });

        }

        return this.tree;
    },


    getTreeNodeListeners: function () {
        var treeNodeListeners = {
            'itemclick': this.onTreeNodeClick.bind(this),
            "itemcontextmenu": this.onTreeNodeContextmenu.bind(this),
            "render": function () {
                this.getRootNode().expand();
            },
            'beforeitemappend': function (thisNode, newChildNode, index, eOpts) {
                //newChildNode.data.expanded = true;
                newChildNode.data.leaf = true;
                newChildNode.data.iconCls = "pimcore_icon_personas";
            }
        };
        return treeNodeListeners;
    },



    onTreeNodeContextmenu: function (tree, record, item, index, e, eOpts ) {
        tree.select();

        var menu = new Ext.menu.Menu();
        menu.add(new Ext.menu.Item({
            text: t('delete'),
            iconCls: "pimcore_icon_delete",
            handler: this.deletePersona.bind(this, tree, record)
        }));

        e.stopEvent();
        menu.showAt(e.pageX, e.pageY);
    },

    addPersona: function () {
        Ext.MessageBox.prompt(t('add_persona'), t('enter_the_name_of_the_new_persona'),
                                                this.addPersonaComplete.bind(this), null, null, "");
    },


    onTreeNodeClick: function (tree, record, item, index, e, eOpts ) {
        this.openPersona(record.data);
    },


    addPersonaComplete: function (button, value, object) {

        if (button == "ok" && value.length > 2) {
            Ext.Ajax.request({
                url: "/admin/reports/targeting/persona-add",
                params: {
                    name: value
                },
                success: function (response) {
                    var data = Ext.decode(response.responseText);

                    this.tree.getStore().reload();

                    if(!data || !data.success) {
                        Ext.Msg.alert(t('add_persona'), t('problem_creating_new_persona'));
                    } else {
                        this.openPersona(intval(data.id));

                        pimcore.globalmanager.get("personas").reload();
                    }
                }.bind(this)
            });
        } else if (button == "cancel") {
            return;
        }
        else {
            Ext.Msg.alert(t('add_persona'), t('naming_requirements_3chars'));
        }
    },

    deletePersona: function (tree, record) {
        Ext.Ajax.request({
            url: "/admin/reports/targeting/persona-delete",
            params: {
                id: record.data.id
            },
            success: function () {
                this.tree.getStore().load();

                pimcore.globalmanager.get("personas").reload();
            }.bind(this)
        });
    },

    openPersona: function (node) {

        if(!is_numeric(node)) {
            node = node.id;
        }


        var existingPanel = Ext.getCmp("pimcore_personas_panel_" + node);
        if(existingPanel) {
            this.panel.setActiveItem(existingPanel);
            return;
        }

        Ext.Ajax.request({
            url: "/admin/reports/targeting/persona-get",
            params: {
                id: node
            },
            success: function (response) {
                var res = Ext.decode(response.responseText);
                var item = new pimcore.settings.targeting.personas.item(this, res);
            }.bind(this)
        });

    },

    getTabPanel: function () {
        if (!this.panel) {
            this.panel = new Ext.TabPanel({
                region: "center",
                border: false,
                plugins:
                    [
                        Ext.create('Ext.ux.TabCloseMenu', {
                            showCloseAll: true,
                            showCloseOthers: true
                        }),
                        Ext.create('Ext.ux.TabReorderer', {})
                    ]
            });
        }

        return this.panel;
    }
});