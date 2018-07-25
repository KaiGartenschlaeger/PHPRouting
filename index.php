<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Test</title>
</head>
<body>

<?php

include_once 'Mvc/Routing.inc.php';
include_once 'Controller/PageController.php';

//echo __DIR__ . '<br>';
//echo dirname(__DIR__);

$routing = new Routing();
$routing->SetRootPath('PHPRouting');
$routing->SetControllerPath($_SERVER['DOCUMENT_ROOT'] . '/PHPRouting/Controller/{controller}Controller.php');

$routing->RegisterRoute('DisplayPage', 'page/{pageName}', [ 'controller' => 'Page', 'action' => 'Display', 'pageName' => 'start' ]);
$routing->RegisterRoute('Default', '{controller}/{action}');

$routing->HandleRequest();

?>
</body>
</html>