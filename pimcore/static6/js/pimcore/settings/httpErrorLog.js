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

pimcore.registerNS("pimcore.settings.httpErrorLog");
pimcore.settings.httpErrorLog = Class.create({

    initialize: function(id) {
        this.getTabPanel();
    },

    activate: function () {
        var tabPanel = Ext.getCmp("pimcore_panel_tabs");
        tabPanel.setActiveItem("pimcore_http_error_log");
    },

    getTabPanel: function () {

        if (!this.panel) {
            this.panel = new Ext.Panel({
                id: "pimcore_http_error_log",
                title: t("http_errors"),
                iconCls: "pimcore_icon_httperrorlog",
                border: false,
                layout: "fit",
                closable:true,
                items: [this.getGrid()]
            });

            var tabPanel = Ext.getCmp("pimcore_panel_tabs");
            tabPanel.add(this.panel);
            tabPanel.setActiveItem("pimcore_http_error_log");


            this.panel.on("destroy", function () {
                pimcore.globalmanager.remove("http_error_log");
            }.bind(this));

            pimcore.layout.refresh();
        }

        return this.panel;
    },


    getGrid: function () {

        var itemsPerPage = 20;
        var url = '/admin/misc/http-error-log?';

        this.store = pimcore.helpers.grid.buildDefaultStore(
            url,
            ["id","path", "code", "date","amount"],
            itemsPerPage
        );
        var proxy = this.store.getProxy();
        proxy.extraParams["group"] = 1;

        this.pagingtoolbar = pimcore.helpers.grid.buildDefaultPagingToolbar(this.store, itemsPerPage);

        var typesColumns = [
            {header: "ID", width: 50, sortable: true, hidden: true, dataIndex: 'id'},
            {header: "Code", width: 60, sortable: true, dataIndex: 'code'},
            {header: t("path"), width: 400, sortable: true, dataIndex: 'path'},
            {header: t("amount"), width: 60, sortable: true, dataIndex: 'amount'},
            {header: t("date"), width: 200, sortable: true, dataIndex: 'date',
                                                                    renderer: function(d) {
                var date = new Date(d * 1000);
                return Ext.Date.format(date, "Y-m-d H:i:s");
            }},
            {
                xtype: 'actioncolumn',
                width: 30,
                items: [{
                    tooltip: t('open'),
                    icon: "/pimcore/static6/img/icon/world_go.png",
                    handler: function (grid, rowIndex) {
                        var data = grid.getStore().getAt(rowIndex);
                        window.open(data.get("path"));
                    }.bind(this)
                }]
            }
        ];


        this.filterField = new Ext.form.TextField({
            xtype: "textfield",
            width: 200,
            style: "margin: 0 10px 0 0;",
            enableKeyEvents: true,
            listeners: {
                "keydown" : function (field, key) {
                    if (key.getKey() == key.ENTER) {
                        var input = field;
                        var val = input.getValue();
                        this.store.getProxy().extraParams.filter = val ? val : "";
                        this.store.load();
                    }
                }.bind(this)
            }
        });


        this.grid = new Ext.grid.GridPanel({
            frame: false,
            autoScroll: true,
            store: this.store,
            columns : typesColumns,
            autoExpandColumn: "path",
            trackMouseOver: true,
            bbar: this.pagingtoolbar,
            columnLines: true,
            stripeRows: true,
            listeners: {
                "rowdblclick": function (grid, record, tr, rowIndex, e, eOpts ) {
                    var data = grid.getStore().getAt(rowIndex);
                    var win = new Ext.Window({
                        closable: true,
                        width: 810,
                        autoDestroy: true,
                        height: 430,
                        modal: true,
                        bodyStyle: "background:#fff;",
                        html: '<iframe src="/admin/misc/http-error-log-detail?id=' + data.get("id")
                                            + '" frameborder="0" width="100%" height="390"></iframe>'
                    });
                    win.show();
                }
            },
            viewConfig: {
                forceFit: true
            },
            tbar: [{
                text: t("refresh"),
                iconCls: "pimcore_icon_reload",
                handler: this.reload.bind(this)
            }, "-",{
                text: t("group_by_path"),
                pressed: true,
                iconCls: "pimcore_icon_groupby",
                enableToggle: true,
                handler: function (button) {
                    this.store.getProxy().extraParams.group = button.pressed ? 1 : 0;
                    this.store.load();
                }.bind(this)
            }, "-",{
                text: t('flush'),
                handler: function () {
                    Ext.Ajax.request({
                        url: "/admin/misc/http-error-log-flush",
                        success: function () {
                            var input = field;
                            var proxy = this.store.getProxy();
                            proxy.extraParams.filter = input.getValue();
                            this.store.load();
                        }.bind(this)
                    });
                }.bind(this),
                iconCls: "pimcore_icon_flush_recyclebin"
            }, "-", {
                text: t("errors_from_the_last_14_days"),
                xtype: "tbtext"
            }, '-',"->",{
              text: t("filter") + "/" + t("search"),
              xtype: "tbtext",
              style: "margin: 0 10px 0 0;"
            },
            this.filterField]
        });

        return this.grid;
    },

    reload: function () {
        this.store.reload();
    }
});