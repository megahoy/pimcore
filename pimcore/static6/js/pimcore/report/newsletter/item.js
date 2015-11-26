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

pimcore.registerNS("pimcore.report.newsletter.item");
pimcore.report.newsletter.item = Class.create({


    initialize: function (data, parentPanel) {
        this.parentPanel = parentPanel;
        this.data = data;

        this.addLayout();
    },


    getAnalyticsVisiblity: function () {
        if(!pimcore.settings.google_analytics_enabled) {
            return false;
        }

        if(this.form && this.form.rendered) {
            var values = this.form.getForm().getFieldValues();
            if(!values["googleAnalytics"]) {
                return false;
            }
        } else {
            if(!this.data.googleAnalytics) {
                return false;
            }
        }
        return true;
    },

    addLayout: function () {

        var panelButtons = [];

        panelButtons.push({
            text: t("send_test_newsletter"),
            iconCls: "pimcore_icon_send_test",
            handler: this.sendTest.bind(this)
        });

        panelButtons.push({
            text: t("send_newsletter"),
            iconCls: "pimcore_icon_send",
            handler: this.send.bind(this)
        });

        panelButtons.push({
            text: t("save"),
            iconCls: "pimcore_icon_apply",
            handler: this.save.bind(this)
        }); 

        var store;

        if(pimcore.settings.google_analytics_enabled) {
            store = new Ext.data.Store({
                proxy: {
                    type: 'ajax',
                    url: '/admin/reports/analytics/chartmetricdata',
                    extraParams: {
                        "metric[]": "visits",
                        filters: "ga:campaign==" + this.data.name + ";ga:medium==Email;ga:source==Newsletter"
                    },
                    reader: {
                        type: 'json',
                        rootProperty: 'data'
                    }
                },
                autoDestroy: true,
                autoLoad: true,
                fields: ['timestamp','datetext','visits']
            });
        } else {
            store = new Ext.data.ArrayStore({
                autoDestroy: true,
                autoLoad: true,
                data: [],
                fields: ['timestamp','datetext','visits']
            });
        }

        this.analytics = new Ext.panel.Panel(
            {
            border: false,
            layout: 'fit',
            items: [{
                xtype: 'fieldset',
                hidden: !this.getAnalyticsVisiblity(),
                title: t("google_analytics"),
                items: [
                    {
                    xtype: 'cartesian',
                    store: store,
                    height: 240,
                    axes: [{
                        type: 'numeric',
                        fields: ['visits' ],
                        position: 'left',
                        grid: true,
                        minimum: 0
                    }
                        , {
                            type: 'category',
                            fields: 'datetext',
                            position: 'bottom',
                            grid: true,
                            label: {
                                rotate: {
                                    degrees: -45
                                }
                            }
                        }
                    ],
                    series: [
                        {
                            type:'line',
                            displayName: t("visits"),
                            xField: 'datetext',
                            yField: 'visits',
                            style: {
                                lineWidth: 2,
                                stroke: '#15428B',
                                fill: '#15428B'
                            },
                            marker: {
                                radius: 4
                            }
                        }
                    ]
                }
            ]}],
            buttons: [{
                text: t("show_in_google_anaytics"),
                iconCls: "pimcore_icon_analytics",
                handler: function () {
                    var analyticsUrl = "#report/trafficsources-campaigns/a{accountId}w{internalWebPropertyId}p{id}/"
                        + "%3F_r.drilldown%3Danalytics.campaign%3A" + this.data.name
                                                                    + "%2Canalytics.sourceMedium%3AEmail/";
                    window.open("/admin/reports/analytics/deeplink?url=" + encodeURIComponent(analyticsUrl));
                }.bind(this)
            }]
        });

        this.statusPanel = new Ext.form.FieldSet({
            hidden: true,
            title: t("status"),
            items: [{
                xtype: "displayfield",
                itemId: "progress",
                fieldLabel: t("progress")
            }, {
                xtype: "displayfield",
                itemId: "start",
                fieldLabel: t("start")
            }, {
                xtype: "displayfield",
                itemId: "lastUpdate",
                fieldLabel: t("last_update")
            }],
            buttons: [{
                iconCls: "pimcore_icon_stop",
                text: t("stop"),
                handler: function () {
                    Ext.Ajax.request({
                        url: "/admin/reports/newsletter/stop-send",
                        params: {
                            name: this.data.name
                        },
                        success: function (response) {
                            this.updateStatus();
                        }.bind(this)
                    });
                }.bind(this)
            }]
        });

        this.form = new Ext.form.FormPanel({
            region: "center",
            bodyStyle: "padding:10px",
            labelWidth: 150,
            autoScroll: true,
            border:false,
            items: [{
                xtype: "fieldset",
                title: t("general"),
                collapsible: false,
                items: [{
                    xtype: "textfield",
                    name: "name",
                    value: this.data.name,
                    fieldLabel: t("name"),
                    width: 450,
                    disabled: true
                },{
                    xtype: "textarea",
                    name: "description",
                    value: this.data.description,
                    fieldLabel: t("description"),
                    width: 450,
                    height: 50
                },{
                    xtype: "textfield",
                    name: "document",
                    value: this.data.document,
                    fieldLabel: t("document"),
                    width: 450,
                    cls: "input_drop_target",
                    enableKeyEvents: true,
                    listeners: {
                        "render": function (el) {
                            new Ext.dd.DropZone(el.getEl(), {
                                reference: el,
                                ddGroup: "element",
                                getTargetFromEvent: function(e) {
                                    return this.getEl();
                                }.bind(el),

                                onNodeOver : function(target, dd, e, data) {
                                    return Ext.dd.DropZone.prototype.dropAllowed;
                                },

                                onNodeDrop : function (el, target, dd, e, data) {
                                    var record = data.records[0];
                                    if (record.data.elementType == "document") {
                                        el.setValue(record.data.path);
                                        return true;
                                    }
                                    return false;
                                }.bind(this, el)
                            });
                        }.bind(this)
                    }
                },{
                    xtype: "combo",
                    name: "class",
                    fieldLabel: t("class"),
                    value: this.data["class"],
                    triggerAction: 'all',
                    editable: false,
                    store: this.data.availableClasses,
                    width: 280

                },{
                    xtype: "textfield",
                    name: "objectFilterSQL",
                    value: this.data.objectFilterSQL,
                    fieldLabel: t("object_filter") + " (SQL)",
                    width: 450,
                    itemId: "objectFilterSQL",
                    enableKeyEvents: true,
                    listeners: {
                        keyup: function (el) {

                            Ext.Ajax.request({
                                url: "/admin/reports/newsletter/checksql",
                                params: this.form.getForm().getFieldValues(),
                                success: function (response) {
                                    var res = Ext.decode(response.responseText);

                                    if(!this.sqlTooltip) {
                                        this.sqlTooltip = new Ext.ToolTip({
                                            title: '',
                                            target: el.getEl(),
                                            anchor: 'left',
                                            html: '',
                                            width: 140,
                                            height: 50,
                                            autoHide: false,
                                            closable: false
                                        });
                                        this.sqlTooltip.show();
                                    }

                                    if(res.success) {
                                        this.sqlTooltip.setTitle("OK");
                                        this.sqlTooltip.update( res.count + " " + t("recipients"));
                                    } else {
                                        this.sqlTooltip.setTitle(t("error"));
                                        this.sqlTooltip.update(t("error"));
                                    }
                                }.bind(this)
                            });
                        }.bind(this)
                    }
                },{
                    fieldLabel: t('associate_target_group') + " (" + t("personas") + ")",
                    xtype: "multiselect",
                    hidden: pimcore.globalmanager.get("personas").getCount() < 1,
                    store: pimcore.globalmanager.get("personas"),
                    displayField: "text",
                    valueField: "id",
                    name: 'personas',
                    width: 300,
                    value: this.data["personas"]
                }, {
                    xtype: "textfield",
                    name: "testEmailAddress",
                    value: this.data.testEmailAddress,
                    fieldLabel: t("test_email_address"),
                    width: 450
                },{
                    xtype: "checkbox",
                    name: "googleAnalytics",
                    checked: this.data.googleAnalytics,
                    fieldLabel: t("google_analytics"),
                    handler: function () {
                        if(this.getAnalyticsVisiblity()) {
                            this.analytics.show();
                        } else {
                            this.analytics.hide();
                        }
                    }.bind(this)
                },{
                    xtype: "displayfield",
                    hideLabel: true,
                    width: 600,
                    value: t("source") + ":Newsletter, " + t("medium") + ":Email, " + t("name") + ":" + this.data.name,
                    cls: "pimcore_extra_label_bottom"
                }]
            }, this.statusPanel, this.analytics]
        });

        this.panel = new Ext.Panel({
            border: false,
            layout: "border",
            closable: true,
            //bodyStyle: "padding: 20px;",
            title: this.data.name,
            id: "pimcore_newsletter_panel_" + this.data.name,
            items: [this.form],
            buttons: panelButtons,
            listeners: {
                destroy: function () {
                    clearInterval(this.updateStatusInterval);
                }.bind(this)
            }
        });

        this.parentPanel.getEditPanel().add(this.panel);
        this.parentPanel.getEditPanel().setActiveItem(this.panel);

        pimcore.layout.refresh();

        // start update interval
        this.updateStatusInterval = window.setInterval(this.updateStatus.bind(this), 5000);

        // do it once manually to get immediately the status
        this.updateStatus();
    },

    updateStatus: function () {

        Ext.Ajax.request({
            url: "/admin/reports/newsletter/get-send-status",
            params: {
                name: this.data.name
            },
            success: function (response) {
                var res = Ext.decode(response.responseText);
                if(res["data"]) {
                    this.statusPanel.show();

                    try {
                        var lastUpdate = new Date(res["data"]["lastUpdate"] * 1000);
                        var start = new Date(res["data"]["start"] * 1000);

                        this.statusPanel.getComponent("progress").setValue(res["data"]["current"] + " / " + res["data"]["total"]);
                        this.statusPanel.getComponent("start").setValue(start.format("Y-m-d H:i:s"));
                        this.statusPanel.getComponent("lastUpdate").setValue(lastUpdate.format("Y-m-d H:i:s"));
                    } catch (e) {
                        clearInterval(this.updateStatusInterval);
                    }
                } else {
                    this.statusPanel.hide();
                }
            }.bind(this)
        });
    },

    save: function () {

        var m = Ext.encode(this.form.getForm().getFieldValues());
        Ext.Ajax.request({
            url: "/admin/reports/newsletter/update",
            method: "post",
            params: {
                configuration: m,
                name: this.data.name
            },
            success: this.saveOnComplete.bind(this)
        });
    },

    saveOnComplete: function () {
        var tree = this.parentPanel.tree;
        tree.getStore().load({
            node: tree.getRootNode()
        });
        pimcore.helpers.showNotification(t("success"), t("saved_successfully"), "success");
    },

    send: function () {
        Ext.MessageBox.confirm(t("are_you_sure"), t("do_you_really_want_to_send_the_newsletter_to_all_recipients"), function (buttonValue) {

            if (buttonValue == "yes") {
                Ext.Ajax.request({
                    url: "/admin/reports/newsletter/send",
                    method: "post",
                    params: {
                        name: this.data.name
                    },
                    success: function (response) {
                        var res = Ext.decode(response.responseText);

                        if(res.success) {
                            Ext.MessageBox.alert(t("info"), t("newsletter_sent_message"))
                        } else {
                            Ext.MessageBox.alert(t("error"), t("newsletter_send_error"))
                        }
                    }
                });
            }
        }.bind(this))
    },

    sendTest: function () {
        Ext.Ajax.request({
            url: "/admin/reports/newsletter/send-test",
            method: "post",
            params: {
                name: this.data.name
            }
        });
    }
});
