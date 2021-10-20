<?php

/**
 * UserController.php
 */

namespace Tests;

use Laravel\Lumen\Testing\DatabaseMigrations;
use Laravel\Lumen\Testing\DatabaseTransactions;

class UserController extends \TestCase {

    use DatabaseTransactions;

    /**
     * To Validate user Code     
     *
     * @return  void
     * @author  Amit Panchal <amit@unoindia.co>
     * @version 0.0.1
     * @covers App\Http\Controllers\UserController::validateCode
     */
    public function testValidateCode() {
        $accessCode = $this->getActiveAccessCode();
        // Params
        $params = [
            'code' => $accessCode['code']
        ];
        $this->post('/user/validate_code', $params);
        $response = $this->response;
        $raw_content = $this->response->content();
        $this->assertEquals(200, $this->response->status(), $raw_content);
        $content = json_decode($raw_content, true);

        $this->assertArrayHasKey('declaration', $content, $raw_content);
        $this->assertArrayHasKey('status', $content, $raw_content);
        $this->assertArrayHasKey('payload', $content, $raw_content);

        $this->assertEquals('success', $content['status'], $raw_content);
        $this->assertEquals('user_found', $content['declaration'], $raw_content);
    }

    /**
     * To send code to user     
     *
     * @return  void
     * @author  Amit Panchal <amit@unoindia.co>
     * @version 0.0.1
     * 
     * @covers App\Http\Controllers\UserController::getProfile
     */
    public function testSendCode() {
        $user = $this->getUser();

        // Params
        $params = [
            'phone_number' => $user['phone']
        ];
        $this->post('/user/send_code', $params);
        $response = $this->response;
        $raw_content = $this->response->content();
        $this->assertEquals(200, $this->response->status(), $raw_content);
        $content = json_decode($raw_content, true);


        $this->assertArrayHasKey('declaration', $content, $raw_content);
        $this->assertArrayHasKey('status', $content, $raw_content);
        $this->assertArrayHasKey('payload', $content, $raw_content);

        $this->assertEquals('success', $content['status'], $raw_content);
        $this->assertEquals('msg_sent', $content['declaration'], $raw_content);
    }

    /**
     * To get user profile     
     *
     * @return  void
     * @author  Amit Panchal <amit@unoindia.co>
     * @version 0.0.1
     * 
     * @covers App\Http\Controllers\UserController::getProfile
     */
    public function testGetProfile() {
        $user = $this->getUser();

        // Params
        $params = [
            'user_id' => $user['id']
        ];
        $token = $this->getIceToken($params['user_id']);
        $this->post('/user/get_profile', $params, ['HTTP_auth-token' => $token]);
        $response = $this->response;
        $raw_content = $this->response->content();
        $this->assertEquals(200, $this->response->status(), $raw_content);
        $content = json_decode($raw_content, true);


        $this->assertArrayHasKey('declaration', $content, $raw_content);
        $this->assertArrayHasKey('status', $content, $raw_content);
        $this->assertArrayHasKey('payload', $content, $raw_content);

        $this->assertEquals('success', $content['status'], $raw_content);
        $this->assertEquals('user_loaded', $content['declaration'], $raw_content);
    }

    /**
     * update user information
     *
     * @return  void
     * @author  Amit Panchal <amit@unoindia.co>
     * @version 0.0.1
     * 
     * @covers App\Http\Controllers\UserController::updateProfile
     */
    public function testUpdateProfile() {
        $user = $this->getUser();
        // Params
        $params = [
            "user_id" => $user['id'],
            "full_name" => "Megh Uno",
            "email" => "meghd@unoindia.com",
            "phone" => "9898012345",
            "address" => "670 Queen Street West",
            "city" => "Toronto",
            "province" => "ON",
            "country" => "CA",
            "postalCode" => "A1B 2C3",
            "password" => "123456",
            "confirmed_password" => "123456"
        ];
        $token = $this->getIceToken($params['user_id']);
        $this->post('/user/update_profile', $params, ['HTTP_auth-token' => $token]);
        $response = $this->response;
        $raw_content = $this->response->content();
        $this->assertEquals(200, $this->response->status(), $raw_content);
        $content = json_decode($raw_content, true);

        $this->assertArrayHasKey('declaration', $content, $raw_content);
        $this->assertArrayHasKey('status', $content, $raw_content);
        $this->assertArrayHasKey('payload', $content, $raw_content);

        $this->assertEquals('success', $content['status'], $raw_content);
        $this->assertEquals('user_updated', $content['declaration'], $raw_content);
    }

    /**
     * To login user
     *
     * @return  void
     * @author  Amit Panchal <amit@unoindia.co>
     * @version 0.0.1
     * @covers App\Http\Controllers\UserController::login
     */
    public function testLogin() {
        $user = $this->getUser();
        // Params
        $params = [
            "phone_number" => $user['phone'],
            "password" => "123456"
        ];
        $this->post('/user/login', $params);
        $response = $this->response;
        $raw_content = $this->response->content();
        $this->assertEquals(200, $this->response->status(), $raw_content);
        $content = json_decode($raw_content, true);

        $this->assertArrayHasKey('message', $content, $raw_content);
        $this->assertArrayHasKey('status', $content, $raw_content);
        $this->assertArrayHasKey('payload', $content, $raw_content);

        $this->assertEquals('success', $content['status'], $raw_content);
        $this->assertEquals('user_found', $content['message'], $raw_content);
    }

