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

pimcore.registerNS("pimcore.object.objectbrick");
pimcore.object.objectbrick = Class.create(pimcore.object.fieldcollection, {

    getTabPanel: function () {
  
        if (!this.panel) {
            this.panel = new Ext.Panel({
                id: "pimcore_objectbricks",
                title: t("objectbricks"),
                iconCls: "pimcore_icon_objectbricks",
                border: false,
                layout: "border",
                closable:true,
                items: [this.getTree(), this.getEditPanel()]
            });

            var tabPanel = Ext.getCmp("pimcore_panel_tabs");
            tabPanel.add(this.panel);
            tabPanel.activate("pimcore_objectbricks");


            this.panel.on("destroy", function () {
                pimcore.globalmanager.remove("objectbricks");
            }.bind(this));

            pimcore.layout.refresh();
        }

        return this.panel;
    },

    getTree: function () {
        if (!this.tree) {
            this.tree = new Ext.tree.TreePanel({
                id: "pimcore_panel_objectbricks_tree",
                region: "west",
                useArrows:true,
                autoScroll:true,
                animate:true,
                containerScroll: true,
                border: true,
                width: 200,
                split: true,
                root: {
                    nodeType: 'async',
                    id: '0'
                },
                loader: new Ext.tree.TreeLoader({
                    dataUrl: '/admin/class/objectbrick-tree',
                    requestMethod: "GET",
                    baseAttrs: {
                        listeners: this.getTreeNodeListeners(),
                        reference: this,
                        allowDrop: false,
                        allowChildren: false,
                        isTarget: false,
                        iconCls: "pimcore_icon_objectbricks",
                        leaf: true
                    }
                }),
                rootVisible: false,
                tbar: {
                    items: [
                        {
                            text: t("add_objectbrick"),
                            iconCls: "pimcore_icon_objectbrick_add",
                            handler: this.addField.bind(this)
                        }
                    ]
                }
            });

            this.tree.on("render", function () {
                this.getRootNode().expand();
            });
        }

        return this.tree;
    },

    onTreeNodeClick: function (node) {
        this.openBrick(node.id);
    },

    openBrick: function (id) {
        Ext.Ajax.request({
            url: "/admin/class/objectbrick-get",
            params: {
                id: id
            },
            success: this.addFieldPanel.bind(this)
        });
    },

    addFieldPanel: function (response) {

        var data = Ext.decode(response.responseText);

        /*if (this.fieldPanel) {
            this.getEditPanel().removeAll();
            delete this.fieldPanel;
        }*/

        var fieldPanel = new pimcore.object.objectbricks.field(data, this, this.openBrick.bind(this, data.key));
        pimcore.layout.refresh();
        
    },


    addField: function () {
        Ext.MessageBox.prompt(t('add_objectbrick'), t('enter_the_name_of_the_new_objectbrick'),
                                                    this.addFieldComplete.bind(this), null, null, "");
    },

    addFieldComplete: function (button, value, object) {

        var regresult = value.match(/[a-zA-Z]+[a-zA-Z0-9]*/);
        var forbiddennames = ["abstract","class","data","folder","list","permissions","resource","concrete",
                                                                                                        "interface"];

        if (button == "ok" && value.length > 2 && regresult == value && !in_array(value, forbiddennames)) {
            Ext.Ajax.request({
                url: "/admin/class/objectbrick-update",
                params: {
                    key: value,
                    task: "add"
                },
                success: function (response) {
                    this.tree.getRootNode().reload();

                    var data = Ext.decode(response.responseText);
                    if(data && data.success) {
                        this.openBrick(data.id);
                    } else {
                        pimcore.helpers.showNotification(t("error"), data["message"], "error");
                    }
                }.bind(this)
            });
        }
        else if (button == "cancel") {
            return;
        }
        else {
            Ext.Msg.alert(t('add_objectbrick'), t('problem_creating_new_objectbrick'));
        }
    },

    activate: function () {
        Ext.getCmp("pimcore_panel_tabs").activate("pimcore_objectbricks");
    },

    deleteField: function () {

        Ext.Msg.confirm(t('delete'), t('delete_message'), function(btn){
            if (btn == 'yes'){
                Ext.Ajax.request({
                    url: "/admin/class/objectbrick-delete",
                    params: {
                        id: this.id
                    }
                });

                this.attributes.reference.getEditPanel().removeAll();
                this.remove();
            }
        }.bind(this));
    }


});
