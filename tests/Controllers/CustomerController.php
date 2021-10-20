<?php

/**
 * CustomerController.php
 */

namespace Tests;

use Laravel\Lumen\Testing\DatabaseMigrations;
use Laravel\Lumen\Testing\DatabaseTransactions;

class CustomerController extends \TestCase {

    use DatabaseTransactions;

    /**
     * post customer
     * Case : successfully post customer
     * 
     * @return void
     * @author Amit Panchal <amit@unoindia.co>
     * @version 0.0.1
     * 
     * @covers App\Http\Controllers\CustomerController::createNewCustomer
     * 
     */
    public function testCreateNewCustomer() {

        $token = $this->getToken();

        // Scope Headers
        $scope_header = json_encode([
            'DealerId' => $token['user']['dealerId'],
            'CultureCode' => "en-us",
            'Environment' => config('dfx.environment'),
        ]);

        // Params
        $params = [
            'dealer_id' => $token['user']['dealerId'],
            'culture_code' => "en-us",
            'vehicle' => [
                'vin' => '1N4AL21E98C163960',
                'manufacturerId' => "41",
                'manufacturerName' => "NISSAN",
                'year' => "2008",
                'model' => "Altima",
                'transmission' => "CVT",
                'engine' => "4CYL",
                'driveTrain' => "2WD",
                'mileage' => 100000
            ],
            'customer' => [
                "customerId" => "53089116",
                'firstName' => 'Johnx45',
                'lastName' => 'Doe',
                'email' => 'email@email.com',
                'cellPhone' => '6479992222',
                'address' => '123 somewhere street',
                "address_2" => "Testing Address",
                'city' => 'toronto',
                'state' => 'ON',
                'country' => 'CA',
                'postalCode' => 'A1B 2C3',
                'county' => 'us',
                'lat' => '1.123456',
                'long' => '1.123456'
            ],
            "customerPreference" => [
                [
                    "notificationPreference" => "InspectionResults",
                    "sms" => true,
                    "pushNotification" => true,
                    "email" => true,
                    "callFromDealership" => true
                ],
                [
                    "notificationPreference" => "AppointmentReminderAndConfirmations",
                    "sms" => true,
                    "pushNotification" => true,
                    "email" => false,
                    "callFromDealership" => false
                ],
                [
                    "notificationPreference" => "VehicleRelatedAlerts",
                    "sms" => false,
                    "pushNotification" => false,
                    "email" => true,
                    "callFromDealership" => true
                ],
                [
                    "notificationPreference" => "MarketingAlerts",
                    "sms" => true,
                    "pushNotification" => false,
                    "email" => true,
                    "callFromDealership" => false
                ]
            ],
            "isNewVehicle" => true,
            "vehicleDeliveryDate" => "2018-04-10"
        ];


        $vehicleParams = [
            'vin' => '1N4AL21E98C163960',
            'dealer_id' => $token['user']['dealerId'],
            'culture_code' => "en-us"
        ];
        $this->post('/sales/makes', $vehicleParams, ['HTTP_Authorization' => 'bearer ' . $token['token']['access_token'],
            'HTTP_Scope' => $scope_header
        ]);

        $this->post('/sales/vehicle', $vehicleParams, ['HTTP_Authorization' => 'bearer ' . $token['token']['access_token']]);

        $this->post('/sales/new_customer', $params, [
            'HTTP_Authorization' => 'bearer ' . $token['token']['access_token'],
            'HTTP_Scope' => $scope_header
        ]);

        $response = $this->response;
        $raw_content = $this->response->content();

        $this->assertEquals(200, $this->response->status(), $raw_content);
        $content = json_decode($raw_content, true);

        $this->assertArrayHasKey('declaration', $content, $raw_content);
        $this->assertArrayHasKey('status', $content, $raw_content);
        $this->assertArrayHasKey('payload', $content, $raw_content);
        $this->assertArrayHasKey('token', $content['payload'], $raw_content);
        $this->assertArrayHasKey('customerId', $content['payload'], $raw_content);

        $this->assertEquals('success', $content['status'], $raw_content);
        $this->assertEquals('customer_created', $content['declaration'], $raw_content);
    }

    /**
     * get welcome package link
     * Case : successfully get welcome package
     * 
     * @return void
     * @author Amit Panchal <amit@unoindia.co>
     * @version 0.0.1
     * 
     * @covers App\Http\Controllers\CustomerController::getWelcomePackage
     * 
     */
    public function testGetWelcomePackage() {

        $token = $this->getToken();
        // Scope Headers
        $scope_header = json_encode([
            'DealerId' => $token['user']['dealerId'],
            'CultureCode' => "en-us",
            'Environment' => config('dfx.environment'),
        ]);

        // Params
        $params = ['token' => "62"];
        $this->post('/sales/welcome_package', $params, [
            'HTTP_Authorization' => 'bearer ' . $token['token']['access_token'],
            'HTTP_Scope' => $scope_header
        ]);

        $response = $this->response;
        $raw_content = $this->response->content();
        $this->assertEquals(200, $this->response->status(), $raw_content);
        $content = json_decode($raw_content, true);

        $this->assertArrayHasKey('declaration', $content, $raw_content);
        $this->assertArrayHasKey('status', $content, $raw_content);
        $this->assertArrayHasKey('payload', $content, $raw_content);
        $this->assertArrayHasKey('url', $content['payload'], $raw_content);

        $this->assertEquals('success', $content['status'], $raw_content);
        $this->assertEquals('package_found', $content['declaration'], $raw_content);
    }

    /**
     * send welcome package
     * Case : successfully send welcome package
     * 
     * @return void
     * @author Amit Panchal <amit@unoindia.co>
     * @version 0.0.1
     * 
     * @covers App\Http\Controllers\CustomerController::sendWelcomePdf
     * 
     */
    public function testSendWelcomePdf() {

        $token = $this->getToken();
        // Scope Headers
        $scope_header = json_encode([
            'DealerId' => $token['user']['dealerId'],
            'CultureCode' => "en-us",
            'Environment' => config('dfx.environment'),
        ]);

        // Params
        $params = ['token' => "62"];

        $this->post('/sales/send_welcome_package', $params, [
            'HTTP_Authorization' => 'bearer ' . $token['token']['access_token'],
            'HTTP_Scope' => $scope_header
        ]);

        $response = $this->response;
        $raw_content = $this->response->content();
        $this->assertEquals(200, $this->response->status(), $raw_content);
        $content = json_decode($raw_content, true);

        $this->assertArrayHasKey('declaration', $content, $raw_content);
        $this->assertArrayHasKey('status', $content, $raw_content);
        $this->assertArrayHasKey('payload', $content, $raw_content);
        $this->assertArrayHasKey('message', $content['payload'], $raw_content);

        $this->assertEquals('success', $content['status'], $raw_content);
        $this->assertEquals('sent_welcome_pdf', $content['declaration'], $raw_content);
        $this->assertEquals('OK', $content['payload']['message'], $raw_content);
    }

