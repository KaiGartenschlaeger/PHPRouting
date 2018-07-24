<?php

include_once dirname(__DIR__) . '/Mvc/Controller.inc.php';

class PageController extends Controller {

    public function Display(string $pageName = null) {

        echo 'PageController.Display called, pageName = ' . $pageName;
        
    }

}

?>