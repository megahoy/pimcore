<?php $this->template("/includes/content-headline.php"); ?>

<?= $this->areablock("content"); ?>



<div class="row">
    <div class="col-lg-6">
        <h2><?= $this->translate("Download compiled"); ?></h2>

        <p><?= $this->translate("Fastest way to get started: get the compiled and minified versions of our CSS, JS, and images. No docs or original source files."); ?></p>

        <p><a class="btn btn-large btn-primary" href="#"><?= $this->translate("Download"); ?></a></p>
    </div>
    <div class="col-lg-6">
        <?php /* placeholder example */ ?>
        <h3><?= $this->translate("Download Now (%s)", Zend_Date::now()->get(Zend_Date::DATE_MEDIUM)); ?></h3>
        <p><?= $this->translate("Get the original files for all CSS and JavaScript, along with a local copy of the docs by downloading the latest version directly from GitHub."); ?></p>

        <p><a class="btn btn-large btn-default" href="#"><?= $this->translate("Download"); ?></a></p>
    </div>
</div>

<?= $this->areablock("contentBottom"); ?>

