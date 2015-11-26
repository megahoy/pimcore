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

pimcore.registerNS("pimcore.object.tags.fieldcollections");
pimcore.object.tags.fieldcollections = Class.create(pimcore.object.tags.abstract, {

    type: "fieldcollections",
    dirty: false,

    initialize: function (data, fieldConfig) {

        this.dirty = false;
        this.data = [];
        this.currentElements = [];
        this.layoutDefinitions = {};
        this.dataFields = [];
        this.layoutIds = [];
        
        if (data) {
            this.data = data;
        }
        this.fieldConfig = fieldConfig;
    },

    getGridColumnConfig: function(field) {
        return {header: ts(field.label), width: 150, sortable: false, dataIndex: field.key,
                renderer: function (key, value, metaData, record) {
                    this.applyPermissionStyle(key, value, metaData, record);

                    return t("not_supported");
                }.bind(this, field.key)};
    },

    loadFieldDefinitions: function () {

        var allowedTypes = this.fieldConfig.allowedTypes;
        if(!allowedTypes) {
            allowedTypes = [];
        }

        this.fieldstore = new Ext.data.Store({
            proxy: {
                type: 'ajax',
                url: "/admin/class/fieldcollection-list",
                reader: {
                    type: 'json',
                    rootProperty: 'fieldcollections',
                    idProperty: 'key'
                },
                extraParams: {
                    allowedTypes: allowedTypes.join(",")
                }
            },
            autoDestroy: false,
            fields: ['key', {name: "fieldConfigigurations", convert: function (v, rec) {
                this.layoutDefinitions[rec.data.key] = rec.data.layoutDefinitions;
            }.bind(this)}],
            listeners: {
                load: this.initData.bind(this)
            }
        });
        
        this.fieldstore.load();

    },

    getLayoutEdit: function () {
        
        this.loadFieldDefinitions();
        
        var panelConf = {
            autoHeight: true,
            border: true,
            style: "margin-bottom: 10px",
            componentCls: "object_field",
            collapsible: this.fieldConfig.collapsible,
            collapsed: this.fieldConfig.collapsed
        };
        if(this.fieldConfig.title) {
            panelConf.title = this.fieldConfig.title;
        }
        
        this.component = new Ext.Panel(panelConf);

        this.component.addListener("render", function() {
            if(this.object.data.metaData[this.getName()] && this.object.data.metaData[this.getName()].hasParentValue) {
                this.addInheritanceSourceButton(this.object.data.metaData[this.getName()]);
            }
        }.bind(this));

        return this.component;
    },
    
    initData: function () {
        
        if(this.data.length < 1) {
            this.component.add(this.getControls());
        } else {
            for (var i=0; i<this.data.length; i++) {
                this.addBlockElement(i,this.data[i].type, this.data[i].data, true);
            }
        }
        
        this.component.updateLayout();
    },
    
    getControls: function (blockElement) {
        
        var collectionMenu = [];
        
        this.fieldstore.each(function (blockElement, rec) {
            collectionMenu.push({
                text: ts(rec.data.key),
                handler: this.addBlock.bind(this,blockElement, rec.data.key),
                iconCls: "pimcore_icon_fieldcollections"
            });
        }.bind(this, blockElement));
        
        var items = [];
        
        if(collectionMenu.length == 1) {
            items.push({
                disabled: this.fieldConfig.disallowAddRemove,
                cls: "pimcore_block_button_plus",
                iconCls: "pimcore_icon_plus",
                handler: collectionMenu[0].handler
            });
        } else if (collectionMenu.length > 1) {
            items.push({
                disabled: this.fieldConfig.disallowAddRemove,
                cls: "pimcore_block_button_plus",
                iconCls: "pimcore_icon_plus",
                menu: collectionMenu
            });
        } else {
            items.push({
                xtype: "tbtext",
                text: t("no_collections_allowed")
            });
        }
        
        
        
        if(blockElement) {
            items.push({
                disabled: this.fieldConfig.disallowAddRemove,
                cls: "pimcore_block_button_minus",
                iconCls: "pimcore_icon_minus",
                listeners: {
                    "click": this.removeBlock.bind(this, blockElement)
                }
            });
            
            items.push({
                disabled: this.fieldConfig.disallowReorder,
                cls: "pimcore_block_button_up",
                iconCls: "pimcore_icon_up",
                listeners: {
                    "click": this.moveBlockUp.bind(this, blockElement)
                }
            });
            
            items.push({
                disabled: this.fieldConfig.disallowReorder,
                cls: "pimcore_block_button_down",
                iconCls: "pimcore_icon_down",
                listeners: {
                    "click": this.moveBlockDown.bind(this, blockElement)
                }
            });
        }
        
        var toolbar = new Ext.Toolbar({
            items: items
        });
        
        return toolbar;
    },
    
    detectBlockIndex: function (blockElement) {
        // detect index
        var index;
        
        for(var s=0; s<this.component.items.items.length; s++) {
            if(this.component.items.items[s].key == blockElement.key) {
                index = s;
                break;
            }
        }
        return index;
    },

    closeOpenEditors: function () {

        // currently just wysiwyg
        for (var i=0; i<this.currentElements.length; i++) {
            if(typeof this.currentElements[i] == "object") {
                for(var e=0; e<this.currentElements[i]["fields"].length; e++) {
                    if(typeof this.currentElements[i]["fields"][e]["close"] == "function") {
                        this.currentElements[i]["fields"][e].close();
                    }
                }
            }
        }
    },

    addBlock: function (blockElement, type) {

        this.closeOpenEditors();

        if(this.fieldConfig.maxItems) {
            var itemAmount = 0;
            for(var s=0; s<this.component.items.items.length; s++) {
                if(typeof this.component.items.items[s].key != "undefined") {
                    itemAmount++;
                }
            }

            if(itemAmount >= this.fieldConfig.maxItems) {
                Ext.MessageBox.alert(t("error"), t("limit_reached"));
                return;
            }
        }

        var index = 0;
        if(blockElement) {
            index = this.detectBlockIndex(blockElement);
        }
        
        this.addBlockElement(index + 1, type);
    },
    
    removeBlock: function (blockElement) {

        this.closeOpenEditors();

        var key = blockElement.key;
        this.currentElements[key] = "deleted";
        
        this.component.remove(blockElement);
        this.dirty = true;
        
        // check for remaining elements
        if(this.component.items.items.length < 1) {
            this.component.removeAll();
            this.component.add(this.getControls());
            this.component.updateLayout();
            this.currentElements = [];
        }
    },
    
    moveBlockUp: function (blockElement) {

        this.closeOpenEditors();

        if(blockElement) {
            index = this.detectBlockIndex(blockElement);
        }
        
        var newIndex = index-1;
        if(newIndex < 0) {
            newIndex = 0;
        }
        
        // move this node temorary to an other so ext recognizes a change
        this.component.remove(blockElement, false);
        this.object.edit.layout.add(blockElement);
        this.object.edit.layout.updateLayout();
        this.component.updateLayout();
        
        // move the element to the right position
        this.object.edit.layout.remove(blockElement,false);
        this.component.insert(newIndex, blockElement);
        this.component.updateLayout();
        this.dirty = true;
    },
    
    moveBlockDown: function (blockElement) {

        this.closeOpenEditors();

        if(blockElement) {
            index = this.detectBlockIndex(blockElement);
        }
        
        // move this node temorary to an other so ext recognizes a change
        this.component.remove(blockElement, false);
        this.object.edit.layout.add(blockElement);
        this.object.edit.layout.updateLayout();
        this.component.updateLayout();
        
        // move the element to the right position
        this.object.edit.layout.remove(blockElement,false);
        this.component.insert(index+1, blockElement);
        this.component.updateLayout();
        this.dirty = true;
    },
    
    addBlockElement: function (index, type, blockData, ignoreChange) {

        this.closeOpenEditors();

        if(!type){
            return;
        }
        if(!this.layoutDefinitions[type]) {
            return;
        }
        
        // remove the initial toolbar if there is no element
        if(this.currentElements.length < 1) {
            this.component.removeAll();
        }
        
        this.dataFields = [];
        this.currentData = {};
        
        if(blockData) {
            this.currentData = blockData;
        }

        var blockElement = new Ext.Panel({
            bodyStyle: "padding:10px;",
            style: "margin: 0 0 10px 0;",
            autoHeight: true,
            border: false,
            items: this.getRecursiveLayout(this.layoutDefinitions[type]).items
        });
        
        blockElement.insert(0, this.getControls(blockElement));
        
        blockElement.key = this.currentElements.length;
        blockElement.fieldtype = type;
        this.component.insert(index, blockElement);
        this.component.updateLayout();

        this.currentElements.push({
            container: blockElement,
            fields: this.dataFields,
            type: type
        });

        if(!ignoreChange) {
            this.dirty = true;
        }

        this.dataFields = [];
        this.currentData = {};
    },

    getDataForField: function (fieldConfig) {
        var name = fieldConfig.name;
        return this.currentData[name];
    },

    getMetaDataForField: function(fieldConfig) {
        return null;
    },

    addToDataFields: function (field, name) {
        this.dataFields.push(field);
    },

    getLayoutShow: function () {

        this.component = this.getLayoutEdit();
        this.component.disable();

        return this.component;
    },

    getValue: function () {
        
        var data = [];
        var element;
        var elementData = {};
        
        for(var s=0; s<this.component.items.items.length; s++) {
            elementData = {};
            if(this.currentElements[this.component.items.items[s].key]) {
                element = this.currentElements[this.component.items.items[s].key];

                // no check for dirty, ... always send all field to the server
                for (var u=0; u<element.fields.length; u++) {
                    elementData[element.fields[u].getName()] = element.fields[u].getValue();
                }
                
                data.push({
                    type: element.type,
                    data: elementData
                });
            }
        }
        
        return data;
    },

    getName: function () {
        return this.fieldConfig.name;
    },

    isDirty: function() {

        // check elements
        var element;

        if(!this.isRendered()) {
            return false;
        }

        if(typeof this.component.items == "undefined") {
            return false;
        }

        for(var s=0; s<this.component.items.items.length; s++) {
            if(this.currentElements[this.component.items.items[s].key]) {
                element = this.currentElements[this.component.items.items[s].key];

                for (var u=0; u<element.fields.length; u++) {
                    if(element.fields[u].isDirty()) {
                        return true;
                    }
                }
            }
        }

        return this.dirty;
    },

    isMandatory: function () {
        var element;

        for(var s=0; s<this.component.items.items.length; s++) {
            if(this.currentElements[this.component.items.items[s].key]) {
                element = this.currentElements[this.component.items.items[s].key];

                for (var u=0; u<element.fields.length; u++) {
                    if(element.fields[u].isMandatory()) {
                        return true;
                    }
                }
            }
        }

        return false;
    },

    isInvalidMandatory: function () {
        var element;
        var isInvalid = false;
        var invalidMandatoryFields = [];

        for(var s=0; s<this.component.items.items.length; s++) {
            if(this.currentElements[this.component.items.items[s].key]) {
                element = this.currentElements[this.component.items.items[s].key];

                for (var u=0; u<element.fields.length; u++) {
                    if(element.fields[u].isMandatory()) {
                        if(element.fields[u].isInvalidMandatory()) {
                            invalidMandatoryFields.push(element.fields[u].getTitle() + " ("
                                                                    + element.fields[u].getName() + ")");
                            isInvalid = true;
                        }
                    }
                }
            }
        }

        // return the error messages not bool, this is handled in object/edit.js
        if(isInvalid) {
            return invalidMandatoryFields;
        }

        return isInvalid;
    }
});

pimcore.object.tags.fieldcollections.addMethods(pimcore.object.helpers.edit);