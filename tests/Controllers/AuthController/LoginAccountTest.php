<?php

/**
 * LoginAccountTest.php
 */

namespace Tests;

use Laravel\Lumen\Testing\DatabaseMigrations;
use Laravel\Lumen\Testing\DatabaseTransactions;

/**
 * LoginAccountTest Class
 */
class LoginAccountTest extends \TestCase {

    use DatabaseTransactions;

    /**
     * Sales Rep Login.
     * Case : successfully login.
     * 
     * @return void
     * @author Amandeep Singh <amandeep@unoapp.com>
     * @version 0.0.1
     * 
     * @covers App\Http\Controllers\AuthController::login
     * 
     */
    public function testSalesRepLogin() {

        $params = [
            'username' => 'si1',
            'password' => 'welcome',
        ];
        $response = $this->call('POST', '/sales/login', $params);
        $raw_content = $response->content();
        $this->assertEquals(200, $response->status(), $raw_content);
        $content = json_decode($raw_content, true);


        $this->assertArrayHasKey('declaration', $content, $raw_content);
        $this->assertArrayHasKey('status', $content, $raw_content);
        $this->assertArrayHasKey('payload', $content, $raw_content);
        $this->assertArrayHasKey('token', $content['payload'], $raw_content);
        $this->assertArrayHasKey('access_token', $content['payload']['token'], $raw_content);
        $this->assertArrayHasKey('token_type', $content['payload']['token'], $raw_content);
        $this->assertArrayHasKey('expires_in', $content['payload']['token'], $raw_content);
        $this->assertArrayHasKey('user', $content['payload'], $raw_content);
        $this->assertArrayHasKey('isValid', $content['payload']['user'], $raw_content);
        $this->assertArrayHasKey('environment', $content['payload']['user'], $raw_content);
        $this->assertArrayHasKey('dealerId', $content['payload']['user'], $raw_content);
        $this->assertArrayHasKey('dfxUserId', $content['payload']['user'], $raw_content);

        $this->assertEquals('success', $content['status'], $raw_content);
        $this->assertEquals('user_found', $content['declaration'], $raw_content);
        $this->assertEquals('bearer', $content['payload']['token']['token_type'], $raw_content);
        $this->assertEquals('1', $content['payload']['user']['isValid'], $raw_content);
        $this->assertEquals('Prod2', $content['payload']['user']['environment'], $raw_content);
    }

    /**
     * Get configuration
     * Case : successfully get configuration.
     * 
     * @return void
     * @author Amit Panchal <amit@unoindia.co>
     * @version 0.0.1
     * 
     * @covers App\Http\Controllers\AuthController::getConfiguration
     * 
     */
    public function testGetConfiguration() {

        $token = $this->getToken();

        // Scope Headers        
        $scope_header = json_encode([
            'DealerId' => $token['user']['dealerId'],
            'CultureCode' => "en-us",
            'Environment' => config('dfx.environment')
        ]);

        $this->get('/sales/configuration', [
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
        $this->assertArrayHasKey('configurations', $content['payload'], $raw_content);


        $this->assertEquals('success', $content['status'], $raw_content);
        $this->assertEquals('get_configration', $content['declaration'], $raw_content);
    }

}
