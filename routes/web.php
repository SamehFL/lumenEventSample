<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$router->get('/', function () use ($router) {
    return $router->app->version();
});

$router->post('register','UserController@register');



$router->group([
    'middleware' => 'auth:api'
], function (\Laravel\Lumen\Routing\Router $router) {
    $router->post('update','UserController@update');
    $router->post('logout','UserController@logout');
    $router->get('userInfo','UserController@show');
    $router->delete('delete[/{id}]','UserController@destroy');
    $router->get('viewUserManipulationLog','UserController@viewUserManipulationLog');

});
