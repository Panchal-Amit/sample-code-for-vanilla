<?php

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
    echo"<div width='80%' align='center'>";
    echo" <img src='/images/logo.jpg' align='center'>";
    echo"</div>";
    echo"<div width='80%' align='center'>";
    echo "Server Time : ".date('Y-m-d H:i:s' );
    echo "<br> Version : ". $router->app->version();
    echo"</div>";
    return "";

});

$router->get('/sample' ,function(){
    return RestResponseFactory::ok((object)[], "Perfect you got the right parameters for header")->toJSON();
});



