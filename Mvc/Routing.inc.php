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

        $route = [
            'name' => $routeName,
            'url' => $routeUrl,
            'parts' => explode('/', $routeUrl),
            'defaults' => $routeDefaults,
            'constraints' => $routeConstraints
        ];

        $this->registeredRoutes[$routeName] = $route;

    }

    private function CreateRequestContext() {
        
        $url     = getCurrentUrl();
        $urlData = parse_url($url);

        $request = new RequestContext();
        $request->Url = $url;
        
        $request->Path = $urlData['path'];
        $request->Path = str_removeFromStart($request->Path, '/');

        if (!empty($this->rootPath)) {
            $request->Path = str_removeFromStart($request->Path, $this->rootPath);
            $request->Path = str_removeFromStart($request->Path, '/');
        }

        if (array_key_exists('query', $urlData)) {
            $request->Query = $urlData['query'];    
        } else {
            $request->Query = [];
        }
        
        return $request;

    }

    private function MatchRoute(RequestContext $request) {

        $urlParts = explode('/', $request->Path);
        $urlPartsCount = count($urlParts);

        $routeValues = [];

        foreach ($this->registeredRoutes as $route) {

            $routePartsCount = count($route['parts']);
            $isMatch = true;
            $hasConstraints = is_array($route['constraints']);

            for ($routePartIndex = 0; $routePartIndex < $routePartsCount; $routePartIndex++) {                
                $routePart = $route['parts'][$routePartIndex];

                if ($urlPartsCount > $routePartIndex) {
                    
                    $routePartValueMatchResult = preg_match('/{([a-zA-Z0-9_-]+?)}/', $routePart, $routePartMatches);
                    if ($routePartValueMatchResult > 0) {

                        // dynamic part
                        $routePartName = $routePartMatches[1];
                        
                        if ($hasConstraints && array_key_exists($routePartName, $route['constraints'])) {
                            $constraintPattern = $route['constraints'][$routePartName];

                            // todo: respect constrains
                            if (preg_match('/^' . $constraintPattern . '$/', $urlParts[$routePartIndex], $matches)) {

                                $routeValues[$routePartName] = $urlParts[$routePartIndex];
                                $isMatch = true;

                            } else {

                                $isMatch = false;

                            }

                        } else {

                            $routeValues[$routePartName] = $urlParts[$routePartIndex];

                        }

                    } else {

                        // static part
                        if (strtolower($urlParts[$routePartIndex]) != strtolower($routePart)) {
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
                $controllerName = $route['defaults']['controller'];
                if (array_key_exists('controller', $routeValues)) {
                    $controllerName = $routeValues['controller'];
                }

                // todo: resolve action name
                $actionName = $route['defaults']['action'];
                if (array_key_exists('action', $routeValues)) {
                    $actionName = $routeValues['action'];
                }
                
                if (empty($controllerName) || empty($actionName)) {
                    return null;
                }

                // todo: pass query string values to route values
                // todo: pass form post values to route values

                // matching route found
                return [
                    'route' => $route,
                    'values' => $routeValues,
                    'controller' => $controllerName,
                    'action' => $actionName
                ];

            }

        }

        // no matching route found
        return null;

    }

    private function HandleRoute(RequestContext $request, array $route) {
        
        $controllerName  = $route['controller'];
        $actionName      = $route['action'];

        if (!class_exists($controllerName . 'Controller', false)) {
            
            // try to autoload
            $fullPath = str_replace('{controller}', $controllerName, $this->controllerPath);
            if (file_exists($fullPath)) {
                include_once $fullPath;
            }

        }

        if (!class_exists($controllerName . 'Controller', false)) {
            trigger_error('Could not resolve "' . $controllerName . 'Controller".', E_USER_WARNING);
            return false;
        }

        $controllerInfo = new ReflectionClass($controllerName . 'Controller');
        
        if (!$controllerInfo->hasMethod($actionName)) {
            trigger_error('Could not resolve action "' . $actionName . '" for controller "' . $controllerName . 'Controller".', E_USER_WARNING);
            return false;
        }

        $actionInfo = $controllerInfo->getMethod($actionName);
        
        $this->HandleActionCall($request, $route, $controllerInfo, $actionInfo);

        return true;
            
    }

    private function GetRouteValue(array $route, string $name, &$value = null) {
        
        if (array_key_exists($name, $route['values'])) {

            $value = $route['values'][$name];
            return true;

        } else if (array_key_exists($name, $route['route']['defaults'])) {
            
            $value = $route['route']['defaults'][$name];
            return true;

        }
        
        return false;

    }

    private function TryConvertValue(ReflectionNamedType $targetType, $value, &$convertedValue = null) {

        switch ($targetType->getName()) {

            case 'string':
                $convertedValue = $value;
                return true;

            case 'int':
                $convertedValue = intval($value);
                return true;

            case 'float':
                $convertedValue = doubleval($value);
                return true;

            case 'bool':
                $convertedValue = boolval($value);
                return true;
            
        }

        return false;

    }

    private function TryGetDefaultValueByType(ReflectionNamedType $targetType, &$value) {

        switch ($targetType->getName()) {

            case 'string':
                $value = '';
                return true;

            case 'int':
            case 'float':
                $value = 0;
                return true;

            case 'bool':
                $value = false;
                return true;
            
        }

        return false;

    }

    private function HandleActionCall(RequestContext $request, array $route, ReflectionClass $controllerInfo, ReflectionMethod $actionInfo) {

        $parameters = $actionInfo->getParameters();
        
        if (count($parameters) > 0) {

            $args = [];
            
            foreach ($parameters as $param) {
        
                $paramName  = $param->getName();
                $paramType  = $param->getType();
                $isOptional = $param->isOptional();
                $allowsNull = $param->allowsNull();
                
                if ($isOptional) {

                    // optional
                    
                    if ($this->GetRouteValue($route, $paramName, $routeValue)) {

                        if ($this->TryConvertValue($paramType, $routeValue, $convertedRouteValue)) {

                            array_push($args, $convertedRouteValue);

                        } else {

                            if ($allowsNull) {

                                array_push($args, null);

                            } else {

                                trigger_error('Not supported', E_USER_ERROR);

                            }

                        }

                    } else {

                        $defaultValue = $param->getDefaultValue();
                        array_push($args, $defaultValue);

                    }

                } else if ($allowsNull) {

                    // nullable

                    if ($this->GetRouteValue($route, $paramName, $routeValue)) {

                        if ($this->TryConvertValue($paramType, $routeValue, $convertedRouteValue)) {

                            array_push($args, $convertedRouteValue);

                        } else {

                            array_push($args, null);

                        }

                    } else {

                        array_push($args, null);

                    }
        
                } else {
        
                    // typed

                    if ($paramType != null) {
        
                        if ($this->GetRouteValue($route, $paramName, $routeValue) &&
                            $this->TryConvertValue($paramType, $routeValue, $convertedValue)) {

                                array_push($args, $convertedValue);

                        } else if ($this->TryGetDefaultValueByType($paramType, $typedDefaultValue)) {

                                array_push($args, $typedDefaultValue);

                        } else {

                            trigger_error('Not supported', E_USER_ERROR);

                        }
                        
                    } else {
        
                        if ($this->GetRouteValue($route, $paramName, $routeValue)) {

                            array_push($args, $routeValue);

                        } else {

                            array_push($args, null);

                        }

                    }
        
                }
                
            }
        
            $controllerInstance = $controllerInfo->newInstance();
            $actionInfo->invokeArgs($controllerInstance, $args);
        
        } else {
        
            $controllerInstance = $controllerInfo->newInstance();
            $actionInfo->invoke($controllerInstance);
        
        }

    }

    public function HandleRequest() {
        
        if (count($this->registeredRoutes) == 0) {
            trigger_error('There must be at least one registered route.', E_USER_ERROR);
        }
        
        $request = $this->CreateRequestContext();
        
        $route = $this->MatchRoute($request);
        if ($route == null) {
            trigger_error('No matching route found', E_USER_ERROR);
        }

        $this->HandleRoute($request, $route);

    }

}

?>