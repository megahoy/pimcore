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

pimcore.registerNS("pimcore.settings.update");
pimcore.settings.update = Class.create({

    initialize: function () {



        Ext.MessageBox.confirm("CONFIRMATION",
            'You are about to update the system. <br />'
            + 'Please do not update this pimcore installation unless you are sure what you are doing.<br/>'
            + '<b style="color:red;"><u>Updates should be performed only by developers!</u></b><br />'
            + 'Please read the '
            + ' <a href="http://www.pimcore.org/wiki/display/PIMCORE/Upgrade+Notes" target="_blank">'
            + 'upgrade notes</a> before you start the update.<br /><br />Are you sure?',
            function (buttonValue) {
                if (buttonValue == "yes") {

                    this.window = new Ext.Window({
                        layout:'fit',
                        width:500,
                        height:385,
                        autoScroll: true,
                        modal: true
                    });

                    this.window.show();

                    // start
                    this.checkFilePermissions();
                }
            }.bind(this));



    },

    checkFilePermissions: function () {
        this.window.removeAll();
        this.window.add(new Ext.Panel({
            title: "Liveupdate",
            bodyStyle: "padding: 20px;",
            html: "<b>Checking file permissions in /pimcore</b><br /><br />"
        }));
        this.window.updateLayout();

        Ext.Ajax.request({
            url: "/admin/update/index/check-file-permissions",
            success: function (response) {
                var res = Ext.decode(response.responseText);
                if(res && res.success) {
                    this.checkForAvailableUpdates();
                } else {
                    this.window.removeAll();
                    this.window.add(new Ext.Panel({
                        title: 'ERROR',
                        bodyStyle: "padding: 20px;",
                        html: '<div class="pimcore_error"><b>Some file in /pimcore is not writeable!</b> <br />'
                        + 'Please ensure that the whole /pimcore directory is writeable.</div>'
                    }));
                    this.window.updateLayout();
                }
            }.bind(this)
        });
    },

    checkForAvailableUpdates: function () {
        this.window.removeAll();
        this.window.add(new Ext.Panel({
            title: 'Liveupdate',
            bodyStyle: "padding: 20px;",
            html: "Looking for updates ..."
        }));
        this.window.updateLayout();

        Ext.Ajax.request({
            url: "/admin/update/index/get-available-updates",
            success: this.selectUpdate.bind(this)
        });
    },

    selectUpdate: function (response) {

        this.window.removeAll();

        var availableUpdates;

        try {
            availableUpdates = Ext.decode(response.responseText);
        }
        catch (e) {
            this.window.add(new Ext.Panel({
                title: "ERROR",
                bodyStyle: "padding: 20px;",
                autoScroll: true,
                html: '<div class="pimcore_error"><b>Unable to retrieve update information, see the error below:</b>'
                + '</div> <br />' + response.responseText
            }));
            this.window.updateLayout();

            return;
        }


        // no updates available
        if (availableUpdates.revisions.length < 1 && availableUpdates.releases.length < 1) {

            var panel = new Ext.Panel({
                html: t('latest_pimcore_version_already_installed'),
                bodyStyle: "padding: 20px;"
            });

            this.window.add(panel);
            this.window.updateLayout();

            return;
        }

        var panelConfig = {
            items: []
        };

        if (availableUpdates.releases.length > 0) {
            var storeReleases = new Ext.data.Store({
                proxy: {
                    type: 'memory',
                    reader: {
                        type: 'json',
                        rootProperty: 'releases',
                        idProperty: 'id'
                    }
                },
                autoDestroy: true,
                data: availableUpdates,
                fields: ["id","date","text","version"]
            });

            panelConfig.items.push({
                xtype: "form",
                bodyStyle: "padding: 10px;",
                style: "margin-bottom: 10px;",
                title: t('stable_updates'),
                items: [
                    {
                        xtype: "combo",
                        fieldLabel: t('select_update'),
                        name: "update_releases",
                        id: "update_releases",
                        width: 400,
                        store: storeReleases,
                        triggerAction: "all",
                        displayField: "version",
                        valueField: "id"
                    }
                ],
                bbar: ["->",
                    {
                        xtype: "button",
                        iconCls: "pimcore_icon_apply",
                        text: t('update'),
                        handler: this.updateStart.bind(this, "update_releases")
                    }
                ]
            });
        }

        if (availableUpdates.revisions.length > 0) {

            var storeRevisions = new Ext.data.Store({
                proxy: {
                    type: 'memory',
                    reader: {
                        type: 'json',
                        rootProperty: 'revisions',
                        idProperty: 'id'
                    }
                },
                autoDestroy: true,
                data: availableUpdates,
                fields: ["id","date","text"]
            });

            panelConfig.items.push({
                xtype: "form",
                bodyStyle: "padding: 10px;",
                title: t('non_stable_updates'),
                items: [
                    {
                        xtype: "panel",
                        border: false,
                        padding: "0 0 10px 0",
                        html: '<div class="pimcore_error"><b>Warning:</b> The following updates are <b>not tested</b>'
                        + ' and might be <b>corrupted</b>!</div>'
                    },
                    {
                        xtype: "combo",
                        fieldLabel: t('select_update'),
                        name: "update_revisions",
                        id: "update_revisions",
                        width: 400,
                        store: storeRevisions,
                        triggerAction: "all",
                        valueField: "id"
                    }
                ],
                bbar: ["->",
                    {
                        xtype: "button",
                        text: t('update'),
                        iconCls: "pimcore_icon_apply",
                        handler: function () {

                            Ext.MessageBox.confirm("!!! WARNING !!!", t("sure_to_install_unstable_update"),
                                function (buttonValue) {
                                    if (buttonValue == "yes") {
                                        this.updateStart("update_revisions");
                                    }
                                }.bind(this));
                        }.bind(this)
                    }
                ]
            });
        }

        this.window.add(new Ext.Panel(panelConfig));
        this.window.updateLayout();
    },

    updateStart: function (type) {
        var updateId = Ext.getCmp(type).getValue();
        this.updateId = updateId;


        this.window.removeAll();
        this.window.add(new Ext.Panel({
            title: "Liveupdate",
            bodyStyle: "padding: 20px;",
            html: "<b>Getting update information ...</b><br />Please wait!<br />"
        }));
        this.window.updateLayout();


        pimcore.helpers.activateMaintenance();

        Ext.Ajax.request({
            url: "/admin/update/index/get-jobs",
            success: this.prepareJobs.bind(this),
            params: {toRevision: this.updateId}
        });
    },

    prepareJobs: function (response)  {
        this.jobs = Ext.decode(response.responseText);

        this.startParallelJobs();
    },

    startParallelJobs: function () {

        this.progressBar = new Ext.ProgressBar({
            text: t('initializing')
        });

        this.window.removeAll();
        this.window.add(new Ext.Panel({
            title: "Liveupdate",
            bodyStyle: "padding: 20px;",
            items: [{
                border:false,
                html: "<b>Downloading data, please wait ...<br />",
                style: "padding: 0 0 20px 0;"
            }, this.progressBar]
        }));
        this.window.updateLayout();

        this.parallelJobsRunning = 0;
        this.parallelJobsFinished = 0;
        this.parallelJobsStarted = 0;
        this.parallelJobsTotal = this.jobs.parallel.length;

        this.parallelJobsInterval = window.setInterval(function () {

            var maxConcurrentJobs = 5;

            if(this.parallelJobsFinished == this.parallelJobsTotal) {
                clearInterval(this.parallelJobsInterval);
                this.startProceduralJobs();

                return;
            }

            if(this.parallelJobsRunning < maxConcurrentJobs && this.parallelJobsStarted < this.parallelJobsTotal) {

                this.parallelJobsRunning++;

                Ext.Ajax.request({
                    url: "/admin/update/index/job-parallel",
                    success: function (response) {

                        try {
                            response = Ext.decode(response.responseText);
                            if(!response.success) {
                                // if the download fails, stop all activity
                                throw response;
                            }
                        } catch (e) {
                            clearInterval(this.parallelJobsInterval);
                            if(typeof response.responseText != "undefined" && !empty(response.responseText)) {
                                response = response.responseText;
                            }
                            this.showErrorMessage("Download fails, see debug.log for more details.<br /><br />"
                            + "Error-Message:<br /><hr />" + this.formatError(response));
                        }

                        this.parallelJobsFinished++;
                        this.parallelJobsRunning-=1;

                        // update progress bar
                        var status = this.parallelJobsFinished / this.parallelJobsTotal;
                        var percent = Math.ceil(status * 100);

                        try {
                            this.progressBar.updateProgress(status, percent + "%");
                        } catch (e2) {}

                    }.bind(this),
                    failure: function (response) {
                        clearInterval(this.parallelJobsInterval);
                        if(typeof response.responseText != "undefined" && !empty(response.responseText)) {
                            response = response.responseText;
                        }
                        this.showErrorMessage("Download fails, see debug.log for more details.<br /><hr />"
                        + this.formatError(response) );
                    }.bind(this),
                    params: this.jobs.parallel[this.parallelJobsStarted]
                });

                this.parallelJobsStarted++;
            }
        }.bind(this),50);
    },

    startProceduralJobs: function () {
        this.progressBar = new Ext.ProgressBar({
            text: t('initializing')
        });

        this.window.removeAll();
        this.window.add(new Ext.Panel({
            title: "Liveupdate",
            bodyStyle: "padding: 20px;",
            items: [{
                border:false,
                html: "<b>Installing data, please wait ...<br />",
                style: "padding: 0 0 20px 0;"
            }, this.progressBar]
        }));
        this.window.updateLayout();

        this.proceduralJobsRunning = 0;
        this.proceduralJobsFinished = 0;
        this.proceduralJobsStarted = 0;
        this.proceduralJobsTotal = this.jobs.procedural.length;
        this.proceduralJobsMessages = [];

        this.proceduralJobsInterval = window.setInterval(function () {

            if(this.proceduralJobsFinished == this.proceduralJobsTotal) {
                clearInterval(this.proceduralJobsInterval);
                this.finished();

                return;
            }

            if(this.proceduralJobsRunning < 1) {

                this.proceduralJobsRunning++;

                Ext.Ajax.request({
                    url: "/admin/update/index/job-procedural",
                    success: function (response) {

                        try {
                            response = Ext.decode(response.responseText);
                            if(!response.success) {
                                // if the download fails, stop all activity
                                throw response;
                            }

                            if(response.message) {
                                this.proceduralJobsMessages.push(response.message);
                            }
                        } catch (e) {
                            clearInterval(this.proceduralJobsInterval);
                            if(typeof response.responseText != "undefined" && !empty(response.responseText)) {
                                response = response.responseText;
                            }
                            this.showErrorMessage("Install of update fails, see debug.log for more details.<br />"
                            + "<br />Error-Message:<br /><hr />" + this.formatError(response) );
                        }

                        this.proceduralJobsFinished++;
                        this.proceduralJobsRunning-=1;

                        // update progress bar
                        var status = this.proceduralJobsFinished / this.proceduralJobsTotal;
                        var percent = Math.ceil(status * 100);

                        try {
                            this.progressBar.updateProgress(status, percent + "%");
                        } catch (e2) {}

                    }.bind(this),
                    failure: function (response) {
                        clearInterval(this.proceduralJobsInterval);
                        if(typeof response.responseText != "undefined" && !empty(response.responseText)) {
                            response = response.responseText;
                        }
                        this.showErrorMessage("Install of update fails, see debug.log for more details.<br /><hr />"
                        + this.formatError(response) );
                    }.bind(this),
                    params: this.jobs.procedural[this.proceduralJobsStarted]
                });

                this.proceduralJobsStarted++;
            }
        }.bind(this),500);
    },

    finished: function () {

        var message = "<b>Update complete!</b><br />Now it's time to reload pimcore.<br /><br />";
        if(this.proceduralJobsMessages.length > 0) {
            message += '<b>Upgrade Notes</b><br /><div class="pimcore_update_message">';
            message += this.proceduralJobsMessages.join('</div><div class="pimcore_update_message">');
            message += '</div>';
        }


        this.window.removeAll();
        this.window.add(new Ext.Panel({
            title: "Liveupdate",
            bodyStyle: "padding: 20px;",
            autoScroll: true,
            html: message
        }));
        this.window.updateLayout();


        pimcore.helpers.deactivateMaintenance();

        window.setTimeout(function () {
            Ext.MessageBox.confirm(t("info"), t("reload_pimcore_changes"), function (buttonValue) {
                if (buttonValue == "yes") {
                    window.location.reload();
                }
            }.bind(this));
        }.bind(this), 1000);
    },

    showErrorMessage: function (message) {
        this.window.removeAll();
        this.window.add(new Ext.Panel({
            title: "ERROR",
            autoHeight: true,
            bodyStyle: "padding: 20px;",
            html: '<div class="pimcore_error">' + message + "</div>"
        }));
        this.window.updateLayout();
    },

    formatError: function (error) {

        if(typeof error == "string" || typeof error == "number") {
            return error;
        } else if (typeof error == "object") {
            return "<pre>"  + htmlentities(FormatJSON(error)) + "</pre>";
        }

        return "No valid error message";
    }

});

