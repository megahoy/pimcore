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

pimcore.registerNS("pimcore.layout.portlets.customreports");
pimcore.layout.portlets.customreports = Class.create(pimcore.layout.portlets.abstract, {

    getType: function () {
        return "pimcore.layout.portlets.customreports";
    },


    getName: function () {
        return t("portlet_customreport");
    },

    getIcon: function () {
        return "pimcore_icon_portlet_custom_reports";
    },

    getLayout: function (portletId) {

        var defaultConf = this.getDefaultConfig();

        defaultConf.tools = [
            {
                type:'search',
                handler: this.openReport.bind(this)
            },
            {
                type:'gear',
                handler: this.editSettings.bind(this)
            },
            {
                type:'close',
                handler: this.remove.bind(this)
            }
        ];

        this.layout = Ext.create('Portal.view.Portlet', Object.extend(defaultConf, {
            title: this.getName(),
            iconCls: this.getIcon(),
            height: 275,
            layout: "fit",
            items: []
        }));

        this.updateChart();

        this.layout.portletId = portletId;
        return this.layout;
    },

    editSettings: function () {
        var win = new Ext.Window({
            width: 600,
            height: 100,
            modal: true,
            title: t('portlet_customreport_settings'),
            closeAction: "destroy",
            items: [
                {
                    xtype: "form",
                    bodyStyle: "padding: 10px",
                    items: [
                        {
                            xtype:"combo",
                            id: "pimcore_portlet_selected_custom_report",
                            autoSelect: true,
                            valueField: "id",
                            displayField: "text",
                            value: this.config,
                            fieldLabel: t("portlet_customreport"),
                            store: new Ext.data.Store({
                                autoDestroy: true,
                                proxy: {
                                    type: 'ajax',
                                    url: '/admin/reports/custom-report/tree',
                                    extraParams: {
                                        portlet: 1
                                    },
                                    reader: {
                                        type: 'json',
                                        rootProperty: 'data'
                                    }
                                },
                                fields: ['id','text']
                            }),
                            triggerAction: "all"
                        },
                        {
                            xtype: "button",
                            text: t("save"),
                            handler: function () {
                                this.updateSettings();
                                win.close();
                            }.bind(this)
                        }
                    ]
                }
            ]
        });

        win.show();
    },

    updateSettings: function() {
        this.config = Ext.getCmp("pimcore_portlet_selected_custom_report").getValue();
        Ext.Ajax.request({
            url: "/admin/portal/update-portlet-config",
            params: {
                key: this.portal.key,
                id: this.layout.portletId,
                config: Ext.getCmp("pimcore_portlet_selected_custom_report").getValue()
            },
            success: function () {
                this.updateChart();
            }.bind(this)
        });
    },

    updateChart: function() {
        if(this.config) {
            Ext.Ajax.request({
                url: "/admin/reports/custom-report/get",
                params: {
                    name: this.config
                },
                success: function (response) {
                    var data = Ext.decode(response.responseText);

                    var chartPanel = this.getChart(data);
                    this.layout.removeAll();
                    if(chartPanel) {
                        this.layout.add(chartPanel);
                    }

                    this.layout.setTitle(t("portlet_customreport") + ": " + data.niceName);
                    if(data.iconClass) {
                        this.layout.setIconCls(data.iconClass);
                    } else {
                        this.layout.setIconCls(this.getIcon());
                    }

                    this.reportConfig = data;

                    this.layout.updateLayout();
                }.bind(this)
            });
        }
    },

    chartColors: [
        0x01841c,
        0x3D32FF,
        0xFF1000,
        0xFFEE00,
        0x00FF21,
        0x7F92FF,
        0xFFD800
    ],

    getChart: function(data) {
        var chartPanel = null;

        var columnLabels = {};
        var colConfig;

        for(var f=0; f<data.columnConfiguration.length; f++) {
            colConfig = data.columnConfiguration[f];
            columnLabels[colConfig["name"]] = colConfig["label"] ? ts(colConfig["label"]) : ts(colConfig["name"]);
        }


        if(data.chartType == 'line' || data.chartType == 'bar') {
            var storeFields = [];
            storeFields.push(data.xAxis);
            for(var i = 0; i < data.yAxis.length; i++) {
                storeFields.push(data.yAxis[i]);
            }

            var chartStore = new Ext.data.Store({
                autoDestroy: true,
                proxy: {
                    type: 'ajax',
                    url: "/admin/reports/custom-report/chart",
                    extraParams: {
                        name: this.config
                    },
                    reader: {
                        type: 'json',
                        rootProperty: 'data'
                    }
                },
                fields: storeFields
            });
            chartStore.load();

            var series = [];
            for(var i = 0; i < data.yAxis.length; i++) {
                series.push({
                    displayName: columnLabels[data.yAxis[i]],
                    type: (data.chartType == 'line' ? 'line' : 'column'),
                    yField: data.yAxis[i],
                    style: {
                        color: this.chartColors[i]
                    }
                });
            }

            chartPanel = new Ext.Panel({
                id:"cartID",
                region: "north",
                height: 350,
                border: false,
                items: [{
                    xtype: (data.chartType == 'line' ? 'cartesian' : 'columnchart'),
                    store: chartStore,
                    xField: data.xAxis,
                    chartStyle: {
                        padding: 10,
                        legend: {
                            display: 'bottom'
                        }
                    },
                    series: series
                }]
            });
        } else if(data.chartType == 'pie') {
            var chartStore = new Ext.data.Store({
                autoDestroy: true,
                proxy: {
                    type: 'ajax',
                    url: "/admin/reports/custom-report/chart",
                    extraParams: {
                        name: this.config
                    },
                    reader: {
                        type: 'json',
                        rootProperty: 'data'
                    }
                },
                fields: [data.pieLabelColumn, data.pieColumn]
            });
            chartStore.load();

            chartPanel = new Ext.Panel({
                region: "north",
                height: 350,
                border: false,
                items: [{
                    store: chartStore,
                    xtype: 'piechart',
                    dataField: data.pieColumn,
                    categoryField: data.pieLabelColumn,
                    chartStyle: {
                        padding: 10,
                        legend: {
                            display: 'right'
                        }
                    }
                }]
            });
        }

        return chartPanel;
    },

    openReport: function() {
        var toolbar = pimcore.globalmanager.get("layout_toolbar");
        toolbar.showReports(pimcore.report.custom.report, {
            name: this.reportConfig.name,
            text: this.reportConfig.niceName,
            niceName: this.reportConfig.niceName,
            iconCls: this.reportConfig.iconClass
        });

    }

});
