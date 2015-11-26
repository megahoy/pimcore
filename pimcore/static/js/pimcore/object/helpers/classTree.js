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

pimcore.registerNS("pimcore.object.helpers.classTree");
pimcore.object.helpers.classTree = Class.create({

    showFieldName: false,

    initialize: function (showFieldName) {
        if(showFieldName) {
            this.showFieldName = showFieldName;
        }
    },


    getClassTree: function(url, classId, objectId) {

        var tree = new Ext.tree.TreePanel({
            title: t('class_definitions'),
            xtype: "treepanel",
            region: "center",
            enableDrag: true,
            enableDrop: false,
            ddGroup: "columnconfigelement",
            autoScroll: true,
            rootVisible: false,
            root: {
                id: "0",
                root: true,
                text: t("base"),
                draggable: false,
                leaf: true,
                isTarget: true
            }
        });

        Ext.Ajax.request({
            url: url, //"/admin/class/get",
            params: {
                id: classId, // this.config.classid,
                oid: objectId
            },
            success: this.initLayoutFields.bind(this, tree)
        });

        return tree;
    },

    initLayoutFields: function (tree, response) {
        var data = Ext.decode(response.responseText);

        var keys = Object.keys(data);
        for(var i = 0; i < keys.length; i++) {
            if (data[keys[i]]) {
                if (data[keys[i]].childs) {
                    var attributePrefix = "";
                    var text = t(data[keys[i]].nodeLabel);
                    if(data[keys[i]].nodeType == "objectbricks") {
                        text = ts(data[keys[i]].nodeLabel) + " " + t("columns");
                        attributePrefix = data[keys[i]].nodeLabel;
                    }
                    var baseNode = new Ext.tree.TreeNode({
                        type: "layout",
                        draggable: false,
                        iconCls: "pimcore_icon_" + data[keys[i]].nodeType,
                        text: text
                    });

                    tree.getRootNode().appendChild(baseNode);
                    for (var j = 0; j < data[keys[i]].childs.length; j++) {
                        baseNode.appendChild(this.recursiveAddNode(data[keys[i]].childs[j], baseNode, attributePrefix));
                    }
                    if(data[keys[i]].nodeType == "object") {
                        baseNode.expand();
                    } else {
                        baseNode.collapse();
                    }
                }
            }
        }
    },

    recursiveAddNode: function (con, scope, attributePrefix) {

        var fn = null;
        var newNode = null;

        if (con.datatype == "layout") {
            fn = this.addLayoutChild.bind(scope, con.fieldtype, con);
        }
        else if (con.datatype == "data") {
            fn = this.addDataChild.bind(scope, con.fieldtype, con, attributePrefix, this.showFieldName);
        }

        newNode = fn();

        if (con.childs) {
            for (var i = 0; i < con.childs.length; i++) {
                this.recursiveAddNode(con.childs[i], newNode, attributePrefix);
            }
        }

        return newNode;
    },

    addLayoutChild: function (type, initData) {

        var nodeLabel = t(type);

        if (initData) {
            if (initData.title) {
                nodeLabel = initData.title;
            } else if (initData.name) {
                nodeLabel = initData.name;
            }
        }

        var newNode = new Ext.tree.TreeNode({
            type: "layout",
            draggable: false,
            iconCls: "pimcore_icon_" + type,
            text: nodeLabel
        });

        this.appendChild(newNode);

        if(this.rendered) {
            this.renderIndent();
            this.expand();
        }

        return newNode;
    },

    addDataChild: function (type, initData, attributePrefix, showFieldname) {

        if(type != "objectbricks" && !initData.invisible) {
            var isLeaf = true;
            var draggable = true;

            // localizedfields can be a drop target
            if(type == "localizedfields") {
                isLeaf = false;
                draggable = false;
            }

            var key = initData.name;
            if(attributePrefix) {
                key = attributePrefix + "~" + key;
            }

            var text = ts(initData.title);
            if(showFieldname) {
                text = text + " (" + key.replace("~", ".") + ")";
            }
            var newNode = new Ext.tree.TreeNode({
                text: text,
                key: key,
                type: "data",
                layout: initData,
                leaf: isLeaf,
                draggable: draggable,
                dataType: type,
                iconCls: "pimcore_icon_" + type
            });

            this.appendChild(newNode);

            if(this.rendered) {
                this.renderIndent();
                this.expand();
            }

            return newNode;
        } else {
            return null;
        }

    }

});
