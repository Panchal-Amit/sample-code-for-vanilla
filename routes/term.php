<?php

/*
  |--------------------------------------------------------------------------
  | Application Cart Routes
  |--------------------------------------------------------------------------
  |
  | Here is where you can register all of the routes for localization of S2S and ICE
  |
 */


$router->group(['prefix' => 'term'], function () use ($router) {
    $router->post('/create', 'LocalizationController@create');
    $router->post('/create_term_translation', 'LocalizationController@createTermTranslation');
    $router->get('/supported_language', 'LocalizationController@getSupportedLanguage');
    $router->post('/term_by_language', 'LocalizationController@getTermByLanguage');
    $router->get('/all_term_by_language', 'LocalizationController@getAllTermByLanguage');
});


