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


pimcore.registerNS("pimcore.settings.user.usertab");
pimcore.settings.user.usertab = Class.create({

    initialize: function (parentPanel, id) {
        this.parentPanel = parentPanel;
        this.id = id;

        Ext.Ajax.request({
            url: "/admin/user/get",
            success: this.loadComplete.bind(this),
            params: {
                id: this.id
            }
        });
    },

    loadComplete: function (transport) {
        var response = Ext.decode(transport.responseText);
        if(response && response.success) {
            this.data = response;
            this.initPanel();
        }
    },

    initPanel: function () {

        this.panel = new Ext.TabPanel({
            title: this.data.user.name,
            closable: true,
            activeTab: 0,
            iconCls: "pimcore_icon_user",
            buttons: [{
                text: t("save"),
                handler: this.save.bind(this),
                iconCls: "pimcore_icon_accept"
            }]
        });

        this.panel.on("beforedestroy", function () {
            delete this.parentPanel.panels["user_" + this.id];
        }.bind(this));

        this.settings = new pimcore.settings.user.user.settings(this);
        this.workspaces = new pimcore.settings.user.workspaces(this);
        this.objectrelations = new pimcore.settings.user.user.objectrelations(this);

        this.panel.add(this.settings.getPanel());
        this.panel.add(this.workspaces.getPanel());
        this.panel.add(this.objectrelations.getPanel());

        if(this.data.user.admin) {
            this.workspaces.disable();
        }

        this.parentPanel.getEditPanel().add(this.panel);
        this.parentPanel.getEditPanel().activate(this.panel);
    },

    activate: function () {
        this.parentPanel.getEditPanel().activate(this.panel);
    },

    save: function () {

        var data = {
            id: this.id
        };

        try {
            data.data = Ext.encode(this.settings.getValues());
        } catch (e) {
            console.log(e);
        }

        try {
            data.workspaces = Ext.encode(this.workspaces.getValues());
        } catch (e2) {
            console.log(e2);
        }

        Ext.Ajax.request({
            url: "/admin/user/update",
            method: "post",
            params: data,
            success: function (transport) {
                try{
                    var res = Ext.decode(transport.responseText);
                    if (res.success) {
                        pimcore.helpers.showNotification(t("success"), t("user_save_success"), "success");
                    } else {
                        pimcore.helpers.showNotification(t("error"), t("user_save_error"), "error",t(res.message));
                    }
                } catch(e){
                    pimcore.helpers.showNotification(t("error"), t("user_save_error"), "error");
                }
            }.bind(this)
        });
    }

});
