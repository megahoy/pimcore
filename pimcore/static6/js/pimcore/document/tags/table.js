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

pimcore.registerNS("pimcore.document.tags.table");
pimcore.document.tags.table = Class.create(pimcore.document.tag, {

    initialize: function(id, name, options, data, inherited) {

        this.id = id;
        this.name = name;
        this.setupWrapper();
        options = this.parseOptions(options);

        if (!data) {
            data = [
                [" "]
            ];
            if (options.defaults) {
                if (options.defaults.cols) {
                    for (var i = 0; i < (options.defaults.cols - 1); i++) {
                        data[0].push(" ");
                    }
                }
                if (options.defaults.rows) {
                    for (var i = 0; i < (options.defaults.rows - 1); i++) {
                        data.push(data[0]);
                    }
                }
                if (options.defaults.data) {
                    data = options.defaults.data;
                }
            }
        }

        options.value = data;
        options.name = id + "_editable";
        options.frame = true;
        options.layout = "fit";
        options.autoHeight = true;

        delete options["height"];

        this.options = options;

        if (!this.panel) {
            this.panel = new Ext.Panel(this.options);
        }

        this.panel.render(id);

        this.initStore(data);

        this.initGrid();
    },

    initGrid: function () {

        this.panel.removeAll();

        var data = this.store.queryBy(function(record, id) {
            return true;
        });
        var columns = [];

        var fields = this.store.getInitialConfig().fields;

        if (data.items[0]) {
            for (var i = 0; i < fields.length; i++) {
                columns.push({
                    dataIndex: fields[i].name,
                    editor: new Ext.form.TextField({
                        allowBlank: true
                    }),
                    sortable: false
                });
            }
        }

        this.cellEditing = Ext.create('Ext.grid.plugin.CellEditing', {
            clicksToEdit: 1
        });

        this.grid = Ext.create('Ext.grid.Panel', {
            store: this.store,
            width: 700,
            border: false,
            columns:columns,
            stripeRows: true,
            columnLines: true,
            selModel: Ext.create('Ext.selection.CellModel'),
            autoHeight: true,
            plugins: [
                this.cellEditing
            ],
            tbar: [
                {
                    iconCls: "pimcore_tag_table_addcol",
                    handler: this.addColumn.bind(this)
                },
                {
                    iconCls: "pimcore_tag_table_delcol",
                    handler: this.deleteColumn.bind(this)
                },
                {
                    iconCls: "pimcore_tag_table_addrow",
                    handler: this.addRow.bind(this)
                },
                {
                    iconCls: "pimcore_tag_table_delrow",
                    handler: this.deleteRow.bind(this)
                },
                {
                    iconCls: "pimcore_tag_table_empty",
                    handler: this.initStore.bind(this, [
                        [" "]
                    ])
                }
            ]
        });
        this.panel.add(this.grid);
        this.panel.updateLayout();
    },

    initStore: function (data) {
        var storeFields = [];
        if (data[0]) {
            for (var i = 0; i < data[0].length; i++) {
                storeFields.push({
                    name: "col_" + i
                });
            }
        }

        this.store = new Ext.data.ArrayStore({
            fields: storeFields
        });

        this.store.loadData(data);
        this.initGrid();
    },

    addColumn : function  () {

        var currentData = this.getValue();

        for (var i = 0; i < currentData.length; i++) {
            currentData[i].push(" ");
        }

        this.initStore(currentData);
    },

    addRow: function  () {
        var initData = {};

        var columnnManager = this.grid.getColumnManager();
        var columns = columnnManager.getColumns();

        for (var o = 0; o < columns.length; o++) {
            initData["col_" + o] = " ";
        }

        this.store.add(initData);
    },

    deleteRow : function  () {
        var selected = this.grid.getSelectionModel();
        if (selected.selection) {
            this.store.remove(selected.selection.record);
        }
    },

    deleteColumn: function () {
        var selected = this.grid.getSelectionModel();

        if (selected.selection) {
            var column = selected.selection.colIdx;

            var currentData = this.getValue();

            for (var i = 0; i < currentData.length; i++) {
                currentData[i].splice(column, 1);
            }

            this.initStore(currentData);
        }
    },

    getValue: function () {
        var data = this.store.queryBy(function(record, id) {
            return true;
        });

        var fields = this.store.getInitialConfig().fields;

        var storedData = [];
        var tmData = [];
        for (var i = 0; i < data.items.length; i++) {
            tmData = [];

            for (var u = 0; u < fields.length; u++) {
                tmData.push(data.items[i].data[fields[u].name]);
            }
            storedData.push(tmData);
        }

        return storedData;
    },

    getType: function () {
        return "table";
    }
});