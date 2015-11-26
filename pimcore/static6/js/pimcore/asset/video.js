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

pimcore.registerNS("pimcore.asset.video");
pimcore.asset.video = Class.create(pimcore.asset.asset, {

    initialize: function(id) {

        this.id = intval(id);
        this.setType("video");
        this.addLoadingPanel();

        pimcore.plugin.broker.fireEvent("preOpenAsset", this, "video");

        this.properties = new pimcore.element.properties(this, "asset");
        this.versions = new pimcore.asset.versions(this);
        this.scheduler = new pimcore.element.scheduler(this, "asset");
        this.dependencies = new pimcore.element.dependencies(this, "asset");
        this.notes = new pimcore.element.notes(this, "asset");
        this.metadata = new pimcore.asset.metadata(this);

        this.getData();
    },

    getTabPanel: function () {
        var items = [];

        items.push(this.getEditPanel());

        if (this.isAllowed("publish")) {
            items.push(this.metadata.getLayout());
        }
        if (this.isAllowed("properties")) {
            items.push(this.properties.getLayout());
        }
        if (this.isAllowed("versions")) {
            items.push(this.versions.getLayout());
        }
        if (this.isAllowed("settings")) {
            items.push(this.scheduler.getLayout());
        }

        items.push(this.dependencies.getLayout());

        if (this.isAllowed("settings")) {
            items.push(this.notes.getLayout());
        }

        this.tabbar = new Ext.TabPanel({
            tabPosition: "top",
            region:'center',
            deferredRender:true,
            enableTabScroll:true,
            border: false,
            items: items,
            activeTab: 0
        });

        return this.tabbar;
    },

    getEditPanel: function () {

        if (!this.editPanel) {
            this.previewPanel = new Ext.Panel({
                region: "center",
                bodyStyle: "-webkit-overflow-scrolling:touch;",
                html: '<iframe src="/admin/asset/get-preview-video/id/'
                                            + this.id + '/" frameborder="0" id="asset_video_edit_'
                                            + this.id + '" name="asset_video_edit_' + this.id + '"></iframe>'
            });
            this.previewPanel.on("resize", function (el, width, height, rWidth, rHeight) {
                Ext.get("asset_video_edit_" + this.id).setStyle({
                    width: width + "px",
                    height: (height) + "px"
                });
            }.bind(this));

            this.previewFrameId = 'asset_video_edit_' + this.id;

            var date = new Date();

            this.previewImagePanel = new Ext.Panel({
                width: 300,
                region: "east",
                bodyStyle: "display:none;",
                items: [{
                    xtype: "panel",
                    title: t("select_image_preview"),
                    bodyStyle: "padding:10px",
                    itemId: "inner",
                    manageHeight: false,
                    items: [{
                        xtype: "panel",
                        border: false,
                        manageHeight: false,
                        style: "margin: 10px 0 10px 0;",
                        id: "pimcore_asset_video_imagepreview_" + this.id,
                        bodyStyle: "min-height:150px;",
                        html: '<img align="center" src="/admin/asset/get-video-thumbnail/id/'
                                        + this.id  + '/width/265/aspectratio/true/?_dc=' + date.getTime() + '" />'
                    },{
                        xtype: "button",
                        text: t("use_current_player_position_as_preview"),
                        iconCls: "pimcore_icon_videoedit",
                        width: 265,
                        handler: function () {
                            try {
                                this.previewImagePanel.getComponent("inner").getComponent("assetPath").setValue("");

                                var time = window[this.previewFrameId].document.getElementById("video").currentTime;
                                var date = new Date();
                                var cmp = Ext.getCmp("pimcore_asset_video_imagepreview_"  + this.id);
                                cmp.update('<img align="center" src="/admin/asset/get-video-thumbnail/id/'
                                    + this.id  + '/width/265/aspectratio/true/time/' + time  + '/settime/true/?_dc='
                                    + date.getTime() + '" />');
                            } catch (e) {
                                console.log(e);
                            }
                        }.bind(this)
                    },{
                        xtype: "panel",
                        border:false,
                        bodyStyle: "padding: 10px 0 10px 0;",
                        html: t("or_specify_an_asset_image_below") + ":"
                    }
                        ,
                        {
                        xtype: "textfield",
                        itemId: "assetPath",
                        cls: "input_drop_target",
                        width: 265,
                        listeners: {
                            "render": function (el) {
                                new Ext.dd.DropZone(el.getEl(), {
                                    reference: el,
                                    ddGroup: "element",
                                    getTargetFromEvent: function(e) {
                                        return this.getEl();
                                    }.bind(el),

                                    onNodeOver : function(target, dd, e, data) {
                                        return Ext.dd.DropZone.prototype.dropAllowed;
                                    },

                                    onNodeDrop : function (el, target, dd, e, data) {
                                        data = data.records[0].data;
                                        if (data.elementType == "asset") {
                                            el.setValue(data.path);

                                            var date = new Date();
                                            var cmp = Ext.getCmp("pimcore_asset_video_imagepreview_"  + this.id);
                                            cmp.update('<img align="center" src="/admin/asset/get-video-thumbnail/id/'
                                                                + this.id  + '/width/265/aspectratio/true/image/'
                                                                + data.id  + '/setimage/true/?_dc='
                                                                + date.getTime() + '" />');
                                            return true;
                                        }
                                        return false;
                                    }.bind(this, el)
                                });
                            }.bind(this)
                        }
                    }
                    ]
                }]
            });

            this.previewImagePanel.on("afterrender", function () {
                this.checkVideoplayerInterval = window.setInterval(function () {
                    if(window[this.previewFrameId] && window[this.previewFrameId].document.getElementById("video")) {
                        this.previewImagePanel.setBodyStyle({
                            display: "block"
                        });
                        this.previewImagePanel.updateLayout();
                        clearInterval(this.checkVideoplayerInterval);
                    }
                }.bind(this), 1000);
            }.bind(this));

            this.previewImagePanel.on("beforedestroy", function () {
                clearInterval(this.checkVideoplayerInterval);
                try {
                    delete window[this.previewFrameId];
                } catch (e) {

                }
            }.bind(this));

            this.editPanel = new Ext.Panel({
                layout: "border",
                items: [this.previewPanel, this.previewImagePanel],
                title: t("preview"),
                iconCls: "pimcore_icon_tab_edit"
            });
        }

        return this.editPanel;
    }
});

