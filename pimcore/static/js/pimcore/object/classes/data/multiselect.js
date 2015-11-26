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

pimcore.registerNS("pimcore.object.classes.data.multiselect");
pimcore.object.classes.data.multiselect = Class.create(pimcore.object.classes.data.data, {

    type: "multiselect",
    /**
     * define where this datatype is allowed
     */
    allowIn: {
        object: true,
        objectbrick: true,
        fieldcollection: true,
        localizedfield: true
    },

    initialize: function (treeNode, initData) {
        this.type = "multiselect";

        this.initData(initData);

        // overwrite default settings
        this.availableSettingsFields = ["name","title","tooltip","mandatory","noteditable","invisible",
            "visibleGridView","visibleSearch","style"];

        this.treeNode = treeNode;
    },

    getTypeName: function () {
        return t("multiselect");
    },

    getGroup: function () {
        return "select";
    },

    getIconClass: function () {
        return "pimcore_icon_multiselect";
    },

    getLayout: function ($super) {

        if(typeof this.datax.options != "object") {
            this.datax.options = [];
        }

        this.valueStore = new Ext.data.JsonStore({
            fields: ["key", "value"],
            data: this.datax.options
        });

        this.valueGrid = new Ext.grid.EditorGridPanel({
            enableDragDrop: true,
            ddGroup: 'objectclassmultiselect',
            tbar: [{
                xtype: "tbtext",
                text: t("selection_options")
            }, "-", {
                xtype: "button",
                iconCls: "pimcore_icon_add",
                handler: function () {
                    var u = new this.valueStore.recordType({
                        key: "",
                        value: ""
                    });

                    var selectedRow = this.selectionModel.getSelected();
                    var idx;
                    if (selectedRow) {
                        idx = this.valueStore.indexOf(selectedRow) + 1;
                    } else {
                        idx = this.valueStore.getCount();
                    }
                    this.valueStore.insert(idx, u);
                    this.selectionModel.selectRow(idx);
                }.bind(this)
            },
                {
                    xtype: "button",
                    iconCls: "pimcore_icon_tab_edit",
                    handler: this.showoptioneditor.bind(this)

                }
            ],
            style: "margin-top: 10px",
            store: this.valueStore,
            disabled: this.isInCustomLayoutEditor(),
            selModel:new Ext.grid.RowSelectionModel({singleSelect:true}),
            columnLines: true,
            columns: [
                {header: t("display_name"), sortable: true, dataIndex: 'key', editor: new Ext.form.TextField({}),
                    width: 200},
                {header: t("value"), sortable: true, dataIndex: 'value', editor: new Ext.form.TextField({}),
                    width: 200},
                {
                    xtype:'actioncolumn',
                    width:30,
                    items:[
                        {
                            tooltip:t('up'),
                            icon:"/pimcore/static/img/icon/arrow_up.png",
                            handler:function (grid, rowIndex) {
                                if (rowIndex > 0) {
                                    var rec = grid.getStore().getAt(rowIndex);
                                    grid.getStore().removeAt(rowIndex);
                                    grid.getStore().insert(--rowIndex, [rec]);
                                    var sm = this.valueGrid.getSelectionModel();
                                    this.selectionModel.selectRow(rowIndex);
                                }
                            }.bind(this)
                        }
                    ]
                },
                {
                    xtype:'actioncolumn',
                    width:30,
                    items:[
                        {
                            tooltip:t('down'),
                            icon:"/pimcore/static/img/icon/arrow_down.png",
                            handler:function (grid, rowIndex) {
                                if (rowIndex < (grid.getStore().getCount() - 1)) {
                                    var rec = grid.getStore().getAt(rowIndex);
                                    grid.getStore().removeAt(rowIndex);
                                    grid.getStore().insert(++rowIndex, [rec]);
                                    var sm = this.valueGrid.getSelectionModel();
                                    this.selectionModel.selectRow(rowIndex);
                                }
                            }.bind(this)
                        }
                    ]
                },
                {
                    xtype: 'actioncolumn',
                    width: 30,
                    items: [
                        {
                            tooltip: t('remove'),
                            icon: "/pimcore/static/img/icon/cross.png",
                            handler: function (grid, rowIndex) {
                                grid.getStore().removeAt(rowIndex);
                            }.bind(this)
                        }
                    ]
                }
            ],
            autoHeight: true
        });

        this.selectionModel = this.valueGrid.getSelectionModel();;
        this.valueGrid.on("afterrender", function () {

            var dropTargetEl = this.valueGrid.getEl();
            var gridDropTarget = new Ext.dd.DropZone(dropTargetEl, {
                ddGroup    : 'objectclassmultiselect',
                getTargetFromEvent: function(e) {
                    return this.valueGrid.getEl().dom;
                }.bind(this),
                onNodeOver: function (overHtmlNode, ddSource, e, data) {
                    if(data["grid"] && data["grid"] == this.valueGrid) {
                        return Ext.dd.DropZone.prototype.dropAllowed;
                    }
                    return Ext.dd.DropZone.prototype.dropNotAllowed;
                }.bind(this),
                onNodeDrop : function(target, dd, e, data) {
                    if(data["grid"] && data["grid"] == this.valueGrid) {
                        var rowIndex = this.valueGrid.getView().findRowIndex(e.target);
                        if(rowIndex !== false) {
                            var store = this.valueGrid.getStore();
                            var rec = store.getAt(data.rowIndex);
                            store.removeAt(data.rowIndex);
                            store.insert(rowIndex, [rec]);
                        }
                    }
                    return false;
                }.bind(this)
            });
        }.bind(this));

        $super();

        this.specificPanel.removeAll();
        this.specificPanel.add([
            {
                xtype: "spinnerfield",
                fieldLabel: t("width"),
                name: "width",
                value: this.datax.width
            },
            {
                xtype: "spinnerfield",
                fieldLabel: t("height"),
                name: "height",
                value: this.datax.height
            },this.valueGrid
        ]);

        return this.layout;
    },

    applyData: function ($super) {

        $super();

        var options = [];

        this.valueStore.commitChanges();
        this.valueStore.each(function (rec) {
            options.push({
                key: rec.get("key"),
                value: rec.get("value")
            });
        });

        this.datax.options = options;
    },

    applySpecialData: function(source) {
        if (source.datax) {
            if (!this.datax) {
                this.datax =  {};
            }
            Ext.apply(this.datax,
                {
                    options: source.datax.options,
                    width: source.datax.width,
                    height: source.datax.height
                });
        }
    },

    showoptioneditor: function() {
        var editor = new pimcore.object.helpers.optionEditor(this.valueStore);
        editor.edit();
    }
});
