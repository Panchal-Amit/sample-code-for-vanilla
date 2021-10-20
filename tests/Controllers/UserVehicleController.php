<?php

/**
 * UserVehicleController.php
 */

namespace Tests;

use Laravel\Lumen\Testing\DatabaseMigrations;
use Laravel\Lumen\Testing\DatabaseTransactions;

class UserVehicleController extends \TestCase {

    use DatabaseTransactions;

    /**
     * To get user vehicle
     *
     * @return  void
     * @author  Amit Panchal <amit@unoindia.co>
     * @version 0.0.1
     * @covers App\Http\Controllers\UserVehicleController::getMyVehicle
     */
    public function testGetMyVehicle() {
        $user = $this->getUser();
        // Params
        $params = [
            'user_id' => $user['id'],
            "dealer_id" => $user['vehicles'][0]['dealer_vehicles'][0]['dealer_id'],
            "culture_code" => "en-us"
        ];

        $token = $this->getIceToken($params['user_id']);
        $this->post('/user/get_vehicle', $params, ['HTTP_auth-token' => $token]);
        $response = $this->response;
        $raw_content = $this->response->content();
        $this->assertEquals(200, $this->response->status(), $raw_content);
        $content = json_decode($raw_content, true);

        $this->assertArrayHasKey('status', $content, $raw_content);
        $this->assertArrayHasKey('declaration', $content, $raw_content);
        $this->assertArrayHasKey('payload', $content, $raw_content);

        $this->assertEquals('success', $content['status'], $raw_content);
        $this->assertEquals('user_vehicle_loaded', $content['declaration'], $raw_content);
    }

    /**
     * To assign vehicle to user
     * @Note : This functionality is disable right now
     * @return  void
     * @author  Amit Panchal <amit@unoindia.co>
     * @version 0.0.1
     * @covers App\Http\Controllers\UserVehicleController::assignVehicle
     */
    public function testAssignVehicle() {
        // Params
        $params = [
            'user_id' => 1,
            "dealer_id" => 4363,
            "vin" => "1N4AL3AP4FN367185",
            "manufacturer_id" => 1,
            "model" => "Altima",
            "year" => "2008",
            "transmissions" => "CVT",
            "cylinder" => "4CYL",
            "drive" => "2WD"
        ];
        $token = $this->getIceToken($params['user_id']);
        $this->post('/user/add_vehicle', $params, ['HTTP_auth-token' => $token]);
        $response = $this->response;
        $raw_content = $this->response->content();
        $this->assertEquals(200, $this->response->status(), $raw_content);
        $content = json_decode($raw_content, true);

        $this->assertArrayHasKey('status', $content, $raw_content);
        $this->assertArrayHasKey('declaration', $content, $raw_content);
        $this->assertArrayHasKey('payload', $content, $raw_content);

        $this->assertEquals('success', $content['status'], $raw_content);
        $this->assertEquals('user_vehicle_connected', $content['declaration'], $raw_content);
    }

}
