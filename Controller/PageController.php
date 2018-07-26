<?php

include_once dirname(__DIR__) . '/Mvc/Controller.inc.php';

class PageController extends Controller {

    public function DisplayById(int $pageId) {

        echo 'PageController.DisplayById called, pageId = ' . $pageId;
        
    }

    public function DisplayByName(string $pageName) {

        echo 'PageController.DisplayByName called, pageName = ' . $pageName;
        
    }

}

?>