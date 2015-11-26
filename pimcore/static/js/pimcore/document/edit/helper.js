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




// disable reload & links, this function is here because it has to be in the header (body attribute)
function pimcoreOnUnload() {
    editWindow.protectLocation();
}


pimcore.edithelpers = {
    __lastPageHeight: null // contains the last page height determined by setBodyHeight()
};

pimcore.edithelpers.setBodyHeight = function () {
    try {
        var lastPageHeight = pimcore.edithelpers.__lastPageHeight;

        var getCurrentHeight = function () {
            var body = document.body,
                html = document.documentElement;

            return Math.max(body.scrollHeight, body.offsetHeight,
                html.clientHeight, html.scrollHeight, html.offsetHeight);
        };

        var height = getCurrentHeight();

        if(!lastPageHeight || lastPageHeight < (height-100)) {
            Ext.getBody().setHeight(height);
            Ext.get(Ext.query("html")[0]).setHeight(height);

            // set the current height based based on the new height read from the dom
            // (not the value in variable height, because the setHeight() above may changes the total height again)
            pimcore.edithelpers.__lastPageHeight = getCurrentHeight();
        }
    } catch (e) {
        console.log(e);
    }
};

pimcore.edithelpers.frame = {
    active: false,
    topEl: null,
    bottomEl: null,
    rightEl: null,
    leftEl: null,
    timeout: null
};

pimcore.edithelpers.frameElement = function (el, body) {

    if(pimcore.edithelpers.frame.active) {
        pimcore.edithelpers.unFrameElement();
    }

    var offsets;
    var borderWidth;
    var width;
    var height;

    try {
        var startDistance = 5;
        offsets = Ext.get(el).getOffsetsTo(Ext.getBody());
        var bodyOffsetLeft = intval(Ext.getBody().getStyle("margin-left"));
        var bodyOffsetTop = intval(Ext.getBody().getStyle("margin-top"));

        offsets[0] -= bodyOffsetLeft;
        offsets[1] -= bodyOffsetTop;

        offsets[0] -= startDistance;
        offsets[1] -= startDistance;

        width = Ext.get(el).getWidth() + (startDistance*2);
        height = Ext.get(el).getHeight() + (startDistance*2);
        borderWidth = 5;

        if(typeof body == "undefined") {
            body = document.body;
        }
    } catch (e) {
        return;
    }

    var top = document.createElement("div");
    top = Ext.get(top);
    top.appendTo(body);
    top.applyStyles({
        position: "absolute",
        top: (offsets[1] - borderWidth) + "px",
        left: (offsets[0] - borderWidth) + "px",
        width: (width + borderWidth*2) + "px",
        height: borderWidth + "px",
        backgroundColor: "#BABABA",
        zIndex: 10000
    });

    var bottom = document.createElement("div");
    bottom = Ext.get(bottom);
    bottom.appendTo(body);
    bottom.applyStyles({
        position: "absolute",
        top: (offsets[1] + borderWidth + height) + "px",
        left: (offsets[0] - borderWidth) + "px",
        width: (width + borderWidth*2) + "px",
        height: borderWidth + "px",
        backgroundColor: "#BABABA",
        zIndex: 10000
    });

    var left = document.createElement("div");
    left = Ext.get(left);
    left.appendTo(body);
    left.applyStyles({
        position: "absolute",
        top: (offsets[1] - borderWidth) + "px",
        left: (offsets[0] - borderWidth) + "px",
        width: borderWidth + "px",
        height: (height + borderWidth*2) + "px",
        backgroundColor: "#BABABA",
        zIndex: 10000
    });

    var right = document.createElement("div");
    right = Ext.get(right);
    right.appendTo(body);
    right.applyStyles({
        position: "absolute",
        top: (offsets[1] - borderWidth) + "px",
        left: (offsets[0] + width ) + "px",
        width: borderWidth + "px",
        height: (height + borderWidth*2) + "px",
        backgroundColor: "#BABABA",
        zIndex: 10000
    });

    pimcore.edithelpers.frame.topEl= top;
    pimcore.edithelpers.frame.bottomEl = bottom;
    pimcore.edithelpers.frame.leftEl = left;
    pimcore.edithelpers.frame.rightEl = right;
    pimcore.edithelpers.frame.active = true;

    var animDuration = 0.35;

    pimcore.edithelpers.frame.timeout = window.setTimeout(function () {
        top.animate( { opacity: {to: 0, from: 1} },  animDuration,  null,  'easeOut' );
        bottom.animate( { opacity: {to: 0, from: 1} },  animDuration,  null,  'easeOut' );
        left.animate( { opacity: {to: 0, from: 1} },  animDuration,  null,  'easeOut' );
        right.animate( { opacity: {to: 0, from: 1} },  animDuration,  null,  'easeOut' );
    }, 500);

};


pimcore.edithelpers.unFrameElement = function () {

    if(pimcore.edithelpers.frame.active) {

        window.clearTimeout(pimcore.edithelpers.frame.timeout);

        pimcore.edithelpers.frame.topEl.remove();
        pimcore.edithelpers.frame.bottomEl.remove();
        pimcore.edithelpers.frame.leftEl.remove();
        pimcore.edithelpers.frame.rightEl.remove();

        pimcore.edithelpers.frame.active = false;
    }
};

