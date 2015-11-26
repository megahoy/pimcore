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

pimcore.registerNS("pimcore.object.classes.data.date");
pimcore.object.classes.data.date = Class.create(pimcore.object.classes.data.data, {

    type:"date",
    /**
     * define where this datatype is allowed
     */
    allowIn:{
        object:true,
        objectbrick:true,
        fieldcollection:true,
        localizedfield:true
    },

    initialize:function (treeNode, initData) {
        this.type = "date";

        this.initData(initData);

        this.treeNode = treeNode;
    },

    getTypeName:function () {
        return t("date");
    },

    getGroup:function () {
        return "date";
    },

    getIconClass:function () {
        return "pimcore_icon_date";
    },

    getLayout:function ($super) {


        $super();

        var date = {
            fieldLabel:t("default_value"),
            name:"defaultValue",
            itemCls:"object_field",
            width: 300,
            disabled: this.datax.useCurrentDate
        };

        if (this.datax.defaultValue) {
            var tmpDate;
            if(typeof this.datax.defaultValue === 'object'){
                tmpDate = this.datax.defaultValue;
            } else {
                tmpDate = new Date(this.datax.defaultValue * 1000);
            }

            date.value = tmpDate;
        }

        this.component = new Ext.form.DateField(date);


        this.specificPanel.removeAll();
        this.specificPanel.add([
            this.component,
            {
                xtype:"checkbox",
                fieldLabel:t("use_current_date"),
                name:"useCurrentDate",
                value:this.datax.defaultValue,
                checked: this.datax.useCurrentDate,
                listeners:{
                    check:this.toggleDefaultDate.bind(this)
                },
                disabled: this.isInCustomLayoutEditor()
            }, {
                xtype: "panel",
                bodyStyle: "padding-top: 3px",
                style: "margin-bottom: 10px",
                html:'<span class="object_field_setting_warning">' +t('default_value_warning')+'</span>'
            }

        ]);

        return this.layout;
    },

    toggleDefaultDate:function (checkbox, checked) {
        if (checked) {
            this.component.setValue(null);
            this.component.setDisabled(true);
        } else {
            this.component.enable();
        }
    },

    applySpecialData: function(source) {
        if (source.datax) {
            if (!this.datax) {
                this.datax =  {};
            }
            Ext.apply(this.datax,
                {
                    defaultValue: source.datax.defaultValue,
                    useCurrentDate: source.datax.useCurrentDate
                });
        }
    }

});
