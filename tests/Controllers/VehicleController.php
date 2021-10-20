<?php

/**
 * VehicleController.php
 */

namespace Tests;

use Laravel\Lumen\Testing\DatabaseMigrations;
use Laravel\Lumen\Testing\DatabaseTransactions;

class VehicleController extends \TestCase {

    use DatabaseTransactions;

    /**
     * Get vehicle by vin .
     * Case : successfully get Vehicle.
     * 
     * @return void
     * @author Amit Panchal <amit@unoindia.co>
     * @version 0.0.1
     * 
     * @covers App\Http\Controllers\VehicleController::getByVin
     * 
     */
    public function testGetVehicleByVin() {

        $token = $this->getToken();
        // Params
        $params = [
            'vin' => '1N4AA5AP8DC809878',
            'dealer_id' => $token['user']['dealerId'],
            'culture_code' => "en-us"
        ];

        $this->post('/sales/vehicle', $params, ['HTTP_Authorization' => 'bearer ' . $token['token']['access_token']]);
        $response = $this->response;
        $raw_content = $this->response->content();
        $this->assertEquals(200, $this->response->status(), $raw_content);
        $content = json_decode($raw_content, true);

        $this->assertArrayHasKey('declaration', $content, $raw_content);
        $this->assertArrayHasKey('status', $content, $raw_content);
        $this->assertArrayHasKey('payload', $content, $raw_content);
        $this->assertArrayHasKey('vehicle', $content['payload'], $raw_content);
        $this->assertArrayHasKey('id', $content['payload']['vehicle'], $raw_content);
        $this->assertArrayHasKey('description', $content['payload']['vehicle'], $raw_content);
        $this->assertArrayHasKey('imageUrl', $content['payload']['vehicle'], $raw_content);
        $this->assertArrayHasKey('manufacturerId', $content['payload']['vehicle'], $raw_content);
        $this->assertArrayHasKey('manufacturerName', $content['payload']['vehicle'], $raw_content);
        $this->assertArrayHasKey('year', $content['payload']['vehicle'], $raw_content);
        $this->assertArrayHasKey('model', $content['payload']['vehicle'], $raw_content);
        $this->assertArrayHasKey('transmission', $content['payload']['vehicle'], $raw_content);
        $this->assertArrayHasKey('vehicleCylinder', $content['payload']['vehicle'], $raw_content);
        $this->assertArrayHasKey('drive', $content['payload']['vehicle'], $raw_content);
        $this->assertArrayHasKey('color', $content['payload']['vehicle'], $raw_content);

        $this->assertArrayHasKey('customer', $content['payload'], $raw_content);
        $this->assertArrayHasKey('id', $content['payload']['customer'], $raw_content);
        $this->assertArrayHasKey('firstName', $content['payload']['customer'], $raw_content);
        $this->assertArrayHasKey('lastName', $content['payload']['customer'], $raw_content);
        $this->assertArrayHasKey('email', $content['payload']['customer'], $raw_content);
        $this->assertArrayHasKey('cellPhone', $content['payload']['customer'], $raw_content);
        $this->assertArrayHasKey('homePhone', $content['payload']['customer'], $raw_content);
        $this->assertArrayHasKey('address', $content['payload']['customer'], $raw_content);
        $this->assertArrayHasKey('city', $content['payload']['customer'], $raw_content);
        $this->assertArrayHasKey('state', $content['payload']['customer'], $raw_content);
        $this->assertArrayHasKey('country', $content['payload']['customer'], $raw_content);
        $this->assertArrayHasKey('postalCode', $content['payload']['customer'], $raw_content);
        $this->assertArrayHasKey('county', $content['payload']['customer'], $raw_content);


        $this->assertEquals('success', $content['status'], $raw_content);
        $this->assertEquals('vehicle_found', $content['declaration'], $raw_content);
    }

