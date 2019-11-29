<?php

namespace App\Api;
use \Route;

class ApiV1Routes extends ApiRoutes
{

    /**
     * This routes are TEST only run if Enviroment is Local
     * This array works like this
     * [url, controller, controllerFunction, method(optional) = get]
     */
    protected $testRoutes = [
       /* ["pass_get", "Api\TestController", "passGet", "get"],
        ["pass_post", "Api\TestController", "passPost", 'post'],
        ["log/{message}", "Api\TestController", "testLog"],*/
        //["generate/appointments", "Test\TestController", "generateAppointments"]
    ];

    /**
     * This array works like this
     * [url, controller, controllerFunction, method(optional) = get]
     */
    protected $routes = [
        /*
        ["ping", "Api\TestController", "ping"],
        ["getLanguages/{updateKey}", "Api\GeneralController", "getLanguages"] */
    ];

    protected $autoControllers = [
        "auth" => \App\Http\Controllers\Api\AuthController::class,
        "admin" => \App\Http\Controllers\Api\AdminController::class,
        "sections" => \App\Http\Controllers\Api\ModelController::class,
        'maps' => \App\Http\Controllers\Api\MapsController::class,
        'locale' => \App\Http\Controllers\Api\InternationalizationController::class
    ];
}