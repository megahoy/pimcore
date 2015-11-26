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

pimcore.registerNS("pimcore.layout.portlets.feed");
pimcore.layout.portlets.feed = Class.create(pimcore.layout.portlets.abstract, {

    getType: function () {
        return "pimcore.layout.portlets.feed";
    },


    getName: function () {
        return t("feed");
    },

    getIcon: function () {
        return "pimcore_icon_portlet_feed";
    },

    getLayout: function (portletId) {

        this.store = new Ext.data.Store({
            autoDestroy: true,
            proxy: {
                type: 'ajax',
                url: '/admin/portal/portlet-feed',
                extraParams: {
                    key: this.portal.key,
                    id: portletId
                },
                reader: {
                    type: 'json',
                    rootProperty: 'entries'
                }
            },
            fields: ['id','title',"description",'date',"link","content"]
        });

        this.store.load();

        var grid = new Ext.grid.GridPanel({
            store: this.store,
            columns: [
                {header: t('title'), id: "title", sortable: false, dataIndex: 'title', flex: 1}
            ],
            stripeRows: true
        });

        grid.on("rowclick", this.openDetail.bind(this));

        var defaultConf = this.getDefaultConfig();

        defaultConf.tools = [
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
            items: [grid]
        }));

        this.layout.portletId = portletId;
        return this.layout;
    },

    editSettings: function () {
        var win = new Ext.Window({
            width: 600,
            height: 100,
            modal: true,
            closeAction: "destroy",
            items: [
                {
                    xtype: "form",
                    bodyStyle: "padding: 10px",
                    items: [
                        {
                            xtype: "textfield",
                            name: "url",
                            id: "pimcore_portlet_feed_url",
                            fieldLabel: "Feed-URL",
                            value: this.config,
                            width: 520
                        },
                        {
                            xtype: "button",
                            text: t("save"),
                            handler: function () {
                                this.config = Ext.getCmp("pimcore_portlet_feed_url").getValue();
                                Ext.Ajax.request({
                                    url: "/admin/portal/update-portlet-config",
                                    params: {
                                        key: this.portal.key,
                                        id: this.layout.portletId,
                                        config: Ext.getCmp("pimcore_portlet_feed_url").getValue()
                                    },
                                    success: function () {
                                        this.store.reload();
                                    }.bind(this)
                                });
                                win.close();
                            }.bind(this)
                        }
                    ]
                }
            ]
        });

        win.show();
    },

    openDetail: function (grid, record, tr, rowIndex, e, eOpts ) {

        var content = '<h1>' + record.data.title + '</h1><br /><br />' + record.data.content + '<br /><br />';

        var win = new Ext.Window({
            width: 650,
            height: 500,
            modal: true,
            bodyStyle: "background: #fff; padding: 20px;",
            autoScroll: true,
            html: content,
            closeAction: "close",
            buttons: [
                {
                    text: t("view_original"),
                    handler: function () {
                        window.open(record.data.link);
                    }
                }
            ]
        });

        win.show();
    }
});