    /**
     * To send email for forgot password
     *
     * @return  void
     * @author  Amit Panchal <amit@unoindia.co>
     * @version 0.0.1
     * @covers App\Http\Controllers\UserController::forgotPassword
     */
    public function testForgotPassword() {
        $user = $this->getUser();
        // Params
        $params = [
            "phone_number" => $user['phone']
        ];
        $this->post('/user/forgot_password', $params);
        $response = $this->response;
        $raw_content = $this->response->content();
        $this->assertEquals(200, $this->response->status(), $raw_content);
        $content = json_decode($raw_content, true);

        $this->assertArrayHasKey('status', $content, $raw_content);
        $this->assertArrayHasKey('declaration', $content, $raw_content);
        $this->assertArrayHasKey('payload', $content, $raw_content);

        $this->assertEquals('success', $content['status'], $raw_content);
        $this->assertEquals('email_sms_sent', $content['declaration'], $raw_content);
    }

    /**
     * Get user dealer 
     *
     * @return  void
     * @author  Amit Panchal <amit@unoindia.co>
     * @version 0.0.1
     * @covers App\Http\Controllers\UserController::getDealer
     */
    public function testGetDealer() {
        $user = $this->getUser();
        // Params        
        $params = ['user_id' => $user['id']];
        $token = $this->getIceToken($params['user_id']);
        $this->post('/user/get_my_dealer', $params, ['HTTP_auth-token' => $token]);
        $response = $this->response;
        $raw_content = $this->response->content();
        $this->assertEquals(200, $this->response->status(), $raw_content);
        $content = json_decode($raw_content, true);

        $this->assertArrayHasKey('status', $content, $raw_content);
        $this->assertArrayHasKey('declaration', $content, $raw_content);
        $this->assertArrayHasKey('payload', $content, $raw_content);

        $this->assertEquals('success', $content['status'], $raw_content);
        $this->assertEquals('dealer_found', $content['declaration'], $raw_content);
    }

    /**
     * Connect user with dealer
     * @Note This functionality has been removed so not considered it
     * @return  void
     * @author  Amit Panchal <amit@unoindia.co>
     * @version 0.0.1
     * @covers App\Http\Controllers\UserController::assignUserDealer
     */
    public function testAssignUserDealer() {
        // Params
        $params = [
            'user_id' => 1,
            "dealer_id" => 1,
            "preferred" => true
        ];
        $token = $this->getIceToken($params['user_id']);
        $this->post('/user/assign_dealer', $params, ['HTTP_auth-token' => $token]);
        $response = $this->response;
        $raw_content = $this->response->content();
        $this->assertEquals(200, $this->response->status(), $raw_content);
        $content = json_decode($raw_content, true);

        $this->assertArrayHasKey('status', $content, $raw_content);
        $this->assertArrayHasKey('declaration', $content, $raw_content);
        $this->assertArrayHasKey('payload', $content, $raw_content);

        $this->assertEquals('success', $content['status'], $raw_content);
        $this->assertEquals('user_dealer_connected', $content['declaration'], $raw_content);
    }

    /**
     * To get home screen notification
     *
     * @return  void
     * @author  Amit Panchal <amit@unoindia.co>
     * @version 0.0.1
     * @covers App\Http\Controllers\UserController::getNotification
     */
    public function testGetNotification() {
        $user = $this->getUser();
        // Params
        $params = [
            'user_id' => $user['id'],
            'vin' => $user['vehicles'][0]['vin'],
            "dealer_id" => $user['vehicles'][0]['dealer_vehicles'][0]['dealer_id'],
            "culture_code" => "en-us"
        ];

        $token = $this->getIceToken($params['user_id']);
        $this->post('/user/get_notification', $params, ['HTTP_auth-token' => $token]);
        $response = $this->response;
        $raw_content = $this->response->content();
        $this->assertEquals(200, $this->response->status(), $raw_content);
        $content = json_decode($raw_content, true);

        $this->assertArrayHasKey('status', $content, $raw_content);
        $this->assertArrayHasKey('declaration', $content, $raw_content);
        $this->assertArrayHasKey('payload', $content, $raw_content);

        $this->assertEquals('success', $content['status'], $raw_content);
        $this->assertEquals('notification_found', $content['declaration'], $raw_content);
    }

    /**
     * To get appointment information
     * @NOTE Currently giving error as call have error of "Appointment has no department"
     * @return  void
     * @author  Amit Panchal <amit@unoindia.co>
     * @version 0.0.1
     * @covers App\Http\Controllers\UserController::getAppointmentById
     */
    public function testGetAppointmentById() {
        // Params
        $params = [
            'user_id' => 1,
            'appointment_id' => 13519749,
            "dealer_id" => 1,
            "culture_code" => "en-us"
        ];

        $token = $this->getIceToken($params['user_id']);
        $this->post('/user/get_appointment_by_id', $params, ['HTTP_auth-token' => $token]);
        $response = $this->response;
        $raw_content = $this->response->content();
        $this->assertEquals(200, $this->response->status(), $raw_content);
        $content = json_decode($raw_content, true);

        $this->assertArrayHasKey('status', $content, $raw_content);
        $this->assertArrayHasKey('declaration', $content, $raw_content);
        $this->assertArrayHasKey('payload', $content, $raw_content);

        $this->assertEquals('success', $content['status'], $raw_content);
        $this->assertEquals('appointment_found', $content['declaration'], $raw_content);
    }

}
