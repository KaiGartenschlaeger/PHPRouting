# PHPRouting

## Konfiguration
Zur Verwendung muss eine Instanz der Klasse Routing erzeugt werden:
~~~php
$routing = new Routing();
~~~

Als nächstes sollte der Ordner-Pfad zu den Controller Klassen definiert werden:
~~~php
$routing->SetControllerPath($_SERVER['DOCUMENT_ROOT'] . '/PHPRouting/Controller/{controller}Controller.php');
~~~~

Danach kann damit begonnen werden, die einzelnen Routen zu definieren:
~~~php
$routing->RegisterRoute('DisplayById', 
    'page/{pageId}',
    [ 'controller' => 'Page', 'action' => 'DisplayById', 'pageId' => 1 ],
    [ 'pageId' => '\d+' ]);

$routing->RegisterRoute('DisplayByName', 
    'page/{pageName}', 
    [ 'controller' => 'Page', 'action' => 'DisplayByName', 'pageName' => 'start' ]);

$routing->RegisterRoute('Default', 
    '{controller}/{action}');
~~~

Über die Methode HandleRequest kann dann der aktuelle Request verarbeitet werden:
~~~php
$routing->HandleRequest();
~~~
