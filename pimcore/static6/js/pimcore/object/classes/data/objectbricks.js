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

pimcore.registerNS("pimcore.object.classes.data.objectbricks");
pimcore.object.classes.data.objectbricks = Class.create(pimcore.object.classes.data.data, {

    type: "objectbricks",
    /**
     * define where this datatype is allowed
     */
    allowIn: {
        object: true,
        objectbrick: false,
        fieldcollection: false,
        localizedfield: false
    },

    initialize: function (treeNode, initData) {
        this.type = "objectbricks";

        this.initData(initData);

        // overwrite default settings
        this.availableSettingsFields = ["name","title","invisible","style"];

        this.treeNode = treeNode;
    },

    getTypeName: function () {
        return t("objectbricks");
    },

    getGroup: function () {
            return "structured";
    },

    getIconClass: function () {
        return "pimcore_icon_objectbricks";
    },

    getLayout: function ($super) {
        $super();
        
        this.specificPanel.removeAll();

        return this.layout;
    },

    isValid: function ($super) {

        if(!$super()) {
            return false;
        }

        // underscore "_" ist not allowed!
        // reason: the backend creates a class with the name of this field, if it contains an _ the autoloader
        // isn't able to load this file
        var data = this.getData();
        if(data.name.match(/[_]+/)) {
            return false;
        }

        return true;
    },

    applySpecialData: function(source) {
        if (source.datax) {
            if (!this.datax) {
                this.datax =  {};
            }
            Ext.apply(this.datax,
                {
                    allowedTypes: source.datax.allowedTypes
                });
        }
    }
    
});
