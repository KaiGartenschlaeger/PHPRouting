<?php declare(strict_types=1); ?>

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

class RequestContext {
    public $Url = '';
}

class Routing {
    
    private $registeredRoutes = [];

    public function RegisterRoute(string $routeName, string $routeUrl, array $routeDefaults = null) {
        if (is_null($routeName) || empty($routeName)) {
            trigger_error('$routeName cannot be null or empty.', E_USER_ERROR);
        }
        else if (is_null($routeUrl) || empty($routeUrl)) {
            trigger_error('$routeUrl cannot be null or empty.', E_USER_ERROR);
        }
        else if (substr($routeUrl, 0, 1) == '/') {
            trigger_error('$routeUrl cannot start with "/".', E_USER_ERROR);
        }
        
        // todo: {controller} and {action} must be defined

        $this->registeredRoutes[$routeName] = [
            'name' => $routeName,
            'url' => $routeUrl,
            'parts' => explode('/', $routeUrl)
        ];
    }

    private function CreateRequestContext() {
        $requestContext = new RequestContext();
        
        $requestUrl = $_SERVER['REQUEST_URI'];
        if (substr($requestUrl, 0, 1) == '/') {
            $requestUrl = substr($requestUrl, 1);
        }

        $requestContext->Url = $requestUrl;
        
        return $requestContext;
    }

    private function MatchRoute(RequestContext $requestContext) {
        $urlParts = explode('/', $requestContext->Url);
        $urlPartsCount = count($urlParts);

        foreach ($this->registeredRoutes as $routeName => $routeSettings) {
            $routePartsCount = count($routeSettings['parts']);
            for ($partIndex = 0; $partIndex < $routePartsCount; $partIndex++) {                
                $continueProcessing = true;

                $routePartValue = $routeSettings['parts'][$partIndex];
                $urlPartValue   = $urlParts[$partIndex];
                
                $routePartValueMatchResult = preg_match('/{([a-zA-Z0-9_-]+?)}/', $routePartValue, $routePartValueMatches);
                if ($routePartValueMatchResult > 0) {
                    // route part is a placeholder
                    // todo: add value condition support
                } else {
                    // route part is a literal value
                    if ($routePartValue != $urlPartValue) {
                        $continueProcessing = false;
                    }
                }

                if (!$continueProcessing) {
                    break;
                }
            }

            if ($continueProcessing) {
                return $routeSettings;                
            }
        }

        return null;
    }

    private function HandleRoute(RequestContext $requestContext, array $routeSettings) {
        print_r($requestContext);
        print('<br>');
        print_r($routeSettings);




    }

    public function HandleRequest() {
        $requestContext = $this->CreateRequestContext();
        
        $matchedRoute = $this->MatchRoute($requestContext);
        if ($matchedRoute == null) {
            trigger_error('No matching route found', E_USER_ERROR);
        }

        $this->HandleRoute($requestContext, $matchedRoute);
    }

}

$routing = new Routing();
$routing->RegisterRoute('DisplayPage', 'page/{pageName}', [ 'controller' => 'Page', 'action' => 'Display' ]);
$routing->RegisterRoute('Default', '{controller}/{action}');

$routing->HandleRequest();


//if(in_array($intended_path,$uris_from_database)){
//  //show the page.
//} else {
//  $search_phrase = preg_replace('!/!',' ',$intended_path);
//  $search_phrase = mysqli_real_escape_string($search_phrase);
//  $sql = "SELECT * FROM pages WHERE MATCH (title,content) AGAINST ('$search_phrase');"
//}



//$ViewData['FirstName'] = 'Max';
//$ViewData['LastName']  = 'Mustermann';

//$ViewContent = file_get_contents("views/Login.html");

//preg_match("/{{([a-zA-Z]+?)}}", $ViewContent, $ViewBindings, PREG_OFFSET_CAPTURE);
//print_r($ViewBindings);

//preg_match_all("/{{([a-zA-Z]+?)}}/m", $ViewContent, $ViewBindings, PREG_SET_ORDER | PREG_OFFSET_CAPTURE | PREG_UNMATCHED_AS_NULL);
//foreach ($ViewBindings as $binding)
//{
//    $bindingName =      $binding[1][0];
//    $bindingNameLen =   strlen($bindingName);
//    $bindingIndex =     $binding[1][1];
//
//    $bindingValue = 'n/a';
//    if (array_key_exists($bindingName, $ViewData)) {
//        $bindingValue = $ViewData[$bindingName];
//    }
//
//    $ViewContent = substr_replace($ViewContent, $bindingValue, $bindingIndex - 2, strlen($bindingName) + 4);
//}

//echo $ViewContent;
?>

</body>
</html>