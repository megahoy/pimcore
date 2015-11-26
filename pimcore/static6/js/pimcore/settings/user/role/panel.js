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


pimcore.registerNS("pimcore.settings.user.role.panel");
pimcore.settings.user.role.panel = Class.create(pimcore.settings.user.panels.abstract, {

    getTabPanel: function () {

        if (!this.panel) {
            this.panel = new Ext.Panel({
                id: "pimcore_roles",
                title: t("roles"),
                iconCls: "pimcore_icon_roles",
                border: false,
                layout: "border",
                closable:true,
                items: [this.getRoleTree(), this.getEditPanel()]
            });

            var tabPanel = Ext.getCmp("pimcore_panel_tabs");
            tabPanel.add(this.panel);
            tabPanel.setActiveItem("pimcore_roles");

            this.panel.on("destroy", function () {
                pimcore.globalmanager.remove("roles");
            }.bind(this));

            this.panel.updateLayout();
            pimcore.layout.refresh();
        }

        return this.panel;
    },

    getRoleTree: function () {
        if (!this.tree) {
            var store = Ext.create('Ext.data.TreeStore', {
                proxy: {
                    type: 'ajax',
                    url: '/admin/user/role-tree-get-childs-by-id/'
                }
            });

            this.tree = Ext.create('Ext.tree.Panel', {
                id: "pimcore_panel_roles_tree",
                store: store,
                region: "west",
                useArrows:true,
                autoScroll:true,
                animate:true,
                containerScroll: true,
                border: true,
                split:true,
                width: 180,
                root: {
                    draggable:false,
                    id: '0',
                    text: t("all_roles"),
                    allowChildren: true,
                    iconCls: "pimcore_icon_folder",
                    expanded: true
                },
                viewConfig: {
                    plugins: {
                        ptype: 'treeviewdragdrop',
                        appendOnly: true,
                        ddGroup: "roles"
                    }
                }
                ,
                listeners: this.getTreeNodeListeners()
            });
        }
        this.tree.getRootNode().expand();

        return this.tree;
    },

    onTreeNodeClick: function (tree, record, item, index, e, eOpts ) {

        if(!record.data.allowChildren && record.data.id > 0) {
            var rolePanelKey = "role_" + record.data.id;
            if(this.panels[rolePanelKey]) {
                this.panels[rolePanelKey].activate();
            } else {
                var rolePanel = new pimcore.settings.user.role.tab(this, record.data.id);
                this.panels[rolePanelKey] = rolePanel;
            }
        }
    },

    onTreeNodeContextmenu: function (tree, record, item, index, e, eOpts ) {
        tree.select();

        var menu = new Ext.menu.Menu();

        if (record.data.allowChildren) {
            menu.add(new Ext.menu.Item({
                text: t('add_folder'),
                iconCls: "pimcore_icon_folder_add",
                listeners: {
                    "click": this.add.bind(this, "rolefolder", null, record)
                }
            }));
            menu.add(new Ext.menu.Item({
                text: t('add_role'),
                iconCls: "pimcore_icon_role_add",
                listeners: {
                    "click": this.add.bind(this, "role", null, record)
                }
            }));
        } else if (record.data.elementType == "role") {
            menu.add(new Ext.menu.Item({
                text: t('clone_role'),
                iconCls: "pimcore_icon_role_add",
                listeners: {
                    "click": this.add.bind(this, "role", record, record)
                }
            }));
        }

        menu.add(new Ext.menu.Item({
            text: t('delete'),
            iconCls: "pimcore_icon_delete",
            listeners: {
                "click": this.remove.bind(this, tree, record)
            }
        }));

        if(typeof menu.items != "undefined" && typeof menu.items.items != "undefined"
                                                                    && menu.items.items.length > 0) {
            menu.showAt(e.pageX, e.pageY);
        }
        e.stopEvent();
    },

    addComplete: function (parentNode, transport) {
        try{
            var data = Ext.decode(transport.responseText);
            if(data && data.success){
                var tree = parentNode.getOwnerTree();
                tree.getStore().reload({
                    node: parentNode
                });
            } else {
                 pimcore.helpers.showNotification(t("error"), t("role_creation_error"), "error",t(data.message));
            }

        } catch(e){
            console.log(e);
            pimcore.helpers.showNotification(t("error"), t("role_creation_error"), "error");
        }
    },

    update: function (userId, values) {

        Ext.Ajax.request({
            url: "/admin/user/update",
            method: "post",
            params: {
                id: userId,
                data: Ext.encode(values)
            },
            success: function (transport) {
                try{
                    var res = Ext.decode(transport.responseText);
                    if (res.success) {
                        pimcore.helpers.showNotification(t("success"), t("role_save_success"), "success");
                    } else {
                        pimcore.helpers.showNotification(t("error"), t("role_save_error"), "error",t(res.message));
                    }
                } catch(e){
                    pimcore.helpers.showNotification(t("error"), t("role_save_error"), "error");
                }
            }.bind(this)
        });
    },

    activate: function () {
        Ext.getCmp("pimcore_panel_tabs").setActiveItem("pimcore_roles");
    }
});





