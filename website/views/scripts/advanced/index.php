
<?php $this->template("/content/default.php"); ?>


<?php if($this->documents->getTotalCount() > 0) { ?>
    <h3><?= $this->translate("Total: %s", $this->documents->getTotalCount()); ?></h3>
    <ul>
        <?php foreach($this->documents as $doc) { ?>
            <li><a href="<?= $doc; ?>"><?= $doc->getProperty("navigation_name"); ?></a></li>
        <?php } ?>
    </ul>
<?php } ?>

