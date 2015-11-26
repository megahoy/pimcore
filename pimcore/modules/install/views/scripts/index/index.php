<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title><?= htmlentities($this->getRequest()->getHttpHost(), ENT_QUOTES, 'UTF-8') ?> :: Pimcore</title>

    <link rel="stylesheet" type="text/css" href="/pimcore/static6/js/lib/ext/classic/theme-triton/resources/theme-triton-all.css"/>
    <link rel="stylesheet" type="text/css" href="/pimcore/static6/css/admin.css"/>

    <style type="text/css">
        body {
            min-height: 600px;
        }
    </style>
    
</head>

<body>

<script type="text/javascript">
    var pimcore_version = "<?= \Pimcore\Version::getVersion() ?>";
</script>

<?php

$scripts = array(
    // library
    "lib/prototype-light.js",
    "lib/jquery.min.js",
    "lib/ext/ext-all.js",
    "lib/ext/classic/theme-triton/theme-triton.js",
);

?>

<?php foreach ($scripts as $scriptUrl) { ?>
<script type="text/javascript" src="/pimcore/static6/js/<?= $scriptUrl ?>"></script>
<?php } ?>


<script type="text/javascript">

    var errorMessages = '<b>ERROR:</b><br /><?= implode("<br />", $this->errors) ?>';
    var installdisabled = false;

    <?php if (!empty($this->errors)) { ?>
        installdisabled = true;
    <?php } ?>

    Ext.onReady(function() {

        Ext.Ajax.setDisableCaching(true);
        Ext.Ajax.setTimeout(900000);

        var win = new Ext.Window({
            width: 450,
            closable: false,
            closeable: false,
            y: 20,
            items: [
                {
                    xtype: "panel",
                    id: "logo",
                    border: false,
                    manageHeight: false,
                    bodyStyle: "padding: 20px 10px 5px 10px",
                    html: '<div align="center"><img width="200" src="/pimcore/static6/img/logo.svg" align="center" /></div>'
                },
                {
                    xtype: "panel",
                    id: "install_errors",
                    border: false,
                    bodyStyle: "color: red; padding: 10px",
                    html: errorMessages,
                    hidden: !installdisabled
                },
                {
                    xtype: "form",
                    id: "install_form",
                    defaultType: "textfield",
                    bodyStyle: "padding: 10px",
                    items: [
                        {
                            title: "MySQL Settings",
                            xtype: "fieldset",
                            defaults: {
                                width: 380
                            },
                            items: [{
                                    xtype: "combo",
                                    name: "mysql_adapter",
                                    fieldLabel: "Adapter",
                                    store: [
                                        ["Mysqli", "Mysqli"],
                                        ["Pdo_Mysql", "Pdo_Mysql"]
                                    ],
                                    mode: "local",
                                    value: "Pdo_Mysql",
                                    triggerAction: "all"
                                },
                                {
                                    xtype: "textfield",
                                    name: "mysql_host_socket",
                                    fieldLabel: "Host / Socket",
                                    value: "localhost"
                                },
                                {
                                    xtype: "textfield",
                                    name: "mysql_port",
                                    fieldLabel: "Port",
                                    value: "3306"
                                },
                                {
                                    xtype: "textfield",
                                    name: "mysql_username",
                                    fieldLabel: "Username"
                                },
                                {
                                    xtype: "textfield",
                                    name: "mysql_password",
                                    fieldLabel: "Password"
                                },
                                {
                                    xtype: "textfield",
                                    name: "mysql_database",
                                    fieldLabel: "Database"
                                }
                            ]
                        },
                        {
                            title: "Admin User",
                            xtype: "fieldset",
                            defaults: {
                                width: 380
                            },
                            items: [
                                {
                                    xtype: "textfield",
                                    name: "admin_username",
                                    fieldLabel: "Username",
                                    value: "admin"
                                },
                                {
                                    xtype: "textfield",
                                    name: "admin_password",
                                    fieldLabel: "Password"
                                }
                            ]
                        }
                    ]
                }
            ],
            bbar: [{
                    id: "check_button",
                    text: "Check Requirements",
                    icon: "/pimcore/static6/img/icon/laptop_magnify.png",
                    handler: function () {
                        window.open("/install/check/?" + Ext.urlEncode(Ext.getCmp("install_form").getForm().getFieldValues()));
                    }
                },"->",
                {
                    text: "<b>Install Now!</b>",
                    icon: "/pimcore/static6/img/icon/accept.png",
                    disabled: installdisabled,
                    handler: function (btn) {

                        btn.disable();
                        Ext.getCmp("install_form").hide();
                        Ext.getCmp("check_button").hide();

                        Ext.getCmp("install_errors").show();
                        Ext.getCmp("install_errors").update("Installing ...");

                        Ext.Ajax.request({
                            url: "/install/index/install",
                            method: "post",
                            params: Ext.getCmp("install_form").getForm().getFieldValues(),
                            success: function (transport) {
                                try {
                                    var response = Ext.decode(transport.responseText);
                                    if (response.success) {
                                        location.href = "/admin/";
                                    }
                                }
                                catch (e) {
                                    Ext.getCmp("install_errors").update(transport.responseText);
                                    Ext.getCmp("install_form").show();
                                    Ext.getCmp("check_button").show();
                                    btn.enable();
                                }
                            },
                            failure: function (transport) {
                                Ext.getCmp("install_errors").update("Failed: " + transport.responseText);
                                Ext.getCmp("install_form").show();
                                Ext.getCmp("check_button").show();
                                btn.enable();
                            }
                        });
                    }
                }
            ],
            listeners: {
                afterrender: function () {
                    // no idea why this is necessary to layout the window correctly
                    window.setTimeout(function () {
                        win.updateLayout();
                    }, 1000);
                }
            }
        });

        win.show();
    });

</script>

</body>
</html>
