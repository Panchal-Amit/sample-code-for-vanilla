<?php

/*
  |--------------------------------------------------------------------------
  | Application Cart Routes
  |--------------------------------------------------------------------------
  |
  | Here is where you can register all of the routes for an application.
  | It is a breeze. Simply tell Lumen the URIs it should respond to
  | and give it the Closure to call when that URI is requested.
  |
 */



$router->post('user/validate_code', ['uses' => 'UserController@validateCode'], function () {
    
});
$router->post('user/send_code', ['uses' => 'UserController@sendCode'], function () {
    
});
$router->post('user/login', ['uses' => 'UserController@login'], function () {
    
});
//$router->post('user/forgot_password', ['uses' => 'UserController@forgotPassword'], function () {});

$router->group(['prefix' => 'user', 'middleware' => ['Auth']], function () use ($router) {
    $router->post('/get_profile', 'UserController@getProfile');
    $router->post('/update_profile', 'UserController@updateProfile');
    $router->post('/update_profile', 'UserController@updateProfile');
    $router->post('/get_vehicle', 'UserVehicleController@getMyVehicle');
    $router->post('/add_vehicle', 'UserVehicleController@assignVehicle');
    $router->post('/remove_vehicle', 'UserVehicleController@removeVehicle');
    $router->post('/get_my_dealer', 'UserController@getMyDealer');
    $router->post('/assign_dealer', 'UserController@assignUserDealer');
    $router->post('/make_default_dealer', 'UserController@makeDefaultDealer');
    $router->post('/update_vehicle_odometer', 'UserVehicleController@updateVehicleOdometer');
    $router->post('/communication_preferences', 'UserController@communicationPreferences');    
});
// As require dfx token for send sms
$router->group(['prefix' => 'user', 'middleware' => ['DfxAuth']], function () use ($router) {
    $router->post('/forgot_password', 'UserController@forgotPassword');
});
$router->group(['prefix' => 'user', 'middleware' => ['Auth', 'DfxAuth']], function () use ($router) {
    $router->post('/makes', 'UserVehicleController@getMakes');
    $router->post('/make_year/{id:[1-9][0-9]*}', 'UserVehicleController@getYearsOfMakes');
    $router->post('/get_transmission', 'UserVehicleController@getTransmission');
    $router->post('/get_advisors/{appointmentType:[1-2]*}', 'UserController@getAdvisors');
    $router->post('/get_models', 'UserVehicleController@getModels');
    $router->post('/get_appointment_time_slots', 'UserController@getAppointmentTimeSlots');
    $router->post('/book_appointment', 'UserController@bookAppointment');
    $router->post('/get_recall', 'UserVehicleController@getRecall');
    $router->post('/get_vehicle_health', 'UserVehicleController@getVehicleHealth');
    $router->post('/get_vehicle_mileage', 'UserVehicleController@getVehicleMileage');
    $router->post('/get_maintenance_service', 'UserVehicleController@getMaintenanceService');
    $router->post('/get_vehicle_upcoming_appointment', 'UserVehicleController@getUpcomingAppointment');
    $router->post('/get_appointment_by_id', 'UserController@getAppointmentById');
    $router->post('/get_vehicle_status', 'UserVehicleController@getVehicleStatus');
    $router->post('/post_user', 'UserController@postUser');
    $router->post('/get_transportation_options', 'UserController@getTransportationOption');
    $router->post('/get_vehicle_service_history', 'UserVehicleController@getVehicleServiceHistory');
    $router->post('/get_dealer_location', 'UserController@getDealerLocation');
    $router->post('/get_invoice', 'UserController@getInvoice');
    $router->post('/get_writeup_pdf', 'UserVehicleController@getWriteupPdf');
    $router->post('/update_communication_preferences', 'UserController@updateCommunicationPreferences');
});
$router->group(['prefix' => 'user', 'middleware' => ['Auth']], function () use ($router) {
    $router->post('/get_notification', 'NotificationController@getAllNotification');
    $router->post('/pull_notification', 'NotificationController@pullNotification');
    $router->post('/dismiss_notification', 'NotificationController@dismissNotification');
    $router->post('/read_notification', 'NotificationController@updatStatusNotification');
    $router->get('/get_document', 'UserController@getDocument');
});

//Notification
//@Notes : auth-token is not added as assuming it will be openedge
$router->group(['prefix' => 'notification'], function () use ($router) {
    $router->post('push_notification', 'NotificationController@pushNotification');
});

