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

pimcore.registerNS("pimcore.document.edit.dnd");
pimcore.document.edit.dnd = Class.create({

    dndManager: null,

    globalDropZone: null,

    initialize: function(parentExt, body, iframeElement) {

        parentExt.dd.DragDropMgr.notifyOccluded = true;
        this.dndManager = parentExt.dd.DragDropMgr;
        var iFrameElement = parent.Ext.get('document_iframe_' + window.editWindow.document.id);

        //parentExt.EventManager.on(body, 'mousemove', this.ddMouseMove.bind(this));
        //parentExt.EventManager.on(body, 'mouseup', this.ddMouseUp.bind(this));

        body.addListener('mousemove', this.ddMouseMove.bind(this));
        body.addListener('mouseup', this.ddMouseUp.bind(this));

        this.globalDropZone = new parent.Ext.dd.DropZone(iframeElement, {
            ddGroup: "element",
            validElements: [],

            getTargetFromEvent: function(e) {
                var element = null;
                var elLength = this.validElements.length;

                for (var i = 0; i < elLength; i++) {
                    element = this.validElements[i];
                    if (element["el"].dndOver) {
                        if(element["drop"]) {
                            this.onNodeDrop = element["drop"];
                        }
                        if(element["over"]) {
                            this.onNodeOver = element["over"];
                        }
                        return element["el"];
                    }
                }
            }
        });

        window.setInterval(this.setIframeOffset.bind(this),1000);
        this.setIframeOffset();
    },

    addDropTarget: function (el, overCallback, dropCallback) {

        el.on("mouseover", function (e) {
            this.dndOver = true;
        }.bind(el));
        el.on("mouseout", function (e) {
            this.dndOver = false;
        }.bind(el));

        el.dndOver = false;

        this.globalDropZone.validElements.push({
            el: el,
            over: overCallback,
            drop: dropCallback
        });
    },

    ddMouseMove: function (e) {
        // update the xy of the event if necessary
        this.setDDPos(e);
        // *** Note that the 'this' scope is the drag drop manager
        this.dndManager.handleMouseMove(e);
    },

    ddMouseUp : function (e) {
        // update the xy of the event if necessary
        this.setDDPos(e);
        // *** Note that the 'this' scope is the drag drop manager
        this.dndManager.handleMouseUp(e);
    },


    setDDPos: function (e) {

        var scrollTop = 0;
        var scrollLeft = 0;

        var doc = (window.contentDocument || window.document);
        scrollTop = doc.documentElement.scrollTop || doc.body.scrollTop;
        scrollLeft = doc.documentElement.scrollLeft || doc.body.scrollLeft;

        var xy = e.getXY();

        if (this.dndManager.dragCurrent) {
            e.xy = [e.pageX + this.iframeOffset[0] - scrollLeft, e.pageY + this.iframeOffset[1] - scrollTop];
        }
    },

    setIframeOffset: function () {
        try {
            this.iframeOffset = parent.Ext.get('document_iframe_'
            + window.editWindow.document.id).getOffsetsTo(parent.Ext.getBody());
        } catch (e) {
            console.log(e);
        }
    },

    disable: function () {
        this.globalDropZone.lock();
    },

    enable: function () {
        this.globalDropZone.unlock();
    }

});
