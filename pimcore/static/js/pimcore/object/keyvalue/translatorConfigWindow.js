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

pimcore.registerNS("pimcore.object.keyvalue.translatorconfigwindow");
pimcore.object.keyvalue.translatorconfigwindow = Class.create({

    initialize: function (keyid, parentPanel, groupId) {
        this.parentPanel = parentPanel;
        this.keyid = keyid;
        this.groupId = groupId;
    },


    show: function() {
        this.window = new Ext.Window({
            layout:'fit',
            width:500,
            height:310,
            autoScroll: true,
            closeAction:'close',
            modal: true
        });

        this.window.show();

        Ext.Ajax.request({
            url: "/admin/key-value/get-translator-configs",
            success: this.selectTranslator.bind(this),
            failure: function() {
                this.window.hide();
            }.bind(this)
        });
    },

    selectTranslator: function (response) {
        var availableTranslators = Ext.decode(response.responseText);

        var panelConfig = {
//            title: t('select_keyvalue_translator'),
            items: []
        };

        var storeConfigs = new Ext.data.JsonStore({
            autoDestroy: true,
            root: 'configurations',
            data: availableTranslators,
            idProperty: 'id',
            fields: ["id","name","translator"]
        });

        panelConfig.items.push({
            xtype: "form",
            bodyStyle: "padding: 10px;",
            title: t('keyvalue_translators'),
            items: [
                {
                    xtype: "combo",
                    fieldLabel: t('keyvalue_select_translator'),
                    name: "translator",
                    id: "translator",
                    mode: "local",
                    width: 250,
                    store: storeConfigs,
                    triggerAction: "all",
                    displayField: "name",
                    valueField: "id",
                    value: this.groupId
                }
            ],
            bbar: ["->",
                {
                    xtype: "button",
                    iconCls: "pimcore_icon_apply",
                    text: t('apply'),
                    handler: this.applyData.bind(this)
                }
            ]
        });

        this.window.add(new Ext.Panel(panelConfig));
        this.window.doLayout();
    },


    applyData: function() {
        var value = Ext.getCmp("translator").getValue();
        this.parentPanel.applyTranslatorConfig(this.keyid, value);
        this.window.close();
    }
});