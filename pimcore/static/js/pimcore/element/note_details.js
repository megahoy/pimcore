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

pimcore.registerNS("pimcore.element.note_details");
pimcore.element.note_details = Class.create({
    getClassName: function (){
        return "pimcore.element.note_details";
    },

    initialize: function (data) {
        this.data = data;
        this.getInputWindow();
        this.detailWindow.show();
    },


    getInputWindow: function () {

        if(!this.detailWindow) {
            this.detailWindow = new Ext.Window({
                width: 700,
                height: 422,
                title: t('note_details'),
                closeAction:'close',
                plain: true,
                maximized: false,
                autoScroll: true,
                modal: true,
                buttons: [
                    {
                        text: t('close'),
                        iconCls: "pimcore_icon_empty",
                        handler: function(){
                            this.detailWindow.hide();
                            this.detailWindow.destroy();
                        }.bind(this)
                    }
                ]
            });

            this.createPanel();
        }
        return this.detailWindow;
    },


    createPanel: function() {
        var items = [];

        items.push({
            xtype: "textfield",
            fieldLabel: t('type'),
            readOnly: true,
            value: this.data.type,
            width: 500
        });

        items.push({
            xtype: "textfield",
            fieldLabel: t('title'),
            readOnly: true,
            value: this.data.title,
            width: 500
        });

        items.push({
            xtype: "textarea",
            fieldLabel: t('description'),
            readOnly: true,
            value: this.data.description,
            width: 500,
            height: 200
        });


        var v;
        if(this.data.data) {
            v =  this.data.data.length;
        } else {
            v = "";
        }

        items.push(
            {
                xtype: "textfield",
                fieldLabel: t('fields'),
                readOnly: true,
                value: v,
                width: 500
            }
        );

        var user;
        if(this.data.user && this.data.user["name"]) {
            user =  this.data.user["name"];
        } else {
            user = "";
        }




        items.push(
            {
                xtype: "textfield",
                fieldLabel: t('user'),
                readOnly: true,
                value: user,
                width: 500
            }
        );

        var date = new Date(this.data.date * 1000);

        items.push(
            {
                xtype: "textfield",
                fieldLabel: t('date'),
                readOnly: true,
                value: date.format("Y-m-d H:i:s"),
                width: 500
            }
        );

        var panel = new Ext.form.FormPanel({
            border: false,
            frame:false,
            bodyStyle: 'padding:10px',
            items: items,
            labelWidth: 130,
            collapsible: false,
            autoScroll: true
        });

        this.detailWindow.add(panel);
    }

});