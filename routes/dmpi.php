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



$router->group(['prefix' => 'service', 'middleware' => ['Auth', 'DfxAuth']], function () use ($router) {
    $router->post('/get', 'ServiceController@getService');
    $router->post('/get_write_up', 'ServiceController@getWriteUp');
});

$router->group(['prefix' => 'service', 'middleware' => ['Auth', 'DfxAuth', 'DmpiAuth']], function () use ($router) {    
    $router->post('/get_dmpi', 'ServiceController@getDmpiService');
    $router->post('/get_all', 'ServiceController@getDmpiAllData');
    $router->post('/get_inspection_link', 'ServiceController@getInspectionLink');
    $router->post('/get_metadata', 'ServiceController@getMetaData');
    $router->post('/service_detail', 'ServiceController@getServiceDetail');
    $router->post('/approve_service', 'ServiceController@approveService');
    $router->post('/calculate_tax', 'ServiceController@calculateTax');
});
