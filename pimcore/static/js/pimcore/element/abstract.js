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

pimcore.registerNS("pimcore.element.abstract");
pimcore.element.abstract = Class.create({

    dirty: false,

    addToHistory: true,

    // startup / opening functions
    addLoadingPanel : function () {
        var type = pimcore.helpers.getElementTypeByObject(this);
        pimcore.helpers.addTreeNodeLoadingIndicator(type, this.id);
    },

    removeLoadingPanel: function () {
        var type = pimcore.helpers.getElementTypeByObject(this);
        pimcore.helpers.removeTreeNodeLoadingIndicator(type, this.id);
    },


    // CHANGE DETECTOR
    startChangeDetector: function () {
        if(!this.changeDetectorInterval) {
            this.changeDetectorInterval = window.setInterval(this.checkForChanges.bind(this),1000);
        }
    },

    stopChangeDetector: function () {
        window.clearInterval(this.changeDetectorInterval);
        this.changeDetectorInterval = null;
    },

    setupChangeDetector: function () {
        this.resetChanges();
        this.tab.on("deactivate", this.stopChangeDetector.bind(this));
        this.tab.on("activate", this.startChangeDetector.bind(this));
        this.tab.on("destroy", this.stopChangeDetector.bind(this));
    },

    isDirty: function () {
        return this.dirty;
    },

    detectedChange: function () {
        this.tab.setTitle(this.tab.initialConfig.title + " *");
        this.dirty = true;
        this.stopChangeDetector();
    },

    resetChanges: function () {
        this.changeDetectorInitData = {};

        this.tab.setTitle(this.tab.initialConfig.title);
        this.startChangeDetector();
        this.dirty = false;
    },

    checkForChanges: function () {
        if(!this.changeDetectorInitData) {
            this.setupChangeDetector();
        }

        this.ignoreMandatoryFields = true;
        var liveData = this.getSaveData();
        this.ignoreMandatoryFields = false;

        var keys = Object.keys(liveData);

        for (var i=0; i<keys.length; i++) {
            if(this.changeDetectorInitData[keys[i]]) {
                if(this.changeDetectorInitData[keys[i]] != liveData[keys[i]]) {
                    this.detectedChange();
                }
            }
            this.changeDetectorInitData[keys[i]] = liveData[keys[i]];
        }
    },

    setAddToHistory: function(addToHistory) {
        this.addToHistory = addToHistory;
    },

    getAddToHistory: function() {
        return this.addToHistory;
    }
});