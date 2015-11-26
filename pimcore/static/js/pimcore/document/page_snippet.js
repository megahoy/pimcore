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

pimcore.registerNS("pimcore.document.page_snippet");
pimcore.document.page_snippet = Class.create(pimcore.document.document, {

    addTab: function () {

        var tabTitle = this.data.key;
        if (tabTitle.length < 1) {
            tabTitle = "home";
        }

        this.tabPanel = Ext.getCmp("pimcore_panel_tabs");
        var tabId = "document_" + this.id;

        this.tab = new Ext.Panel({
            id: tabId,
            title: tabTitle,
            closable:true,
            hideMode: "offsets",
            layout: "border",
            items: [
                this.getLayoutToolbar(),
                this.getTabPanel()
            ],
            iconCls: "pimcore_icon_" + this.data.type,
            document: this
        });

        // remove this instance when the panel is closed
        this.tab.on("beforedestroy", function () {
            Ext.Ajax.request({
                url: "/admin/element/unlock-element",
                params: {
                    id: this.data.id,
                    type: "document"
                }
            });

            this.cleanUpOnDestroy();
        }.bind(this));

        this.tab.on("destroy", function () {
            pimcore.globalmanager.remove("document_" + this.id);
            pimcore.helpers.forgetOpenTab("document_" + this.id + "_" + this.data.type);
        }.bind(this));


        this.tab.on("activate", function () {
            this.tab.doLayout();
            pimcore.layout.refresh();
        }.bind(this));

        this.tab.on("afterrender", function (tabId) {
            this.tabPanel.activate(tabId);
            pimcore.plugin.broker.fireEvent("postOpenDocument", this, this.data.type);
        }.bind(this, tabId));

        this.removeLoadingPanel();

        this.tabPanel.add(this.tab);


        // recalculate the layout
        pimcore.layout.refresh();
    },

    cleanUpOnDestroy: function () {
        if (this.edit) {
            if (typeof this.edit.onClose == "function") {
                this.edit.onClose();
            }
        }
        if (this.preview) {
            if (typeof this.preview.onClose == "function") {
                this.preview.onClose();
            }
        }
        if (this.settings) {
            if (typeof this.settings.onClose == "function") {
                this.settings.onClose();
            }
        }
        if (this.properties) {
            if (typeof this.properties.onClose == "function") {
                this.properties.onClose();
            }
        }
        this.removeFromSession();
    },

    getLayoutToolbar : function () {

        if (!this.toolbar) {

            this.toolbarButtons = {};

            this.toolbarButtons.save = new Ext.SplitButton({
                text: t('save'),
                iconCls: "pimcore_icon_save_medium",
                scale: "medium",
                handler: this.unpublish.bind(this),
                menu: [{
                    text: t('save_close'),
                    iconCls: "pimcore_icon_save",
                    handler: this.unpublishClose.bind(this)
                }]
            });


            this.toolbarButtons.publish = new Ext.SplitButton({
                text: t('save_and_publish'),
                iconCls: "pimcore_icon_publish_medium",
                scale: "medium",
                handler: this.publish.bind(this),
                menu: [
                    {
                        text: t('save_pubish_close'),
                        iconCls: "pimcore_icon_save",
                        handler: this.publishClose.bind(this)
                    },{
                        text: t('save_only_new_version'),
                        iconCls: "pimcore_icon_save",
                        handler: this.save.bind(this)
                    },
                    {
                        text: t('save_only_scheduled_tasks'),
                        iconCls: "pimcore_icon_save",
                        handler: this.save.bind(this, "scheduler","scheduler")
                    }
                ]
            });


            this.toolbarButtons.unpublish = new Ext.Button({
                text: t('unpublish'),
                iconCls: "pimcore_icon_unpublish_medium",
                scale: "medium",
                handler: this.unpublish.bind(this)
            });

            this.toolbarButtons.reload = new Ext.Button({
                text: t('reload'),
                iconCls: "pimcore_icon_reload_medium",
                scale: "medium",
                handler: this.reload.bind(this)
            });

            // extras menu
            var extrasMenu = [];

            // translation menu
            if(pimcore.settings.google_translate_api_key) {
                var translationMenu = [];
                for (var p=0; p<pimcore.settings.websiteLanguages.length; p++) {

                    translationMenu.push({
                        text: pimcore.available_languages[pimcore.settings.websiteLanguages[p]],
                        handler: function (lang) {
                            Ext.Ajax.request({
                                url: this.urlprefix + this.getType() + '/translate/language/' + lang,
                                method: "post",
                                params: this.getSaveData(),
                                success: function () {
                                    this.edit.reload(true);
                                }.bind(this)
                            });
                        }.bind(this, pimcore.settings.websiteLanguages[p])
                    });
                }

                if(translationMenu.length > 0) {
                    extrasMenu.push({
                        text: t("translate_content_to"),
                        iconCls: "pimcore_icon_translations",
                        hideOnClick: false,
                        menu: translationMenu
                    });
                }
            }

            if(extrasMenu.length > 0) {
                this.toolbarButtons.extras = new Ext.Button({
                    text: t('extras'),
                    iconCls: "pimcore_icon_extras_medium",
                    scale: "medium",
                    hideOnClick: false,
                    menu: extrasMenu
                });
            }

            this.toolbarButtons.remove = new Ext.Button({
                text: t('delete'),
                iconCls: "pimcore_icon_delete_medium",
                scale: "medium",
                handler: this.remove.bind(this)
            });


            var buttons = [];

            if (this.isAllowed("save")) {
                buttons.push(this.toolbarButtons.save);
            }
            if (this.isAllowed("publish")) {
                buttons.push(this.toolbarButtons.publish);
            }
            if (this.isAllowed("unpublish") && !this.data.locked) {
                buttons.push(this.toolbarButtons.unpublish);
            }

            if(this.isAllowed("delete") && !this.data.locked && this.data.id != 1) {
                buttons.push(this.toolbarButtons.remove);
            }

            buttons.push("-");

            buttons.push(this.toolbarButtons.reload);

            buttons.push({
                text: t('show_in_tree'),
                iconCls: "pimcore_icon_download_showintree",
                scale: "medium",
                handler: this.selectInTree.bind(this)
            });

            buttons.push({
                text: t("show_metainfo"),
                scale: "medium",
                iconCls: "pimcore_icon_info_large",
                handler: this.showMetaInfo.bind(this)
            });



            if(typeof this.toolbarButtons.extras != "undefined") {
                buttons.push("-");
                buttons.push(this.toolbarButtons.extras);
            }


            buttons.push("-");
            buttons.push({
                text: t("open"),
                iconCls: "pimcore_icon_cursor_medium",
                scale: "medium",
                handler: function () {
                    var date = new Date();
                    var link = this.data.path + this.data.key + "?pimcore_preview=true&time=" + date.getTime();

                    // add persona parameter if available
                    if(this["edit"] && this.edit["persona"]) {
                        if(this.edit.persona && this.edit.persona.getValue()) {
                            link += "&_ptp=" + this.edit.persona.getValue();
                        }
                    }

                    window.open(link);
                }.bind(this)
            });
            buttons.push("-");
            buttons.push({
                xtype: 'tbtext',
                text: this.data.id,
                scale: "medium"
            });

            // version notification
            this.newerVersionNotification = new Ext.Toolbar.TextItem({
                xtype: 'tbtext',
                text: '&nbsp;&nbsp;<img src="/pimcore/static/img/icon/error.png" align="absbottom" />&nbsp;&nbsp;'
                    + t("this_is_a_newer_not_published_version"),
                scale: "medium",
                hidden: true
            });

            buttons.push(this.newerVersionNotification);

            // check for newer version than the published
            if (this.data.versions.length > 0) {
                if (this.data.modificationDate < this.data.versions[0].date) {
                    this.newerVersionNotification.show();
                }
            }


            this.toolbar = new Ext.Toolbar({
                id: "document_toolbar_" + this.id,
                region: "north",
                border: false,
                cls: "document_toolbar",
                items: buttons
            });

            this.toolbar.on("afterrender", function () {
                window.setTimeout(function () {
                    if (!this.data.published) {
                        this.toolbarButtons.unpublish.hide();
                    } else if (this.isAllowed("publish")) {
                        this.toolbarButtons.save.hide();
                    }
                }.bind(this), 500);
            }.bind(this));
        }

        return this.toolbar;
    },

    saveToSession: function (onComplete) {

        if (typeof onComplete != "function") {
            onComplete = function () {
            };
        }

        Ext.Ajax.request({
            url: this.urlprefix + this.getType() + '/save-to-session/',
            method: "post",
            params: this.getSaveData(),
            success: onComplete
        });
    },

    removeFromSession: function () {
        Ext.Ajax.request({
            url: this.urlprefix + this.getType() + '/remove-from-session/',
            params: {id: this.data.id}
        });
    },

    reloadEditmode: function () {

        this.saveToSession(function () {
            if (this.edit && this.edit.layout.rendered) {
                this.edit.reload(true);
            }

            if (this.preview && this.preview.layout.rendered) {
                this.preview.loadCurrentPreview();
            }

        }.bind(this));
    },

    showMetaInfo: function() {

        new pimcore.element.metainfo([
            {
                name: "id",
                value: this.data.id
            },
            {
                name: "path",
                value: this.data.path + this.data.key
            }, {
                name: "parentid",
                value: this.data.parentId
            }, {
                name: "type",
                value: this.data.type
            }, {
                name: "modificationdate",
                type: "date",
                value: this.data.modificationDate
            }, {
                name: "creationdate",
                type: "date",
                value: this.data.creationDate
            }, {
                name: "usermodification",
                type: "user",
                value: this.data.userModification
            }, {
                name: "userowner",
                type: "user",
                value: this.data.userOwner
            },
            {
                name: "deeplink",
                value: window.location.protocol + "//" + window.location.hostname + "/admin/login/deeplink?document_" + this.data.id + "_" + this.data.type
            }
        ], "document");
    }
});