    /**
     * send sms
     * Case : successfully send sms
     * 
     * @return void
     * @author Amit Panchal <amit@unoindia.co>
     * @version 0.0.1
     * 
     * @covers App\Http\Controllers\CustomerController::sendSms
     * 
     */
    public function testSendSms() {

        $token = $this->getToken();
        // Scope Headers
        $scope_header = json_encode([
            'DealerId' => $token['user']['dealerId'],
            'CultureCode' => "en-us",
            'Environment' => config('dfx.environment'),
            'DfxUserId' => $token['user']['dfxUserId']
        ]);

        // Params
        $params = [
            'customerPhone' => "9276152802",
            'customerId' => 74319569,
            'message' => "This is for testing",
            'is_enrolled' => true
        ];


        $this->post('/sales/send_sms', $params, [
            'HTTP_Authorization' => 'bearer ' . $token['token']['access_token'],
            'HTTP_Scope' => $scope_header
        ]);

        $response = $this->response;
        $raw_content = $this->response->content();
        $this->assertEquals(200, $this->response->status(), $raw_content);
        $content = json_decode($raw_content, true);

        $this->assertArrayHasKey('declaration', $content, $raw_content);
        $this->assertArrayHasKey('status', $content, $raw_content);
        $this->assertArrayHasKey('payload', $content, $raw_content);
        $this->assertArrayHasKey('status', $content['payload'], $raw_content);

        $this->assertEquals('success', $content['status'], $raw_content);
        $this->assertEquals('sent_sms', $content['declaration'], $raw_content);
        $this->assertEquals('OK', $content['payload']['status'], $raw_content);
    }

    /**
     * get user communication preferences
     * Case : successfully get user communication preferences
     * 
     * @return void
     * @author Amit Panchal <amit@unoindia.co>
     * @version 0.0.1
     * 
     * @covers App\Http\Controllers\CustomerController::communicationPreferences
     * 
     */
    public function testCommunicationPreferences() {

        $this->testCreateNewCustomer();
        $token = $this->getToken();

        // Scope Headers
        $scope_header = json_encode([
            'DealerId' => $token['user']['dealerId'],
            'CultureCode' => "en-us",
            'Environment' => config('dfx.environment'),
            'DfxUserId' => $token['user']['dfxUserId']
        ]);

        // Params
        $params = [
            'customerId' => 53089116
        ];

        $this->post('/sales/communication_preferences', $params, [
            'HTTP_Authorization' => 'bearer ' . $token['token']['access_token'],
            'HTTP_Scope' => $scope_header
        ]);

        $response = $this->response;
        $raw_content = $this->response->content();
        $this->assertEquals(200, $this->response->status(), $raw_content);
        $content = json_decode($raw_content, true);


        $this->assertArrayHasKey('declaration', $content, $raw_content);
        $this->assertArrayHasKey('status', $content, $raw_content);
        $this->assertArrayHasKey('payload', $content, $raw_content);
        $this->assertArrayHasKey('customerPreference', $content['payload'], $raw_content);

        $this->assertEquals('success', $content['status'], $raw_content);
        $this->assertEquals('communication_preferences', $content['declaration'], $raw_content);
    }

    /**
     * Get frst possible appointment date time
     * Case : successfully get frst possible appointment date time
     * 
     * @return void
     * @author Amit Panchal <amit@unoindia.co>
     * @version 0.0.1
     * 
     * @covers App\Http\Controllers\CustomerController::getFirstAppointment
     * 
     */
    public function testGetFirstAppointment() {

        $token = $this->getToken();


        // Scope Headers
        $scope_header = json_encode([
            'DealerId' => $token['user']['dealerId'],
            'CultureCode' => "en-us",
            'Environment' => config('dfx.environment'),
            'DfxUserId' => $token['user']['dfxUserId']
        ]);


        $appointmentType = 2;
        $customerToken = 6934;

        $this->get('/sales/schedule_appointment/' . $appointmentType . '/' . $customerToken, [
            'HTTP_Authorization' => 'bearer ' . $token['token']['access_token'],
            'HTTP_Scope' => $scope_header
        ]);

        $response = $this->response;
        $raw_content = $this->response->content();
        $this->assertEquals(200, $this->response->status(), $raw_content);
        $content = json_decode($raw_content, true);


        $this->assertArrayHasKey('declaration', $content, $raw_content);
        $this->assertArrayHasKey('status', $content, $raw_content);
        $this->assertArrayHasKey('payload', $content, $raw_content);
        $this->assertArrayHasKey('appointmentDateTimeStr', $content['payload'], $raw_content);

        $this->assertEquals('success', $content['status'], $raw_content);
        $this->assertEquals('get_first_appointment', $content['declaration'], $raw_content);
    }

    /**
     * Get appointment time slots based on date and appointment_type
     * Case : successfully Get appointment time slots based on date and appointment_type
     * 
     * @return void
     * @author Amit Panchal <amit@unoindia.co>
     * @version 0.0.1
     * 
     * @covers App\Http\Controllers\CustomerController::scheduleAppointmentTimeSlots
     * 
     */
    public function testScheduleAppointmentTimeSlots() {

        $token = $this->getToken();

        // Scope Headers
        $scope_header = json_encode([
            'DealerId' => $token['user']['dealerId'],
            'CultureCode' => "en-us",
            'Environment' => config('dfx.environment')
        ]);

        // Params
        $params = [
            'appointment_type' => 1,
            'appointment_date' => '2018-02-28'
        ];


        $this->post('/sales/schedule_appointment_time_slots', $params, [
            'HTTP_Authorization' => 'bearer ' . $token['token']['access_token'],
            'HTTP_Scope' => $scope_header
        ]);

        $response = $this->response;
        $raw_content = $this->response->content();
        $this->assertEquals(200, $this->response->status(), $raw_content);
        $content = json_decode($raw_content, true);


        $this->assertArrayHasKey('declaration', $content, $raw_content);
        $this->assertArrayHasKey('status', $content, $raw_content);
        $this->assertArrayHasKey('payload', $content, $raw_content);
        $this->assertArrayHasKey('timeSlots', $content['payload'], $raw_content);

        $this->assertEquals('success', $content['status'], $raw_content);
        $this->assertEquals('get_appointment_slots', $content['declaration'], $raw_content);
    }

