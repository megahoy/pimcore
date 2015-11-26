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

pimcore.registerNS("pimcore.settings.targeting.rules.panel");
pimcore.settings.targeting.rules.panel= Class.create({

    initialize: function() {
        this.treeDataUrl = '/admin/reports/targeting/rule-list/';
    },


    getLayout: function () {

        if (this.layout == null) {
            this.layout = new Ext.Panel({
                title: t('global_targeting_rules'),
                layout: "border",
                closable: true,
                border: false,
                iconCls: "pimcore_icon_tab_targeting",
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
                rootVisible: false,
                tbar: Ext.create('Ext.Toolbar', {
                    cls: 'main-toolbar',
                    items: [
                        {
                            text: t("add_target"),
                            iconCls: "pimcore_icon_add",
                            handler: this.addTarget.bind(this)
                        }
                    ]
                }),
                listeners: this.getTreeNodeListeners()
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
                newChildNode.data.iconCls = "pimcore_icon_targeting";
            }
        };
        return treeNodeListeners;
    },


    addTarget: function () {
        Ext.MessageBox.prompt(t('add_target'), t('enter_the_name_of_the_new_target'),
                                                this.addTargetComplete.bind(this), null, null, "");
    },

    addTargetComplete: function (button, value, object) {

        var regresult = value.match(/[a-zA-Z0-9_\-]+/);
        if (button == "ok" && value.length > 2 && regresult == value) {
            Ext.Ajax.request({
                url: "/admin/reports/targeting/rule-add",
                params: {
                    name: value
                },
                success: function (response) {
                    var data = Ext.decode(response.responseText);

                    this.tree.getStore().load();

                    if(!data || !data.success) {
                        Ext.Msg.alert(t('add_target'), t('problem_creating_new_target'));
                    } else {
                        this.openTarget(intval(data.id));
                    }
                }.bind(this)
            });
        } else if (button == "cancel") {
            return;
        }
        else {
            Ext.Msg.alert(t('add_target'), t('naming_requirements_3chars'));
        }
    },



    onTreeNodeClick: function (tree, record, item, index, e, eOpts ) {
        this.openTarget(record.data);
    },


    deleteTarget: function (tree, record) {
        Ext.Ajax.request({
            url: "/admin/reports/targeting/rule-delete",
            params: {
                id: record.data.id
            },
            success: function () {
                this.tree.getStore().load();
            }.bind(this)
        });
    },

    onTreeNodeContextmenu: function (tree, record, item, index, e, eOpts ) {
        tree.select();

        var menu = new Ext.menu.Menu();
        menu.add(new Ext.menu.Item({
            text: t('delete'),
            iconCls: "pimcore_icon_delete",
            handler: this.deleteTarget.bind(this, tree, record)
        }));

        e.stopEvent();
        menu.showAt(e.pageX, e.pageY);
    },


    openTarget: function (node) {

        if(!is_numeric(node)) {
            node = node.id;
        }


        var existingPanel = Ext.getCmp("pimcore_targeting_panel_" + node);
        if(existingPanel) {
            this.panel.setActiveItem(existingPanel);
            return;
        }

        Ext.Ajax.request({
            url: "/admin/reports/targeting/rule-get",
            params: {
                id: node
            },
            success: function (response) {
                try {
                    var res = Ext.decode(response.responseText);
                    var item = new pimcore.settings.targeting.rules.item(this, res);
                } catch (e) {
                    console.log(e);
                }
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