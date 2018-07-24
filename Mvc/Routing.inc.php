<?php

include_once 'Helper.inc.php';
include_once 'RequestContext.inc.php';


class Routing {

    private $registeredRoutes = [];
    
    private $rootPath = null;
    private $controllerPath = '../Controller/{controller}Controller.php';

    public function SetRootPath(string $path) {

        $this->rootPath = $path;

    }

    public function SetControllerPath(string $path) {

        $this->controllerPath = $path;

    }

    public function RegisterRoute(string $routeName, string $routeUrl, array $routeDefaults = null, array $routeConstraints = null) {

        if (is_null($routeName) || empty($routeName)) {
            trigger_error('$routeName cannot be null or empty.', E_USER_ERROR);
        }
        else if (is_null($routeUrl) || empty($routeUrl)) {
            trigger_error('$routeUrl cannot be null or empty.', E_USER_ERROR);
        }
        else if (substr($routeUrl, 0, 1) == '/') {
            trigger_error('$routeUrl cannot start with "/".', E_USER_ERROR);
        }
        
        $ctrlPos = strpos($routeUrl, '{controller}');
        if (!$ctrlPos && is_array($routeDefaults) && !array_key_exists('controller', $routeDefaults)) {
            trigger_error('$routeUrl or $routeDefaults must define the controller name.', E_USER_ERROR);
        }

        $routeSettings = [
            'name' => $routeName,
            'url' => $routeUrl,
            'parts' => explode('/', $routeUrl),
            'defaults' => $routeDefaults,
            'constraints' => $routeConstraints
        ];

        $this->registeredRoutes[$routeName] = $routeSettings;

    }

    private function CreateRequestContext() {
        
        $url     = getCurrentUrl();
        $urlData = parse_url($url);

        $requestContext = new RequestContext();
        $requestContext->Url = $url;
        
        $requestContext->Path = $urlData['path'];
        $requestContext->Path = str_removeFromStart($requestContext->Path, '/');

        if (!empty($this->rootPath)) {
            $requestContext->Path = str_removeFromStart($requestContext->Path, $this->rootPath);
            $requestContext->Path = str_removeFromStart($requestContext->Path, '/');
        }

        if (array_key_exists('query', $urlData)) {
            $requestContext->Query = $urlData['query'];    
        } else {
            $requestContext->Query = [];
        }
        
        return $requestContext;

    }

    private function MatchRoute(RequestContext $requestContext) {

        $urlParts = explode('/', $requestContext->Path);
        $urlPartsCount = count($urlParts);
        
        $routeValues = [];

        foreach ($this->registeredRoutes as $route) {
            $routePartsCount = count($route['parts']);
            $isMatch = true;

            for ($routePartIndex = 0; $routePartIndex < $routePartsCount; $routePartIndex++) {                
                $routePart = $route['parts'][$routePartIndex];

                if ($urlPartsCount > $routePartIndex) {
                    
                    $routePartValueMatchResult = preg_match('/{([a-zA-Z0-9_-]+?)}/', $routePart, $routePartMatches);
                    if ($routePartValueMatchResult > 0) {
                        // dynamic part
                        $routePartName = $routePartMatches[1];
                        $routeValues[$routePartName] = $urlParts[$routePartIndex];

                        // todo: respect constrains

                    } else {
                        // static part
                        if ($urlParts[$routePartIndex] != $routePart) {
                            $isMatch = false;
                        }
                    }

                }

                if (!$isMatch) {
                    break;
                }
            }

            if ($isMatch) {
                
                // todo: resolve controller name
                // todo: resolve action name
                
                // todo: pass query string values to route values
                // todo: pass form post values to route values

                // matching route found
                return [
                    'route' => $route,
                    'values' => $routeValues,
                    'controller' => 'todo',
                    'action' => 'todo'
                ];

            }

        }

        // no matching route found
        return null;

    }

    //private function ControllerClassAutoLoader($controllerName) {
    //
    //    $fullPath = str_replace('{controller}', $controllerName, $this->controllerPath);
    //    include_once $fullPath;
    //
    //}

    private function HandleRoute(RequestContext $requestContext, array $routeSettings) {
        
        //print_pre($requestContext);
        //print_pre($routeSettings);

        // todo: call matching controller / action

        // commands:
        // method_exists($controllerInstance, 'methodName');

        $controllerName  = 'Page';
        $actionName      = 'Display';

        if (!class_exists($controllerName, false)) {
            // try to autoload
            $fullPath = str_replace('{controller}', $controllerName, $this->controllerPath);
            include_once $fullPath;
            
            //trigger_error('Could not find a controller with name "' . 'Page' . '".', E_USER_ERROR);
        }

        //$actionMethodInfo = new ReflectionMethod($controllerName . 'Controller', $actionName);
        //$params = $actionMethodInfo->getParameters();
        //foreach ($params as $param) {
        //    //$param is an instance of ReflectionParameter
        //    //echo 'paramName: ' . $param->getName() . '<br>';
        //    //echo 'optional: ' . $param->isOptional() . '<br>';
        //}

        $controllerInfo = new ReflectionClass($controllerName . 'Controller');
        $controllerInstance = $controllerInfo->newInstance();

        $actionInfo = $controllerInfo->getMethod($actionName);
        
        $actionParameters = $actionInfo->getParameters();
        foreach ($actionParameters as $parameter) {
            //http://php.net/manual/de/class.reflectionparameter.php
            //echo $parameter->getName();
            //echo $parameter->isOptional();
            //echo $parameter->allowsNull();
            //echo $parameter->getPosition();
        }

        $actionInfo->invokeArgs($controllerInstance, ['test 123']);
        //$actionInfo->invoke($controllerInstance, ['test 123']);

    }

    public function HandleRequest() {
        
        if (count($this->registeredRoutes) == 0) {
            trigger_error('There must be at least one registered route.', E_USER_ERROR);
        }
        
        //spl_autoload_register([$this, 'ControllerClassAutoLoader']);

        $requestContext = $this->CreateRequestContext();
        
        $matchedRoute = $this->MatchRoute($requestContext);
        if ($matchedRoute == null) {
            trigger_error('No matching route found', E_USER_ERROR);
        }

        $this->HandleRoute($requestContext, $matchedRoute);

    }

}

?>