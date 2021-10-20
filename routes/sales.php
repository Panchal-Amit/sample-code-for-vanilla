<?php

/*
  |--------------------------------------------------------------------------
  | Application Cart Routes
  |--------------------------------------------------------------------------
  |
  | Here is where you can register all of the routes for an S2S application.
  |
 */

$router->group(['prefix' => 'sales'], function () use ($router) {
    // Auth Controller
    $router->get('/token', 'AuthController@getToken');
    $router->post('/login', 'AuthController@login');
    $router->get('/sendMail/{type:predelivery[1-9]*}', 'UserController@sendMail');

    //Get Configration
    $router->get('/configuration', 'AuthController@getConfiguration');
    $router->get('/get_dealer_location', 'AuthController@getDealerLocation');

    $router->get('/sales_rep', function(){
        $return = [
            'declaration' => 'reps_found',
            'status' => 'success',
            'payload' => [
                'sales_rep' => [
                    [
                        'id' => 1,
                        'empl_no' => 859,
                        'last_name' => 'Alkabra',
                        'first_name' => 'Ahmad',
                        'email' => 'aalkabra@magictoyota.com',
                    ],
                    [
                        'id' => 1,
                        'empl_no' => 33,
                        'last_name' => 'Test 1',
                        'first_name' => 'ZZZ',
                        'email' => 'xxxx@magictoyota.com',
                    ],
                    [
                        'id' => 1,
                        'empl_no' => 32,
                        'last_name' => 'YYY',
                        'first_name' => 'PPP',
                        'email' => 'yyyyyy@magictoyota.com',
                    ],
                ],
            ],
        ];
        return response()->json($return, 200);
    });


    // Vehicle Controller
    $router->post('/vehicle', 'VehicleController@getByVin');
    $router->post('/makes', 'VehicleController@getMakes');
    $router->post('/get_main_manufacturer', 'VehicleController@GetMainManufacturer');        
    $router->post('/make/{id:[1-9][0-9]*}/year', 'VehicleController@getYearsOfMakes');
    $router->post('/models', 'VehicleController@getModels');
    $router->post('/model/transmission', 'VehicleController@getTransmission');
    $router->post('/get_maintenance_service', 'VehicleController@getMaintenanceService');

    //GEO Calls
    $router->get('/get_all_country', 'CustomerController@getAllCountry');
    $router->post('/get_province', 'CustomerController@getProvince');
    $router->post('/get_county', 'CustomerController@getCounty');
    

    // Customer Controller
    $router->post('/new_customer', 'CustomerController@createNewCustomer');
    $router->post('/welcome_package', 'CustomerController@getWelcomePackage');
    $router->post('/send_welcome_package', 'CustomerController@sendWelcomePdf');
    $router->post('/send_pre_delivery_checklist', 'CustomerController@sendPreDeliveryChecklist');
    $router->post('/send_sms', 'CustomerController@sendSms');
    $router->post('/communication_preferences', 'CustomerController@communicationPreferences');
    $router->post('/update_communication_preferences', 'CustomerController@updateCommunicationPreferences');
    $router->get('/schedule_appointment/{appointmentType:[1-2]}/{token:[1-9][0-9]*}', 'CustomerController@getFirstAppointment');
    $router->post('/schedule_appointment_time_slots', 'CustomerController@scheduleAppointmentTimeSlots');
    $router->get('/advisors/{appointmentType:[1-9]*}', 'CustomerController@getAdvisors');
    $router->post('/schedule_transportation_options', 'CustomerController@scheduleTransportationOption');
    $router->post('/search_customer', 'CustomerController@searchCustomer');
    $router->post('/book_appointment', 'CustomerController@bookAppointment');
    $router->post('/forgot_password', 'CustomerController@forgotPassword');
    $router->post('/get_question', 'CustomerController@getQuestion');
    $router->post('/question_response', 'CustomerController@saveQuestionResponse');
    $router->post('/generate_preview', 'CustomerController@previewQuestionResponse');
    $router->post('/toggle_enrolled_ice', 'CustomerController@toggleEnrolledIce');
    
    
    // Localization Controller
    $router->get('/localization', 'LocalizationController@getLocalization');    
        
    //Survey    
    $router->get('form_credential', 'SurveyController@getFormCredential');        
    $router->put('guest_login', 'SurveyController@guestLogin');    
    $router->post('list_survey', 'SurveyController@surveyList');
    $router->post('section_list', 'SurveyController@sectionList');
    $router->post('section_detail', 'SurveyController@sectionDetail');
    $router->post('question_list', 'SurveyController@questionList');
    $router->post('question_option_list', 'SurveyController@QuestionOptionList');
    $router->post('section_response', 'SurveyController@sectionResponse');
    $router->post('get_survey_detail', 'SurveyController@SurveyDetail');
    $router->post('get_survey_response', 'SurveyController@SurveyResponse');
    $router->post('submit_survey', 'SurveyController@SubmitSurvey');
    $router->post('file_upload', 'SurveyController@saveFile');
    $router->put('redo_survey', 'SurveyController@associateParticipantToforms');
    $router->post('get_pdf', 'SurveyController@generatePdf');    
    $router->post('scan_vin', 'VehicleController@getOcrContent');
});


