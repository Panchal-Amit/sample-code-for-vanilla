<?php

/**
 * ServiceController.php
 */

namespace Tests;

use Laravel\Lumen\Testing\DatabaseMigrations;
use Laravel\Lumen\Testing\DatabaseTransactions;
define('CLIENT_TOKEN', 'FC0D4584-947A-4E1E-8A3E-887D4DE3F786');

class ServiceController extends \TestCase {

    use DatabaseTransactions;
      
    /**
     * To Get DMPI Meta data
     *
     * @return  void
     * @author  Amit Panchal <amit@unoindia.co>
     * @version 0.0.1
     * @covers App\Http\Controllers\ServiceController::getMetaData
     */
    public function testGetMetaData() {        
        // Params
        $params = [
            'user_id' => '1',
            "client_token" => CLIENT_TOKEN,
            "lan" => "en-CA"
        ];
        $token = $this->getIceToken($params['user_id']);       
        $this->post('/service/get_metadata', $params, ['HTTP_auth-token' => $token]);        
        $response = $this->response;        
        $raw_content = $this->response->content();
        $this->assertEquals(200, $this->response->status(), $raw_content);
        $content = json_decode($raw_content, true);                

        $this->assertArrayHasKey('declaration', $content, $raw_content);
        $this->assertArrayHasKey('status', $content, $raw_content);
        $this->assertArrayHasKey('payload', $content, $raw_content);

        $this->assertEquals('success', $content['status'], $raw_content);
        $this->assertEquals('metadata_found', $content['declaration'], $raw_content);
    }

    /**
     * To get inspection link
     *
     * @return  void
     * @author  Amit Panchal <amit@unoindia.co>
     * @version 0.0.1
     * @covers App\Http\Controllers\ServiceController::getInspectionLink
     */
    public function testGetInspectionLink() {
        // Params
        $params = [
            'user_id' => '1',
            "client_token" => CLIENT_TOKEN,
            "lan" => "en-CA"
        ];
        $token = $this->getIceToken($params['user_id']);
        $this->post('/service/get_inspection_link', $params, ['HTTP_auth-token' => $token]);
        $response = $this->response;
        $raw_content = $this->response->content();
        $this->assertEquals(200, $this->response->status(), $raw_content);
        $content = json_decode($raw_content, true);

        $this->assertArrayHasKey('declaration', $content, $raw_content);
        $this->assertArrayHasKey('status', $content, $raw_content);
        $this->assertArrayHasKey('payload', $content, $raw_content);
        $this->assertArrayHasKey('fullInspectionUrl', $content['payload'], $raw_content);


        $this->assertEquals('success', $content['status'], $raw_content);
        $this->assertEquals('inspection_found', $content['declaration'], $raw_content);
    }
    /**
     * To calculate tax
     *
     * @return  void
     * @author  Amit Panchal <amit@unoindia.co>
     * @version 0.0.1
     * @covers App\Http\Controllers\ServiceController::calculateTax
     */
    public function testCalculateTax() {
        // Params
        $params = [
            'user_id' => '1',
            "client_token" => CLIENT_TOKEN,
            "lan" => "en-CA",
            "selectedServices" => ["HOP", "FRT"]
        ];
        $token = $this->getIceToken($params['user_id']);
        $this->post('/service/calculate_tax', $params, ['HTTP_auth-token' => $token]);
        $response = $this->response;
        $raw_content = $this->response->content();
        $this->assertEquals(200, $this->response->status(), $raw_content);
        $content = json_decode($raw_content, true);

        $this->assertArrayHasKey('declaration', $content, $raw_content);
        $this->assertArrayHasKey('status', $content, $raw_content);
        $this->assertArrayHasKey('payload', $content, $raw_content);

        $this->assertEquals('success', $content['status'], $raw_content);
        $this->assertEquals('service_found', $content['declaration'], $raw_content);
    }
    /**
     * To calculate tax
     *
     * @return  void
     * @author  Amit Panchal <amit@unoindia.co>
     * @version 0.0.1
     * @covers App\Http\Controllers\ServiceController::calculateTax
     */
    public function testApproveService() {
        // Params
        $params = [
            'user_id' => '1',
            "client_token" => CLIENT_TOKEN,
            "lan" => "en-CA",
            "approvedServices" => [
                [
                    "id" => "HOP",
                    "name" => "Brake Pad Replacement",
                    "category" => 1,
                    "price" => 379.9,
                    "taxAndFee" => 56.98,
                    "isSelected" => false,
                    "parentServiceId" => "",
                    "comment" => "Worn brake pads can result in a loud squeaking or grinding noise while driving, especially at low speeds. Replacing your brake pads early may prevent the brake rotors from being worn or damaged beyond repair.",
                    "imageUrl" => "http://unoapp.com/dfx-mpi/v1/images/nna.jpg",
                    "reasonId" => 5,
                    "isApproved" => false
                ]
            ],
            "customerSignature" => "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAeAAAAB4CAYAAAAqliEPAAAQYUlEQVR4Xu3df 7AWVR3H8e/l/gAU64+Uqa45UY0DCcRMRc2QM17LmdRxckLgWgnCiMCVZioTG/8h+sNpBKspftnNH5DDoIDp2K DOhGiOVkYlAf2YZGCwmBGvfyQlcn+2Z68bz314Ls/us+ec7+657+skBrt7dl/n8Hyec3b3nKah6Ef4QQABBBB AAAGvAk0EsFdvCkMAAQQQQCAWIIBpCAgggAACCCgIEMAK6BSJAAIIIIAAAUwbQAABBBBAQEGAAFZAp0gEEEAA AQQIYNoAAhUCV925QwYHh2TP2vm4IIAAAk4FCGCnvBy8bAIX"
        ];
        $token = $this->getIceToken($params['user_id']);
        $this->post('/service/approve_service', $params, ['HTTP_auth-token' => $token]);
        $response = $this->response;
        $raw_content = $this->response->content();
        $this->assertEquals(200, $this->response->status(), $raw_content);
        $content = json_decode($raw_content, true);
       
        $this->assertArrayHasKey('declaration', $content, $raw_content);
        $this->assertArrayHasKey('status', $content, $raw_content);
        $this->assertArrayHasKey('payload', $content, $raw_content);

        $this->assertEquals('success', $content['status'], $raw_content);
        $this->assertEquals('service_approved', $content['declaration'], $raw_content);
    }

}
