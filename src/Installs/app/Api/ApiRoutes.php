<?php

namespace App\Api;
use \Route;
use \ReflectionClass;
use \ReflectionMethod;
class ApiRoutes
{
    private $validMethods = ['get', 'post'];

    protected $testRoutes = [
    ];

    protected $routes = [
    ];

    protected $autoControllers = [];    

    protected $testPrefix = "test/";

    public static function run()
    {
        $class = get_called_class();
        $api = new $class();
        Route::prefix(env("API_PREFIX"))->group(function() use($api) {
            $api->setRoutes();
        });
    }

    protected function setRoutes()
    {
        $this->getAutoControllers();
        $this->handlePreflight();
        $this->processRoutes();
    }

    private function getAutoControllers()
    {
        foreach ($this->autoControllers as $pseudonim => $class) {
            $this->extractMethods($pseudonim, $class);
        }
    }

    protected function processRoutes()
    {
        $routeCollection = $this->routes;
        if(env('APP_ENV') === 'local')
        {
            for ($i=0; $i < count($this->testRoutes); $i++) {
                $this->testRoutes[$i][0] = $this->testPrefix.$this->testRoutes[$i][0];
            }
            $routeCollection = array_merge($routeCollection, $this->testRoutes);
        }

        foreach($routeCollection as $route)
        {
            $method = isset($route[3]) ? $route[3] : 'get';

            $controller = $method != 'resource' ? $route[1]."@".$route[2] : $route[1];

            Route::$method($route[0], $controller);
        }
    }

    protected function handlePreflight()
    {

        Route::options('{any}', function ($any) {
            return "success";
        })->where('any', '.*');

    }

    private function startsWith($haystack, $needle)
    {
         $length = strlen($needle);
         return (substr($haystack, 0, $length) === $needle);
    }

    private function extractMethods($pseudonim, $controllerClass) 
    {
        $class = new ReflectionClass($controllerClass);
        $methods = $class->getMethods(ReflectionMethod::IS_PUBLIC);
        
        $outMethods =  [];
        foreach ($methods as $m) {
            
            if ($m->class == $controllerClass) {
                $name = $m->name;
                $controller = "\\".$controllerClass;
                
                if($this->startsWith($name, "api_"))
                {   
                    $restExplode = explode("_", $name);
                    $restMethod = $restExplode[1];
                    $route = lcfirst($restExplode[2]);
                    
                    foreach($m->getParameters() as $param)
                    {
                        if($this->startsWith($param->name, "_"))
                        {
                            $route .= "/{{$param->name}}";
                        }
                    }

                    if(in_array($restMethod, ["get", "post"]))
                    {
                        $restMethod = $restExplode[1];

                        //echo "get".json_encode($m->getParameters());
                        array_push($this->routes, [
                            $pseudonim."/".$route,
                            $controller,
                            $name,
                            $restMethod
                        ]);
                    }
                }   
                                 
            }

            
        }


        return $outMethods;
    }
}

