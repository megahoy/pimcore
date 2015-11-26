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
            tabPanel.activate("pimcore_roles");

            this.panel.on("destroy", function () {
                pimcore.globalmanager.remove("roles");
            }.bind(this));

            pimcore.layout.refresh();
        }

        return this.panel;
    },

    getRoleTree: function () {
        if (!this.tree) {
            this.tree = new Ext.tree.TreePanel({
                id: "pimcore_panel_roles_tree",
                region: "west",
                useArrows:true,
                autoScroll:true,
                animate:true,
                enableDD:true,
                ddGroup: "roles",
                containerScroll: true,
                border: true,
                split:true,
                width: 150,
                minSize: 100,
                maxSize: 350,
                root: {
                    nodeType: 'async',
                    draggable:false,
                    id: '0',
                    text: t("all_roles"),
                    allowChildren: true
                },
                loader: new Ext.tree.TreeLoader({
                    dataUrl: '/admin/user/role-tree-get-childs-by-id/',
                    requestMethod: "GET",
                    baseAttrs: {
                        listeners: this.getTreeNodeListeners(),
                        reference: this,
                        allowDrop: true,
                        allowChildren: true,
                        isTarget: true
                    }
                })
            });


            this.tree.on("render", function () {
                this.getRootNode().expand();
            });
        }

        return this.tree;
    },

    onTreeNodeClick: function (node) {

        if(!node.attributes.allowChildren && node.id > 0) {
            var rolePanelKey = "role_" + node.id;
            if(this.panels[rolePanelKey]) {
                this.panels[rolePanelKey].activate();
            } else {
                var rolePanel = new pimcore.settings.user.role.tab(this, node.id);
                this.panels[rolePanelKey] = rolePanel;
            }
        }
    },

    onTreeNodeContextmenu: function () {

        this.select();
        var menu = new Ext.menu.Menu();

        if (this.allowChildren) {
            menu.add(new Ext.menu.Item({
                text: t('add_folder'),
                iconCls: "pimcore_icon_folder_add",
                listeners: {
                    "click": this.attributes.reference.add.bind(this, "rolefolder", 0)
                }
            }));
            menu.add(new Ext.menu.Item({
                text: t('add_role'),
                iconCls: "pimcore_icon_role_add",
                listeners: {
                    "click": this.attributes.reference.add.bind(this, "role", 0)
                }
            }));
        } else if (this.attributes.elementType == "role") {
            menu.add(new Ext.menu.Item({
                text: t('clone_role'),
                iconCls: "pimcore_icon_role_add",
                listeners: {
                    "click": this.attributes.reference.add.bind(this, "role", this.attributes.id)
                }
            }));
        }

        menu.add(new Ext.menu.Item({
            text: t('delete'),
            iconCls: "pimcore_icon_delete",
            listeners: {
                "click": this.attributes.reference.remove.bind(this)
            }
        }));

        if(typeof menu.items != "undefined" && typeof menu.items.items != "undefined"
                                                                    && menu.items.items.length > 0) {
            menu.show(this.ui.getAnchor());
        }
    },

    addComplete: function (parentId, transport) {
        try{
            var data = Ext.decode(transport.responseText);
            if(data && data.success){
                this.tree.getNodeById(parentId).reload();
            } else {
                 pimcore.helpers.showNotification(t("error"), t("role_creation_error"), "error",t(data.message));
            }

        } catch(e){
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
        Ext.getCmp("pimcore_panel_tabs").activate("pimcore_roles");
    }
});