    /**
     * Get advisor list
     * Case : successfully get advisor list
     * 
     * @return void
     * @author Amit Panchal <amit@unoindia.co>
     * @version 0.0.1
     * 
     * @covers App\Http\Controllers\CustomerController::getAdvisors
     * 
     */
    public function testGetAdvisors() {

        $token = $this->getToken();
        // Scope Headers
        $scope_header = json_encode([
            'DealerId' => $token['user']['dealerId'],
            'CultureCode' => "en-us",
            'Environment' => config('dfx.environment')
        ]);


        $appointmentType = 1;

        $this->get('/sales/advisors/' . $appointmentType, [
            'HTTP_Authorization' => 'bearer ' . $token['token']['access_token'],
            'HTTP_Scope' => $scope_header
        ]);

        $response = $this->response;
        $raw_content = $this->response->content();
        $this->assertEquals(200, $this->response->status(), $raw_content);
        $content = json_decode($raw_content, true);


        $this->assertArrayHasKey('declaration', $content, $raw_content);
        $this->assertArrayHasKey('status', $content, $raw_content);
        $this->assertArrayHasKey('payload', $content, $raw_content);
        $this->assertArrayHasKey('advisors', $content['payload'], $raw_content);

        $this->assertEquals('success', $content['status'], $raw_content);
        $this->assertEquals('get_advisors', $content['declaration'], $raw_content);
    }

    /**
     * Get transportation option
     * Case : successfully get transportation option
     * 
     * @return void
     * @author Amit Panchal <amit@unoindia.co>
     * @version 0.0.1
     * 
     * @covers App\Http\Controllers\CustomerController::scheduleTransportationOption
     * 
     */
    public function testScheduleTransportationOption() {

        $token = $this->getToken();
        // Scope Headers
        $scope_header = json_encode([
            'DealerId' => $token['user']['dealerId'],
            'CultureCode' => "en-us",
            'Environment' => config('dfx.environment')
        ]);

        // Params
        $params = [
            'transportation_date' => '2018-01-25'
        ];

        $this->post('/sales/schedule_transportation_options', $params, [
            'HTTP_Authorization' => 'bearer ' . $token['token']['access_token'],
            'HTTP_Scope' => $scope_header
        ]);

        $response = $this->response;
        $raw_content = $this->response->content();
        $this->assertEquals(200, $this->response->status(), $raw_content);
        $content = json_decode($raw_content, true);


        $this->assertArrayHasKey('declaration', $content, $raw_content);
        $this->assertArrayHasKey('status', $content, $raw_content);
        $this->assertArrayHasKey('payload', $content, $raw_content);
        $this->assertArrayHasKey('transportationOptions', $content['payload'], $raw_content);

        $this->assertEquals('success', $content['status'], $raw_content);
        $this->assertEquals('get_transportation', $content['declaration'], $raw_content);
    }

    /**
     * Get all customer based on search criteria
     * Case : successfully get all customer based on search criteria
     * 
     * @return void
     * @author Amit Panchal <amit@unoindia.co>
     * @version 0.0.1
     * 
     * @covers App\Http\Controllers\CustomerController::searchCustomer
     * 
     */
    public function testSearchCustomer() {
        $token = $this->getToken();
        // Scope Headers
        $scope_header = json_encode([
            'DealerId' => $token['user']['dealerId'],
            'CultureCode' => "en-us",
            'Environment' => config('dfx.environment')
        ]);

        // Params
        $params = [
            'searchtext' => 'test'
        ];

        $this->post('/sales/search_customer', $params, [
            'HTTP_Authorization' => 'bearer ' . $token['token']['access_token'],
            'HTTP_Scope' => $scope_header
        ]);

        $response = $this->response;
        $raw_content = $this->response->content();
        $this->assertEquals(200, $this->response->status(), $raw_content);
        $content = json_decode($raw_content, true);

        $this->assertArrayHasKey('declaration', $content, $raw_content);
        $this->assertArrayHasKey('status', $content, $raw_content);
        $this->assertArrayHasKey('payload', $content, $raw_content);

        $this->assertEquals('success', $content['status'], $raw_content);
        $this->assertEquals('customer_found', $content['declaration'], $raw_content);
    }

    /**
     * To book appointment
     * Case : successfully book appointment
     * 
     * @return void
     * @author Amit Panchal <amit@unoindia.co>
     * @version 0.0.1
     * 
     * @covers App\Http\Controllers\CustomerController::bookAppointment
     * 
     */
    public function testBookAppointment() {

        $token = $this->getToken();

        // Scope Headers
        $scope_header = json_encode([
            'DealerId' => $token['user']['dealerId'],
            'CultureCode' => "en-us",
            'Environment' => config('dfx.environment')
        ]);

        // Params
        $params = [
            "customerId" => 74319142,
            "appointment_type" => 1,
            "token" => 4248,
            "appointmentDateTimeStr" => "2018-03-03T08:15:00-05:00",
            "mileage" => 100000,
            "transportationOption" => " ",
            "advisor_id" => ""
        ];

        $this->post('/sales/book_appointment', $params, [
            'HTTP_Authorization' => 'bearer ' . $token['token']['access_token'],
            'HTTP_Scope' => $scope_header
        ]);

        $response = $this->response;
        $raw_content = $this->response->content();
        $this->assertEquals(200, $this->response->status(), $raw_content);
        $content = json_decode($raw_content, true);

        $this->assertArrayHasKey('declaration', $content, $raw_content);
        $this->assertArrayHasKey('status', $content, $raw_content);
        $this->assertArrayHasKey('payload', $content, $raw_content);
        $this->assertArrayHasKey('appointment', $content['payload'], $raw_content);


        $this->assertEquals('success', $content['status'], $raw_content);
        $this->assertEquals('appointment_booked', $content['declaration'], $raw_content);
    }

    /**
     * To send mail for forgot password
     * Case : successfully send mail for forgot password
     * 
     * @return void
     * @author Amit Panchal <amit@unoindia.co>
     * @version 0.0.1
     * 
     * @covers App\Http\Controllers\CustomerController::forgotPassword
     * 
     */
    public function testForgotPassword() {

        $token = $this->getToken();

        // Scope Headers
        $scope_header = json_encode([
            'DealerId' => $token['user']['dealerId'],
            'CultureCode' => "en-us",
            'Environment' => config('dfx.environment')
        ]);

        // Params
        $params = ["username" => "test_user"];

        $this->post('/sales/forgot_password', $params, [
            'HTTP_Authorization' => 'bearer ' . $token['token']['access_token'],
            'HTTP_Scope' => $scope_header
        ]);

        $response = $this->response;
        $raw_content = $this->response->content();
        $this->assertEquals(200, $this->response->status(), $raw_content);
        $content = json_decode($raw_content, true);

        $this->assertArrayHasKey('declaration', $content, $raw_content);
        $this->assertArrayHasKey('status', $content, $raw_content);
        $this->assertArrayHasKey('payload', $content, $raw_content);
        $this->assertArrayHasKey('message', $content['payload'], $raw_content);


        $this->assertEquals('success', $content['status'], $raw_content);
        $this->assertEquals('sent_email', $content['declaration'], $raw_content);
        $this->assertEquals('Email sent', $content['payload']['message'], $raw_content);
    }

