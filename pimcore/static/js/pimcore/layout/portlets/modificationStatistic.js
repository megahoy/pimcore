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

pimcore.registerNS("pimcore.layout.portlets.modificationStatistic");
pimcore.layout.portlets.modificationStatistic = Class.create(pimcore.layout.portlets.abstract, {

    getType: function () {
        return "pimcore.layout.portlets.modificationStatistic";
    },

    getName: function () {
        return t("modification_statistic");
    },

    getIcon: function () {
        return "pimcore_icon_portlet_modification_statistic";
    },

    getLayout: function (portletId) {

        var store = new Ext.data.JsonStore({
            autoDestroy: true,
            url: '/admin/portal/portlet-modification-statistics',
            root: 'data',
            fields: ['timestamp','datetext',"objects",'documents',"assets"]
        });

        store.load();


        var panel = new Ext.Panel({
            layout:'fit',
            height: 275,
            items: {
                xtype: 'linechart',
                store: store,
                xField: 'datetext',
                series: [
                    {
                        type: 'line',
                        displayName: t('documents'),
                        yField: 'documents',
                        style: {
                            color:0x01841c
                        }
                    },
                    {
                        type:'line',
                        displayName: t('assets'),
                        yField: 'assets',
                        style: {
                            color: 0x15428B
                        }
                    },
                    {
                        type:'line',
                        displayName: t('objects'),
                        yField: 'objects',
                        style: {
                            color: 0xff6600
                        }
                    }
                ]
            }
        });


        this.layout = new Ext.ux.Portlet(Object.extend(this.getDefaultConfig(), {
            title: this.getName(),
            iconCls: this.getIcon(),
            height: 275,
            layout: "fit",
            items: [panel]
        }));

        this.layout.portletId = portletId;
        return this.layout;
    }
});
