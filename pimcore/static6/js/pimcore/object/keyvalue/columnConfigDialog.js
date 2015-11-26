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

pimcore.registerNS("pimcore.object.keyvalue.columnConfigDialog");
pimcore.object.keyvalue.columnConfigDialog = Class.create({

    keysAdded: 0,
    requestIsPending: false,

    getConfigDialog: function(node, selectionPanel) {
        this.node = node;
        this.selectionPanel = selectionPanel;

        var selectionWindow = new pimcore.object.keyvalue.selectionwindow(this);
        selectionWindow.show();
    },


    handleSelectionWindowClosed: function() {
        if (this.keysAdded == 0 && !this.requestIsPending) {
            // no keys added, remove the node
            this.node.remove();
        }
    },

    requestPending: function() {
        this.requestIsPending = true;
    },

    handleAddKeys: function (response) {
        var data = Ext.decode(response.responseText);

        var originalKey =  this.node.attributes.key;

        if(data && data.success) {
            for (var i=0; i < data.data.length; i++) {
                var keyDef = data.data[i];

                var encodedKey = "~keyvalue~" + originalKey + "~" +  keyDef.id;

                if (this.selectionPanel.getRootNode().findChild("key", encodedKey)) {
                    // key already exists, continue
                    continue;
                }

                if (this.keysAdded > 0) {
                    var configEncoded = Ext.encode(this.node.attributes);
                    var configDecoded = Ext.decode(configEncoded);

                    var copy = new Ext.tree.TreeNode( // copy it
                        Ext.apply({}, configDecoded)
                    );
                    this.node = copy;
                    delete this.node.attributes.layout.options;
                    delete this.node.attributes.layout.gridType;
                }


                this.node.attributes.key = encodedKey;
                this.node.attributes.layout.gridType = keyDef.type;

                //TODo  implement all subtypes
                if (keyDef.type == "select") {
                    this.node.attributes.layout.options = Ext.decode(keyDef.possiblevalues);
                }

                this.node.setText( "#" + keyDef.name);

                if (this.keysAdded > 0) {
                    this.selectionPanel.getRootNode().appendChild(this.node);
                }
                this.keysAdded++;
            }
        }

        if (this.keysAdded == 0) {
             this.node.remove();
        }
    }

});