    /**
     * To get question
     * Case : successfully get question
     * 
     * @return void
     * @author Amit Panchal <amit@unoindia.co>
     * @version 0.0.1
     * 
     * @covers App\Http\Controllers\CustomerController::getQuestion
     * 
     */
    public function testGetQuestion() {
        $token = $this->getToken();
        // Params
        $params = [
            'manufacturerId' => '41',
            "dealer_id" => 4363
        ];

        $this->post('/sales/get_question', $params, ['HTTP_Authorization' => 'bearer ' . $token['token']['access_token']]);
        $response = $this->response;
        $raw_content = $this->response->content();
        $this->assertEquals(200, $this->response->status(), $raw_content);
        $content = json_decode($raw_content, true);

        $this->assertArrayHasKey('declaration', $content, $raw_content);
        $this->assertArrayHasKey('status', $content, $raw_content);
        $this->assertArrayHasKey('payload', $content, $raw_content);

        $this->assertEquals('success', $content['status'], $raw_content);
        $this->assertEquals('questions_found', $content['declaration'], $raw_content);
    }

    /**
     * To save question answare
     * Case : successfully get question
     * 
     * @return void
     * @author Amit Panchal <amit@unoindia.co>
     * @version 0.0.1
     * 
     * @covers App\Http\Controllers\CustomerController::saveQuestionResponse
     * 
     */
    public function testSaveQuestionResponse() {
        $token = $this->getToken();
        // Params
        $params = [
            "customerId" => 74319569,
            "manufacturerId" => 41,
            "dealer_title" => "Magic Toyota",
            "dealer_logo" => "https://assets.dealer-fx.com/OEM/toyota.png",
            "customer_vehicle_info" => [
                "first_name" => "Low",
                "last_name" => "Adam",
                "email" => "alow@gmail.com",
                "phone_number" => "1234567890",
                "vin" => "JN8AS5MT6DW013973",
                "model" => "Rogue",
                "color" => "RAQ",
                "stock_number" => "123456",
                "delivery_date" => "1/27/2017",
                "sales_person" => "Hass Hijjati",
                "notes" => "testing"
            ],
            "questions_info" => [
                "tag_head" => "Your First Service",
                "tag_line" => "To be completed by the New Owner. Please check off items as they are completed",
                "question" => [
                    [
                        "question_id" => 1,
                        "text" => "Satisfied with the condition of Your New Toyoya at the time of delivery?",
                        "option" => [],
                        "reply" => false
                    ],
                    [
                        "question_id" => 2,
                        "text" => "Vehicle delivered with a full tank of gas or a gas voucher??",
                        "option" => [],
                        "reply" => true
                    ]
                ]
            ],
            "signature" => [
                "owner" => "iVBORw0KGgoAAAANSUhEUgAAAjoAAABkCAYAAACPU5puAAAABHNCSVQICAgIfAhkiAAAG69JREFU\neJzt3XlYVNf5B/B3FvYII6JRUBk3XCJIxK0KdKxxwYW6oGhpJULdTdXWxDRubUyjJj5gbKnBDdPY\niEtjWoS4NNEKNUatLFrRapyxRYOJMtcaRVC4vz/8aZ25584+9w7D9/M8PE/mvfee+zIY5uWcc89R\n8DzPEwAAAIAXUsqdAAAAAIC7oNABAAAAr4VCBwAAALwWCh0AAADwWih0AAAAwGuh0AEAAACvhUIH\nAAAAvBYKHQAAAPBaKHQAAADAa6HQAQAAAK+FQgcAAAC8FgodAAAA8FoodAAAAMBrodABAAAAr4VC\nBwAAALwWCh0AAADwWih0AAAAwGuh0AEAAACvhUIHAAAAvBYKHQAAAPBaKHRkwHEcrV27loYPH05d\nunShiIgIGjp0KL333ntypwYAAOBVFDzP83In0ZwMHz6c/vrXv4oeV6vVNGrUKFq6dCnFx8dLmBkA\nAID3QaEjEY7jqEePHnTz5k2br9FoNLR//37S6XTuSwwAAMCLodCRSI8ePejSpUsOXTt8+HA6fPiw\nizOCpmjHjh1UXl5OGo2GtFotpaeny50SAIBHU8udQHOwYcMGh4scIqIjR45Q37596ezZsy7MCpoS\njuOoW7dudOvWLZN4aWkpbdiwQaasAAA8H3p0JBAWFka3b992up2cnByaN2+eCzKCpiY5OZkKCgqY\nx/C/MACAODx1JQGj0ciMT58+nYxGI+l0OlKpVFbbee2111ydGjQRYkUOEdHkyZMlzAQAoGlBj46b\nVVRUUJ8+fQRxtVpNDx8+NIkVFRVRVlYWffbZZ6LtNddenQULFtC+ffvo9u3b1NDQQESPezKUSiX5\n+vpSSEgIDRkyhBYtWkQJCQkyZ+t6CoXC4nGDwUCRkZESZeOYiooK2rNnD926dYtqa2upXbt2lJiY\nSKNHj5Y7NQDwYih03Cw/P5+mTZsmiIeFhdG3337LvKakpIQSExOZQxIDBw6kkydPujxPT8NxHOXk\n5FBeXh5dvXrVruGZ4OBgGjx4MKWkpNCkSZNIo9G4MVNpxMbGUnl5uejxdu3a0Y0bNyTMyHYZGRn0\nhz/84WmBak6lUlFMTAwtXbqUUlNTJc4OALweD26VmprKE5Hg64UXXrB43c6dO5nXtWzZUqLM5VFa\nWspHRkYyv3dHvwICAvjMzExer9fL/e057OjRo7xKpbL4fRYXF8udpoklS5ZYzdn8S61W81u2bJE7\ndQDwIujRcbN+/frRP/7xD0F81KhR9Omnn1q8NjU1lfbs2WMSU6lU9OjRI5fm6Clef/11euedd9w6\nuTYiIoJ69OhB7du3p6SkJBo4cCBptVq33c+Vzp07RzExMaLHe/fuTefOnZMwI7bdu3dTRkYG3b9/\n3+E2goODad68ebRmzRoXZgYAzREKHTebO3cuvf/++4L4a6+9RuvWrbN4rcFgoK5duwq6/I1Go1cM\nxzxr9OjRVgs/d8nLy6OXX35ZlnvbS6VSUWNjI/OYQqGgmpoa2f5tcBxHffv2Jb1e77I2W7RoQSUl\nJRYLPAAAS/DUlZuNGTOGGW/fvr3Va7VaLf3gBz8QxA8dOuR0Xp7Elt6tJ1q1akXTp0+njRs30q5d\nu2jdunXUp08fCgwMdPj+mZmZpFAoSKFQUKdOneiTTz5xuC13a9WqlegxnudpxIgREmbzP09W/nZl\nkUNEdPfuXerbty/l5+e7tF0AaEbkHDdrLhQKhWAugsFgsOnakpISwbW5ubluzlg6+fn5Fuds+Pr6\n8kOGDOGPHj1qU3vZ2dn8xIkTeR8fH4fn9Pj6+vJGo9G937iDoqOjreZfUFAgeV5Lliyx+f3t2rUr\nP2fOHL5Lly52/VzKy8sl/74AoOlDj44EWJtz8jaOGEZERAhi3jJHh+M4mj17NvOYr68v5efnU11d\nHZWUlNi839eiRYvoT3/6E9XX11N5eTn17dvX7rzq6+tp7dq1dl8nhcGDB1s958c//rEEmZj6/e9/\nb/F4YGAgzZw5k3iep8uXL9OmTZvoypUrxPM8LVu2jMLDw63eQ6x3FADAEhQ6EmANJ1jawfxZ//nP\nfwSxmpoap3PyBL/5zW/ozp07gnhISAjdvHnT6UeNN27cSGVlZQ5d+9577zl1b3cZNmyY1XPu3LlD\nu3fvliCbxziOE5147O/vTzk5OXTv3j3avHkz85y33nqLrl+/TkajkYYOHSp6n6qqKuI4ziU5A0Dz\ngUJHArGxsYLYkSNHbLp25cqVgtiFCxeczkluHMcx92gKDAwkjuOcmlC7d+9e8vf3p23btolO3LXm\nwYMHtHXrVodzcBfzVZCVSvb/wnPmzJEiHSJ6vO4Ty4svvki1tbU2L3Cp0Wjo888/p8LCQtFzzJ9C\nBACwBoWOBFgTj8+cOWPTtayipra21umc5DZu3DjBEJxKpaK///3vTrU7cuRImjJlCtXV1TnVDhHR\n/PnznW7DHXx9fa2ew3GcZL0fYr1mjm5CO3r0aJo5cybz2AcffOBQmwDQfKHQkQBrnRZbV7FlbQb6\n/e9/39mUZFVRUcHsBVi5ciWz98sW165do9atW9Phw4dtvqZ79+4W5/DU19dL2jNiq7CwsKf/3djY\nKDrEJ9VWIf7+/oJYUFCQU22OHz+eGTcYDE61CwDNDwodCWg0GvLx8TGJPXjwwKa/uFnL5ot9CDQV\nEyZMEMTi4uKYw3S2qKiooJ49e9KtW7dsOl+hUNDq1avp4sWLJkUDi9i8Ejm1bdvW5PX7779PwcHB\ngvMKCgok6dVh3btjx45OtSm2/xVrThcAgCUodCSSmJgoiC1cuNDiNceOHRPEfHx8msxKviwVFRV0\n9epVk5harbZ5cra54uJiiouLs3k4r3///lRTU0PLly8nIrL6NBfP8zR37lyHcnMX1ryja9eukUql\nMol99913kjw9xip0evTo4ZZ7BQQEuKVdAPBeKHQkkpWVJYj98Y9/tHhNaGioIObsX8pyYw0F/fnP\nf3Zo8nF+fj4NHTrUpsftX3jhBTIYDHTq1CmTe/3oRz+yeu327dvtzs2dqqurTV6XlJSQRqOhM2fO\nCIqddevWUUVFhVvz6dWrlyBmy4KYjvD0HdoBwPOg0JFITEwMjR071iTW0NBAL730kug1VVVVglhm\nZqbLc5OKwWCgL774wiTWrVs30WEKS9566y2aNm2a6I7YTwwYMIAMBgOdP3+e+SEZEhJi9V719fV0\n4MABu3N0l7t375q8fjKcExsbS/v27ROc7+51dWJiYkihUJjE1Gq1W+7lyL8VAGjeUOhI6MMPP6Tn\nnnvOJPbZZ5+JLm9/8uRJQczThlHskZKSIojZM3n4ib1799KKFSssntOnTx8yGo305ZdfWuwF0Gg0\nNj3F5EkLCJo/Ufbsk3njx48X7At17tw5KioqcmtO5gtgumIbDfPiiYho+PDhTrcLAM0LCh0JaTQa\nevPNNwXxtLQ05iO65oWOWq1uspt5lpWVCXZxnzx5skPzjX7yk5+IHmvTpg0VFhZSWVmZze9VmzZt\nrJ5z4sQJ5pwpOZj3Yl2/ft3k9Ycffii4RmwFaldg9XaxFrq0F2v18A4dOjjdLgA0Lyh0JLZ48WLq\n3r27SayxsfHpEMuzKisrTV435UnI5sN2RETvvvuu3e1MnTpVdI2ckSNH0qVLl+we3khOTrZ6Ds/z\nsmytwGJeANy7d8/kdUxMDA0aNMgkVlVVRXv37nVLPuZzhojct01JU/5/AADkgUJHBgcPHhSsaPvw\n4UPq0aOHycTR//73vybnOLslglxKSkoEvQ79+vWze2KpwWAQ/bCOjY2lgwcPOtTj9eqrrwpio0aN\nEsSuX79O48aNs7t9V7p27ZogxioqWMOh6enpbnnc/N///jczbuuO9CxSbmEBAN4NhY4MtFotrV+/\nXhCvq6ujgQMHUllZGXEcJyh0lixZIlWKLpWeni6IZWdn293OSy+9xHy0OigoiEpLSx3KjYjdSxAe\nHk5JSUmC+IEDB9w+38US1pBQp06dBLHIyEjBvlG1tbWUlpbm8pzEHu3//PPPHW7TfMI1AICjUOjI\nZPHixTRr1ixB/MGDBzRo0CD66KOPTOJBQUFNcn4Ox3Gk1+tNYsHBwcwd3S1JS0ujr776ShBXKBR0\n4sQJp3JkKS0tpY8++og5UTklJUW2zSXN5zkREUVHRzPP/fjjjwX5FxUV0apVq1yak9iSB2J7YNni\n22+/FcRsmTQOAGAOhY6McnNzmY+L19XVCfZZYv3V3hSsXLlSMKfE2kKJ5jIzMwWF3xPvvPOO4Ckj\ne7G2Ffjqq69Io9Ewh7Vqa2tle/rn5s2bglhcXBzzXI1GQ/v37xcMk7755pvM78tRYpPDWcNstmIN\nh4ltYAoAYBEPstuwYQNPRBa/pkyZIneaDgkNDTX5Pnx8fOy6fsuWLaLvSXp6uktyZN3D19f36fGo\nqCjm/d9++22X3N8emZmZgjyMRqPFa44ePcr7+voKruvWrRuv1+tdkpdCobD4HtprwIABLm0PAJov\n/InkARYuXEi7du0S7If1rJEjR0qYkWsUFxdTTU2NSWz69Ol2tfGLX/yCGe/Tpw/t2LHD0dRMXL58\nWRB7doLvoUOHmFsPrFixwiXrxdjj66+/FsSsDWnqdDo6dOiQYI+sy5cvU+fOnalTp06Ul5fnVF4t\nWrQQxOrr6x2ez2Q0GgUxa4tDAgCwoNDxEFOnTqXDhw8zPzCIiKKioiTOyHnmRY1CoWBOwhazefNm\nwYRsIqLAwEBJ17TRarVUWVkp2JG7oaGBUlJSJN3403xIx9Z5Kzqdjr744gvq2bOnSZzneTIYDJSR\nkUFKpZJ69epFGzZssDsvsXlCtmyxwcIqajB0BQCOwG8OD6LT6egvf/kLs9hJTk6WbQKsI/Ly8gRz\nX/r162fXhOpf/vKXgpiPjw9duHDBpROzWU/4mH+oRkZG0vnz56l169Ym8YaGBpo9ezZlZGS4LB9L\nzOfoBAYG2nytVquloqIi5gazRI+LnsrKSlq8eDEFBQXR5MmTbf43JzYh+c6dO3Tw4EGbc3zC/H0m\nQqEDAI7Bbw4Po9PpmGu4GI1G6tKlS5MpdubNmyeITZgwwebrN2/eLBj2UiqV9K9//cvlGzv6+/sL\nYqxhRK1WS6dOnaJ27doJjuXl5VHnzp3d/vMxfxqJ1eNliVarpb/97W+0bds2i0XS/fv3ad++fdSy\nZUsaMGCA1Y1BLS26aD6x3hbdunUTxFhLCwAAWINCxwOZr4j8RE1NDfXq1cvji52ioiJ68OCBIG7P\nPl2rV68WxNavX++WlXFZ76fYppRarZZOnDjB7HXT6/XUunVrKi4udnmOROw8Hf3wz8jIoHv37lFx\ncTElJCRYHAI7ffo09enTh55//nnaunUr8xxWcf7E1atX7f43y9oR/eHDh3a1AQBAhELH43AcZ7JJ\no7mvv/7a4oeKJ3jllVeYcXuGm8x3blcqlbR48WKn8hLDmg8its0E0eNip6Kiglq2bCk49ujRI0pM\nTKRVq1a5vCDdtGmTS9sjIoqPj6fjx49TXV0dFRYW0sCBA0XP/eabb2jmzJkUEBBAycnJJgWdRqOx\nuGO5vRPHxeb8eHqRDwCeB4WOh1m7dq3JX+nmu50TEX355Zc27c8kl6tXrwpioaGhNl/P2iTS0hNp\nzmLN0amvr7d4jVarpbNnzzKHsYger1WTmprq0g9m84UXXW306NF08uRJ0uv1ovN4iB4vallQUECJ\niYmkVCopKCiIWrVqZXF/q9/97nd25SI25ycrK8uudgAAUOh4mG3btpm8XrVqFXPfooKCAuZig3J7\n/fXXmfEZM2bY3AZr8qpKpXI4J2vMn6aylVarpQsXLlBKSgrz+OHDhyk6OtqphfOe5Y4VoFmezOMp\nLy8XbEBrjud5un//vmA+lTl734OYmBhSKBSC+KlTp+xqBwAAhY4HKSoqolu3bj19rVar6ac//Sml\npqZScXGxYPLo9u3bPW6jT7E5HMuXL7e5DdZu2Kw5P67CWiPHVhqNhvbu3Us7d+4kPz8/wfGqqiqK\nioqisrIyZ1IkInaxYGm4yFkxMTF08eJFKi8vp6SkJOakbVs5spt5eHi4IHbixAkMXwGAXVDoeJAF\nCxaYvB42bNjTeS3x8fH0z3/+kwYNGmRyzp49e6h///4e8ct/9+7ddPv2bUE8NjbWrvk5V65cEcTc\nOXQVEhLidBtpaWl08OBB6tChg+BYfX09xcXFMYfk7PHdd98JYs8//7xTbdoiJiaGioqKqLa2lvbv\n3++WCeEsu3btEsTu3r1La9euleT+AOAlZF6ZGf5fUVGRYMl71vL8RqORHzt2rODczp07W90KwN1a\ntGjB3CqhtLTUrnZefPFFQRtqtdpNWfN8UlISM29H6PV6vn379sz2lEoln5OT41C7GzduZLaZmZnp\nUHvOMhgMfGpqKh8SEmJ1+xIi4n/4wx86dJ+UlBRBWyqVymVbVwCA90Oh4yFat25t8ss8Ojra4vnj\nx48XfAAEBwfzxcXFEmVsasaMGcwPuIiICLvbGjp0qMsKD1sMGzZMcC+FQuFwe0ajkY+MjBT90E9J\nSbGrveLiYtG2POEDX6/X89nZ2fz48eP5zp078y1atHi6t1ZwcDCfnp7ucBFuNBqZ+3SFhYXJXtgD\nQNOAQscDZGVlCX6Rl5eXW70uOjqa+eGXlpYmQdb/YzQaeaVSycwlNzfX7vays7OZbW3ZssUN2fN8\nq1atBPcKDQ11qk2j0cjsjXi2hyorK8tqOwaDgVer1aJtNAezZ89mfv8jRoyQLae8vDxep9PxOp2O\n/9WvfiVbHgBgHQodD2D+QTZ06FCbr+3YsSPzQ8DPz4/fvHmzG7P+H7GhH0c/iMUKp/bt27s488f3\nYu28bc/PwJKCggKLwztqtZqfOHEibzAYmNeLDQcSET98+HCX5NgU+Pn5Md8DR4cCnVFaWirIIzs7\nW/I8AMA2KHRklpycLChQ7BUbGyv6YRgQEMCvWLHCDZk/tnPnTtF7r1692uF2J02axGzT1UNzs2bN\nYt7H1R9cv/71r/nAwEDR94qI+KioKJNenu9973sWzz969KhLc/RkhYWFzPdAqVTy+/fvlzQXVg/T\ngAEDJM0BAGyHQkdGer1e8AuzsLDQobZeeeUVix+KSqWS7969O799+3aX5W8wGJi9IUTEh4eHO9V2\neXk5s92QkBAXZS9+D3cNCZWWlvI9e/a0+HOy9SspKcktOXqy3Nxc5nuhUqn4goICyfKIi4sT5NCv\nXz/J7g8A9kGhIyPzp4sGDx7sVHvHjx/n27Zta/VD0tfXl9fpdHx+fr5T9xN7uojItjlG1rDmzhAR\nP3XqVKfb5nnxYSFneqJs8fbbb4vOu7Hla9CgQW7Nz5Nt2bJF9H1x9I8EexiNRmbPXGpqqtvvDQCO\nQaEjk127dgmKD1cwGo38/Pnzbf7QVKvVfHR0NL927Vqrbev1ev7YsWM8z/O8TqcTbdPaE2O2ys/P\nF73Hz372M6faTkxMZLbryNChI4xGIz9jxgze39/friLHx8dHkvw8WUFBAfO9USgU/PLly9167/Dw\ncOa9xeZYAYD8UOjIQK/X86GhoSa/KF09qbK8vJzv3r273b0FLVq04ENDQ/mgoCDRJ6msfbnyL+uo\nqCjR+3To0MGhR4xZj5PL+Zd5Wlqa6BCg+ZctT2o1B5bmhnXq1InfsWOHS+93/PhxXqPRMO+HYSsA\nz4ZCRwbma+DExMS4bU0Qo9HIT5s2zeaF3Zz9cseTUb179xa9n0Kh4OPj423+izo+Pl60LaVSKdva\nLEajkU9ISLD43k6cOFGW3DyVtXlpfn5+/JgxY5z6mer1etF1nZ58ybV2FQDYRsHzPE8gmS1bttCs\nWbNMYrt27aKpU6e6/d4VFRW0bNkyKioqMtkh3VWee+45OnfunFu2CHj55Zfpgw8+sHhOWFgYJSUl\nUUBAANXU1FB1dTU9evSI6urq6OHDh1RZWUkNDQ2i1+fm5gp+NlLjOI6ys7Pp448/purqampsbKS4\nuDh64403SKfTyZqbJ5owYQJ98sknVs9TqVTUunVrSkhIoPj4eOrSpQtFRESQUvl4F5zKykq6cuUK\nffPNN3T27Fm6ePEi3b17l+rq6iy227VrV7p8+bJLvhcAcA8UOhLiOI7atGlDDx8+fBrr168fnT59\nWvJc5s2bR3v37jXZRNQZQUFBVFJSQrGxsS5pj2XNmjX0xhtvuKXtd999l5YsWeKWtsG9Xn31VVq/\nfr3k9/Xz86Pq6mq79nEDAOmh0JFQTEwMnTt37ulrhUJBV69elWyTRDElJSW0Z88eOnPmDBkMBqqu\nriZ7/ln07t2biouLJfmFv3XrVpo7d65Du2GLWb16tV27q4PnKSsro7Fjx9L169cluV+3bt3o1KlT\nKHIAmgAUOhJh9UbMmTOHNm3aJFNGlnEcRwcPHqRjx46RwWCgmpoaamxspIaGBrpx4wZ16tSJRowY\nQT//+c8l/2XPcRxNmTKFjhw54lQ7SqWS8vPzafLkyS7KDOS2detWWrhwId2/f98t7Wu1WlqzZo0k\nQ80A4BoodCTAcRyFh4dTbW3t01i7du3oxo0bMmblHSZNmkSffvqpyXtrjVqtpuTkZNq2bRv+IvdS\nxcXFtH79ejp9+jTdu3ePiB4PNdXV1dGjR4+otraW2WupVCrJz8+P1Go1KRQK8vPzo44dO1J6ejqN\nGzdO9t5XALAfCh0JLFiwgHJyckxiBoOBIiMjZcrIu3AcRzt27KDf/va3VFVVRfX19czzwsLCaNmy\nZbRo0SKJMwRPxHEcnT9/noKDgyk4OBhFDICXQqHjZhzHUcuWLU1is2bNotzcXJkyaj44jnv63+i5\nAQBonlDouFn//v3pzJkzT19HRERQVVWVjBkBAAA0H0q5E/Bma9asMSlyiIgOHDggUzYAAADND3p0\n3KSoqIjGjBljEktISKDjx4/LlBEAAEDzg0LHDTiOo7Zt2wpWVT1+/DglJCTIlBUAAEDzg0LHDXr1\n6kWVlZUmscDAwKePuQIAAIA0MEfHxSoqKgRFDhHRqFGjZMgGAACgeUOPjou1bduWbt68KYgbjUY8\n4gwAACAx9Oi4UEVFBbPI8ff3R5EDAAAgAxQ6LjRv3jxmfP78+RJnAgAAAEQYunIZjuOoVatW1NjY\nKDiGtxgAAEAe6NFxkfT0dGaRM2zYMBmyAQAAACL06LjE1q1baebMmcxjmIQMAAAgHxQ6TuI4jkJD\nQ5nDU0OGDKGSkhIZsgIAAAAiDF05LT4+XnQOzoIFCyTOBgAAAJ6FHh0ncBxHLVu2FD2OYSsAAAB5\noUfHCStXrhQ9plarUeQAAADIDD06TggMDKTa2lrmsaioKLp06ZLEGQEAAMCz0KPjoLKyMtEih4ho\n6dKlEmYDAAAALCh0HHTs2DHRY0qlkjIyMqRLBgAAAJhQ6Diourpa9FhkZKSEmQAAAIAYFDoOGjRo\nkOix5ORkCTMBAAAAMf8HMKp6irF4IOkAAAAASUVORK5CYII=\n",
                "representative" => "iVBORw0KGgoAAAANSUhEUgAAAAwAAAAUCAYAAAC58NwRAAAABGdBTUEAALGPC/xhBQAAACBjSFJNAAB6JgAAgIQAAPoAAACA6AAAdTAAAOpgAAA6mAAAF3CculE8AAAACXBIWXMAAAsTAAALEwEAmpwYAAABWWlUWHRYTUw6Y29tLmFkb2JlLnhtcAAAAAAAPHg6eG1wbWV0YSB4bWxuczp4PSJhZG9iZTpuczptZXRhLyIgeDp4bXB0az0iWE1QIENvcmUgNS40LjAiPgogICA8cmRmOlJERiB4bWxuczpyZGY9Imh0dHA6Ly93d3cudzMub3JnLzE5OTkvMDIvMjItcmRmLXN5bnRheC1ucyMiPgogICAgICA8cmRmOkRlc2NyaXB0aW9uIHJkZjphYm91dD0iIgogICAgICAgICAgICB4bWxuczp0aWZmPSJodHRwOi8vbnMuYWRvYmUuY29tL3RpZmYvMS4wLyI+CiAgICAgICAgIDx0aWZmOk9yaWVudGF0aW9uPjE8L3RpZmY6T3JpZW50YXRpb24+CiAgICAgIDwvcmRmOkRlc2NyaXB0aW9uPgogICA8L3JkZjpSREY+CjwveDp4bXBtZXRhPgpMwidZAAABmElEQVQoFX2TzU7CQBDHd1pvHGjLA1g+fAhPRkEPvowXFQnRxPgBxIMxvo8xKjwFBPoAQjlgUGPX/yy7a6mESdrd6f5/MzuzW1fAymHxoeAF2+N48sw+zMEj1SzzcitheO+Qc4T1ncDz5uM47mrxSsiRkmZSSsGPENRGgLoOmmBkaMncyTR+QuQvIqoyhLEG/xOZ3qDkKEuZXMZ5MfCCOZGoWSjvf4+n8WsWYkBFQMHdgl/4gL+voWqwAmLApgXU81C4g20ZyM/7P9j2i9GpLRmHxwm6BNEMtRwwBHgPfmIgA0BrMwks9jLQroHSgIEIEwcQZ3rH/FA1nAhQPvevz0ytsyxg/KS8WTxGmx8ZRj04WHExiKLT9JZYzKcrtPhOF63FwyteMxGtuISrgYBKzJETKc4H0ULMes5gxVvF8gkq7ugz4L41Ib7hyDCl444oq4QlXDrZTov70fBWL9ugqgbc0AYWWlYsZKMfjVpZMfsbKPAaYzMlPuuPRp1VYgUQyRwRZ2STa8ULDd78i6KGS/vhr3upT4vpL4VSzwtillRSAAAAAElFTkSuQmCC",
                "date" => "1/27/2017"
            ]
        ];


        $this->post('/sales/question_response', $params, ['HTTP_Authorization' => 'bearer ' . $token['token']['access_token']]);
        $response = $this->response;
        $raw_content = $this->response->content();
        $this->assertEquals(200, $this->response->status(), $raw_content);
        $content = json_decode($raw_content, true);

        $this->assertArrayHasKey('declaration', $content, $raw_content);
        $this->assertArrayHasKey('status', $content, $raw_content);
        $this->assertArrayHasKey('payload', $content, $raw_content);

        $this->assertEquals('success', $content['status'], $raw_content);
        $this->assertEquals('questions_response_updated', $content['declaration'], $raw_content);
    }

