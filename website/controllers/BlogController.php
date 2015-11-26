<?php

use Website\Controller\Action;
use Pimcore\Model\Object;

class BlogController extends Action
{
    public function indexAction() {
        $this->enableLayout();

        // get a list of news objects and order them by date
        $blogList = new Object\BlogArticle\Listing();
        $blogList->setOrderKey("date");
        $blogList->setOrder("DESC");

        $conditions = [];

        if($this->getParam("category")) {
            $conditions[] = "categories LIKE " . $blogList->quote("%," . (int) $this->getParam("category") . ",%");
        }

        if($this->getParam("archive")) {
            $conditions[] = "DATE_FORMAT(FROM_UNIXTIME(date), '%Y-%c') = " . $blogList->quote($this->getParam("archive"));
        }

        if(!empty($conditions)) {
            $blogList->setCondition(implode(" AND ", $conditions));
        }

        $paginator = \Zend_Paginator::factory($blogList);
        $paginator->setCurrentPageNumber( $this->getParam('page') );
        $paginator->setItemCountPerPage(5);

        $this->view->articles = $paginator;

        // get all categories
        $categories = Object\BlogCategory::getList(); // this is an alternative way to get an object list
        $this->view->categories = $categories;

        // archive information, we have to do this in pure SQL
        $db = \Pimcore\Db::get();
        $ranges = $db->fetchCol("SELECT DATE_FORMAT(FROM_UNIXTIME(date), '%Y-%c') as ranges FROM object_5 GROUP BY DATE_FORMAT(FROM_UNIXTIME(date), '%b-%Y') ORDER BY ranges ASC");
        $this->view->archiveRanges = $ranges;
    }

    public function detailAction() {
        $this->enableLayout();

        // "id" is the named parameters in "Static Routes"
        $article = Object\BlogArticle::getById($this->getParam("id"));

        if(!$article instanceof Object\BlogArticle || !$article->isPublished()) {
            // this will trigger a 404 error response
            throw new \Zend_Controller_Router_Exception("invalid request");
        }

        $this->view->article = $article;
    }

    public function sidebarBoxAction() {

        $items = (int) $this->getParam("items");
        if(!$items) {
            $items = 3;
        }

        // this is the alternative way of getting a list of objects
        $blogList = Object\BlogArticle::getList([
            "limit" => $items,
            "order" => "DESC",
            "orderKey" => "date"
        ]);

        $this->view->articles = $blogList;
    }
}
