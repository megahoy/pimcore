<!DOCTYPE html>
<html>
<head>

    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <meta name="robots" content="noindex, nofollow" />

    <style type="text/css">
        body {
            font-family: Arial, Tahoma, Verdana;
            font-size: 12px;
        }

        h2 {
            font-size: 16px;
            margin: 0;
            padding: 0 0 5px 0;
        }

        table {
            border-collapse: collapse;
        }

        a {
            color: #0066cc;
        }

        .legend {
            display: inline-block;
            padding-right: 10px;
        }
    </style>

</head>
<body>

    <table cellpadding="20">
        <tr>
            <td valign="top">
                <h2>PHP</h2>
                <table border="1" cellpadding="3" cellspacing="0">
                    <?php foreach ($this->checksPHP as $check) { ?>
                        <tr>
                            <td><a href="<?= $check["link"]; ?>" target="_blank"><?= $check["name"]; ?></a></td>
                            <td><img src="/pimcore/static6/img/icon/<?php
                                if($check["state"] == "ok") {
                                    echo "accept";
                                } else if ($check["state"] == "warning") {
                                    echo "error";
                                } else {
                                    echo "delete";
                                }
                            ?>.png" /></td>
                        </tr>
                    <?php } ?>
                </table>
            </td>
            <td valign="top">
                <h2>MySQL</h2>
                <table border="1" cellpadding="3" cellspacing="0">
                    <?php foreach ($this->checksMySQL as $check) { ?>
                        <tr>
                            <td><?= $check["name"]; ?></td>
                            <td><img src="/pimcore/static6/img/icon/<?php
                                if($check["state"] == "ok") {
                                    echo "accept";
                                } else if ($check["state"] == "warning") {
                                    echo "error";
                                } else {
                                    echo "delete";
                                }
                            ?>.png" /></td>
                        </tr>
                    <?php } ?>
                </table>
            </td>
            <td valign="top">
                <h2>Filesystem</h2>
                <table border="1" cellpadding="3" cellspacing="0">
                    <?php foreach ($this->checksFS as $check) { ?>
                        <tr>
                            <td><?= $check["name"]; ?></td>
                            <td><img src="/pimcore/static6/img/icon/<?php
                                if($check["state"] == "ok") {
                                    echo "accept";
                                } else if ($check["state"] == "warning") {
                                    echo "error";
                                } else {
                                    echo "delete";
                                }
                            ?>.png" /></td>
                        </tr>
                    <?php } ?>
                </table>

                <br />
                <br />

                <h2>Applications &amp; System</h2>
                <table border="1" cellpadding="3" cellspacing="0">
                    <?php foreach ($this->checksApps as $check) { ?>
                        <tr>
                            <td><?= $check["name"]; ?></td>
                            <td><img src="/pimcore/static6/img/icon/<?php
                                if($check["state"] == "ok") {
                                    echo "accept";
                                } else if ($check["state"] == "warning") {
                                    echo "error";
                                } else {
                                    echo "delete";
                                }
                            ?>.png" /></td>
                        </tr>
                    <?php } ?>
                </table>
            </td>
        </tr>
    </table>


    <div class="legend">
        <p>
            <b>Explanation:</b>
        </p>
        <p>
            <span class="legend"><img src="/pimcore/static6/img/icon/accept.png" /> Everything ok.</span>
            <span class="legend"><img src="/pimcore/static6/img/icon/error.png" /> Recommended but not required.</span>
            <span class="legend"><img src="/pimcore/static6/img/icon/delete.png" /> Required.</span>
        </p>
    </div>

</body>
</html>