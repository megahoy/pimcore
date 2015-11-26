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

        if (data.items[0]) {
            var keys = Object.keys(data.items[0].data);

            for (var i = 0; i < keys.length; i++) {
                columns.push({
                    dataIndex: keys[i],
                    editor: new Ext.form.TextField({
                        allowBlank: true
                    })
                });
            }
        }


        this.grid = new Ext.grid.EditorGridPanel({
            store: this.store,
            width: 700,
            border: false,
            columns:columns,
            stripeRows: true,
            columnLines: true,
            clicksToEdit: 2,
            autoHeight: true,
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
        this.panel.doLayout();
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

        for (var o = 0; o < this.grid.getColumnModel().config.length; o++) {
            initData["col_" + o] = " ";
        }

        this.store.add(new this.store.recordType(initData, this.store.getCount() + 1));
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
            var column = selected.selection.cell[1];

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

        var storedData = [];
        var tmData = [];
        for (var i = 0; i < data.items.length; i++) {
            tmData = [];

            var keys = Object.keys(data.items[i].data);
            for (var u = 0; u < keys.length; u++) {
                tmData.push(data.items[i].data[keys[u]]);
            }
            storedData.push(tmData);
        }

        return storedData;
    },

    getType: function () {
        return "table";
    }
});