    /**
     * get vehicle makes
     * Case : successfully get Vehicle makes.
     * 
     * @return void
     * @author Amit Panchal <amit@unoindia.co>
     * @version 0.0.1
     * 
     * @covers App\Http\Controllers\VehicleController::getMakes
     * 
     */
    public function testGetMakes() {

        $token = $this->getToken();
        // Params
        $params = [
            'username' => 'si1',
            'password' => "welcome"
        ];

        // Scope Headers
        $scope_header = json_encode([
            'DealerId' => $token['user']['dealerId'],
            'CultureCode' => "en-us",
            'Environment' => config('dfx.environment'),
        ]);

        $this->post('/sales/makes', $params, ['HTTP_Authorization' => 'bearer ' . $token['token']['access_token'],
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
        $this->assertEquals('makes_found', $content['declaration'], $raw_content);
    }

    /**
     * get vehicle year of makes
     * Case : successfully get Vehicle year of makes.
     * 
     * @return void
     * @author Amit Panchal <amit@unoindia.co>
     * @version 0.0.1
     * 
     * @covers App\Http\Controllers\VehicleController::getYearsOfMakes
     * 
     */
    public function testGetYearsOfMakes() {
        $token = $this->getToken();
        // Params
        $params = [
            'username' => 'si1',
            'password' => "welcome"
        ];
        $makesId = 1;

        $this->post('/sales/make/' . $makesId . '/year', $params, ['HTTP_Authorization' => 'bearer ' . $token['token']['access_token']]);

        $response = $this->response;
        $raw_content = $this->response->content();
        $this->assertEquals(200, $this->response->status(), $raw_content);
        $content = json_decode($raw_content, true);

        $this->assertArrayHasKey('declaration', $content, $raw_content);
        $this->assertArrayHasKey('status', $content, $raw_content);
        $this->assertArrayHasKey('payload', $content, $raw_content);

        $this->assertEquals('success', $content['status'], $raw_content);
        $this->assertEquals('years_found', $content['declaration'], $raw_content);
    }

    /**
     * get model
     * Case : successfully get model based on makes id and year.
     * 
     * @return void
     * @author Amit Panchal <amit@unoindia.co>
     * @version 0.0.1
     * 
     * @covers App\Http\Controllers\VehicleController::getModels
     * 
     */
    public function testGetModels() {

        $token = $this->getToken();
        // Params
        $params = [
            'make_id' => '4',
            'year' => '2007'
        ];
        $makesId = 1;

        $this->post('/sales/models', $params, ['HTTP_Authorization' => 'bearer ' . $token['token']['access_token']]);
        $response = $this->response;
        $raw_content = $this->response->content();
        $this->assertEquals(200, $this->response->status(), $raw_content);
        $content = json_decode($raw_content, true);

        $this->assertArrayHasKey('declaration', $content, $raw_content);
        $this->assertArrayHasKey('status', $content, $raw_content);
        $this->assertArrayHasKey('payload', $content, $raw_content);

        $this->assertEquals('success', $content['status'], $raw_content);
        $this->assertEquals('models_found', $content['declaration'], $raw_content);
    }

    /**
     * get model
     * Case : successfully get transmission.
     * 
     * @return void
     * @author Amit Panchal <amit@unoindia.co>
     * @version 0.0.1
     * 
     * @covers App\Http\Controllers\VehicleController::getTransmission
     * 
     */
    public function testGetTransmission() {

        $token = $this->getToken();
        // Params
        $params = [
            'make_id' => '4',
            'year' => '2017',
            'model_name' => "MDX (3.5L)"
        ];

        $this->post('/sales/model/transmission', $params, ['HTTP_Authorization' => 'bearer ' . $token['token']['access_token']]);
        $response = $this->response;
        $raw_content = $this->response->content();
        $this->assertEquals(200, $this->response->status(), $raw_content);
        $content = json_decode($raw_content, true);


        $this->assertArrayHasKey('declaration', $content, $raw_content);
        $this->assertArrayHasKey('status', $content, $raw_content);
        $this->assertArrayHasKey('payload', $content, $raw_content);

        $this->assertEquals('success', $content['status'], $raw_content);
        $this->assertEquals('transmission_found', $content['declaration'], $raw_content);
    }

}
