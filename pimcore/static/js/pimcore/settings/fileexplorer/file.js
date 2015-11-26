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

pimcore.registerNS("pimcore.settings.fileexplorer.file");
pimcore.settings.fileexplorer.file = Class.create({

    initialize: function (path, explorer) {
        this.path = path;
        this.explorer = explorer;
        this.loadFileContents(path);
    },

    loadFileContents: function (path) {
        Ext.Ajax.request({
            url: "/admin/misc/fileexplorer-content",
            success: this.loadFileContentsComplete.bind(this),
            params: {
                path: path
            }
        });
    },

    loadFileContentsComplete: function (response) {
        response = Ext.decode(response.responseText);
        if(response.success) {

            var toolbarItems = [];
            if(response.writeable) {
                toolbarItems.push({
                    text: t("save"),
                    handler: this.saveFile.bind(this, response.path),
                    iconCls: "pimcore_icon_save"
                });
            }

            this.textarea = new Ext.form.TextArea({
                value: response.content,
                style: "font-family:courier"
            });

            this.editor = new Ext.Panel({
                title: response.path,
                closable: true,
                layout: "fit",
                tbar: toolbarItems,
                bodyStyle: "position:relative;",
                items: [this.textarea]
            });

            this.editor.on("beforedestroy", function () {
                delete this.explorer.openfiles[this.path];
            }.bind(this));

            this.explorer.editorPanel.add(this.editor);
            this.explorer.editorPanel.activate(this.editor);
            this.explorer.editorPanel.doLayout();
        }
    },

    saveFile: function (path) {
        var content = this.textarea.getValue();
        Ext.Ajax.request({
            method: "post",
            url: "/admin/misc/fileexplorer-content-save",
            params: {
                path: path,
                content: content
            },
            success: function (response) {
                try{
                    var rdata = Ext.decode(response.responseText);
                    if (rdata && rdata.success) {
                        pimcore.helpers.showNotification(t("success"), t("file_explorer_saved_file_success"),
                                                                    "success");
                    }
                    else {
                        pimcore.helpers.showNotification(t("error"), t("file_explorer_saved_file_error"), "error");
                    }
                } catch (e) {
                    pimcore.helpers.showNotification(t("error"), t("file_explorer_saved_file_error"), "error");
                }
            }.bind(this)
        });
    },

    activate: function () {
        this.explorer.editorPanel.activate(this.editor);
    }

});