    /**
     * To Preview question response
     * Case : successfully previewed question response
     * 
     * @return void
     * @author Amit Panchal <amit@unoindia.co>
     * @version 0.0.1
     * 
     * @covers App\Http\Controllers\CustomerController::previewQuestionResponse
     * 
     */
    public function testPreviewQuestionResponse() {
        $token = $this->getToken();
        // Params
        $params = [
            "customerId" => 74319199,
            "manufacturerId" => 41,
            "dealer_title" => "Magic Toyota",
            "dealer_logo" => "https://assets.dealer-fx.com/OEM/toyota.png",
            "customer_vehicle_info" => [
                "first_name" => "Low",
                "last_name" => "Adam",
                "email" => "alow@gmail.com",
                "phone_number" => "1234567890",
                "vin" => "JN8AS5MT6DW013973",
                "model" => "Rogue",
                "color" => "RAQ",
                "stock_number" => "123456",
                "delivery_date" => "1/27/2017",
                "sales_person" => "Hass Hijjati",
                "notes" => "testing"
            ],
            "questions_info" => [
                "tag_head" => "Your First Service",
                "tag_line" => "To be completed by the New Owner. Please check off items as they are completed",
                "question" => [
                    [
                        "question_id" => 1,
                        "text" => "Satisfied with the condition of Your New Toyoya at the time of delivery?",
                        "option" => [],
                        "reply" => false
                    ],
                    [
                        "question_id" => 2,
                        "text" => "Vehicle delivered with a full tank of gas or a gas voucher??",
                        "option" => [],
                        "reply" => true
                    ]
                ]
            ],
            "signature" => [
                "owner" => "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\n",
                "representative" => "iVBORw0KGgoAAAANSUhEUgAAAAwAAAAUCAYAAAC58NwRAAAABGdBTUEAALGPC/xhBQAAACBjSFJNAAB6JgAAgIQAAPoAAACA6AAAdTAAAOpgAAA6mAAAF3CculE8AAAACXBIWXMAAAsTAAALEwEAmpwYAAABWWlUWHRYTUw6Y29tLmFkb2JlLnhtcAAAAAAAPHg6eG1wbWV0YSB4bWxuczp4PSJhZG9iZTpuczptZXRhLyIgeDp4bXB0az0iWE1QIENvcmUgNS40LjAiPgogICA8cmRmOlJERiB4bWxuczpyZGY9Imh0dHA6Ly93d3cudzMub3JnLzE5OTkvMDIvMjItcmRmLXN5bnRheC1ucyMiPgogICAgICA8cmRmOkRlc2NyaXB0aW9uIHJkZjphYm91dD0iIgogICAgICAgICAgICB4bWxuczp0aWZmPSJodHRwOi8vbnMuYWRvYmUuY29tL3RpZmYvMS4wLyI+CiAgICAgICAgIDx0aWZmOk9yaWVudGF0aW9uPjE8L3RpZmY6T3JpZW50YXRpb24+CiAgICAgIDwvcmRmOkRlc2NyaXB0aW9uPgogICA8L3JkZjpSREY+CjwveDp4bXBtZXRhPgpMwidZAAABmElEQVQoFX2TzU7CQBDHd1pvHGjLA1g+fAhPRkEPvowXFQnRxPgBxIMxvo8xKjwFBPoAQjlgUGPX/yy7a6mESdrd6f5/MzuzW1fAymHxoeAF2+N48sw+zMEj1SzzcitheO+Qc4T1ncDz5uM47mrxSsiRkmZSSsGPENRGgLoOmmBkaMncyTR+QuQvIqoyhLEG/xOZ3qDkKEuZXMZ5MfCCOZGoWSjvf4+n8WsWYkBFQMHdgl/4gL+voWqwAmLApgXU81C4g20ZyM/7P9j2i9GpLRmHxwm6BNEMtRwwBHgPfmIgA0BrMwks9jLQroHSgIEIEwcQZ3rH/FA1nAhQPvevz0ytsyxg/KS8WTxGmx8ZRj04WHExiKLT9JZYzKcrtPhOF63FwyteMxGtuISrgYBKzJETKc4H0ULMes5gxVvF8gkq7ugz4L41Ib7hyDCl444oq4QlXDrZTov70fBWL9ugqgbc0AYWWlYsZKMfjVpZMfsbKPAaYzMlPuuPRp1VYgUQyRwRZ2STa8ULDd78i6KGS/vhr3upT4vpL4VSzwtillRSAAAAAElFTkSuQmCC",
                "date" => "1/27/2017"
            ]
        ];

        $this->post('/sales/generate_preview', $params, ['HTTP_Authorization' => 'bearer ' . $token['token']['access_token']]);
        $response = $this->response;
        $raw_content = $this->response->content();
        $this->assertEquals(200, $this->response->status(), $raw_content);
        $content = json_decode($raw_content, true);

        $this->assertArrayHasKey('declaration', $content, $raw_content);
        $this->assertArrayHasKey('status', $content, $raw_content);
        $this->assertArrayHasKey('payload', $content, $raw_content);

        $this->assertEquals('success', $content['status'], $raw_content);
        $this->assertEquals('questions_response_previewed', $content['declaration'], $raw_content);
    }

}
