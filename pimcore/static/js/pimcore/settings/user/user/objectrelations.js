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


pimcore.registerNS("pimcore.settings.user.user.objectrelations");
pimcore.settings.user.user.objectrelations = Class.create({

    initialize: function (userPanel) {
        this.userPanel = userPanel;

        this.data = this.userPanel.data;
    },

    getPanel: function () {

        this.objectDependenciesStore = new Ext.data.JsonStore({
            autoDestroy: true,
            data: this.data.objectDependencies,
            root: 'dependencies',
            fields: ['id', 'path', 'subtype']
        });

        this.objectDependenciesGrid = new Ext.grid.GridPanel({
            store: this.objectDependenciesStore,
            columns: [
                {header: "ID", sortable: true, dataIndex: 'id'},
                {header: t("path"), id: "path", sortable: true, dataIndex: 'path'},
                {header: t("subtype"), sortable: true, dataIndex: 'subtype'}
            ],
            columnLines: true,
            autoExpandColumn: "path",
            stripeRows: true,
            autoHeight: true,
            title: t('user_object_dependencies_description')
        });
        this.objectDependenciesGrid.on("rowclick", function(grid, index){
                var d = grid.getStore().getAt(index).data;
                pimcore.helpers.openObject(d.id, "object");

        });

        this.hiddenNote = new Ext.Panel({
            html:t('hidden_dependencies'),
            cls:'dependency-warning',
            border:false,
            hidden: !this.data.objectDependencies.hasHidden
        });

        this.panel = new Ext.Panel({
            title: t("user_object_dependencies_description"),
            items: [this.hiddenNote, this.objectDependenciesGrid]
        });

        return this.panel;
    }
});