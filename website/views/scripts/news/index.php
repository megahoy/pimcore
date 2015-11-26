<?php $this->template("/includes/content-headline.php"); ?>

<?= $this->areablock("content"); ?>

<?php foreach ($this->news as $news) { ?>
    <div class="media">
        <?php
            $detailLink = $this->url([
                "id" => $news->getId(),
                "text" => $news->getTitle(),
                "prefix" => $this->document->getFullPath()
            ], "news", true);
        ?>
        <?php if($news->getImage_1()) { ?>
            <a class="pull-left" href="<?= $detailLink; ?>">
                <?= $news->getImage_1()->getThumbnail("newsList")->getHTML(["class" => "media-object"]); ?>
            </a>
        <?php } ?>

        <div class="media-body">
            <h4 class="media-heading">
                <a href="<?= $detailLink; ?>"><?= $news->getTitle(); ?></a>
                <br />
                <small><i class="glyphicon glyphicon-calendar"></i> <?= $news->getDate()->get(Zend_Date::DATETIME_MEDIUM); ?></small>
            </h4>
            <?= $news->getShortText(); ?>
        </div>
    </div>
<?php } ?>


<!-- pagination start -->
<?= $this->paginationControl($this->news, 'Sliding', 'includes/paging.php', [
   'urlprefix' => $this->document->getFullPath() . '?page=',
   'appendQueryString' => true
]); ?>
<!-- pagination end -->