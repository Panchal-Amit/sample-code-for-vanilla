<?php

use App\Models\User\AuthToken;
use App\Models\AccessCode;
use App\Models\User\User;

abstract class TestCase extends Laravel\Lumen\Testing\TestCase {

    /**
     * Creates the application.
     *
     * @return \Laravel\Lumen\Application
     */
    public function createApplication() {
        return require __DIR__ . '/../bootstrap/app.php';
    }

    /**
     * To get access token    
     * @return void
     * @author Amit Panchal <amit@unoindia.co>
     * @version 0.0.1          
     * 
     */
    public function getToken() {
        $params = [
            'username' => 'si1',
            'password' => 'welcome',
        ];
        $response = $this->call('POST', '/sales/login', $params);
        $raw_content = $response->content();
        $this->assertEquals(200, $response->status(), $raw_content);
        $content = json_decode($raw_content, true);
        return $content['payload'];
    }

    /**
     * To get user's latest token for ICE App
     * @return void
     * @author Amit Panchal <amit@unoindia.co>
     * @version 0.0.1          
     * 
     */
    public function getIceToken($userId) {
        $authObj = AuthToken::where('user_id', '=', $userId)->orderBy('id', 'desc')->first();
        $data = json_decode($authObj, true);
        return $data['token'];
    }

    /**
     * To get active access code
     * @return void
     * @author Amit Panchal <amit@unoindia.co>
     * @version 0.0.1          
     */
    public function getActiveAccessCode() {
        $accessCode = AccessCode::with('user')->where('status', '=', 'active')->first()->toArray();
        return (count($accessCode) > 0) ? $accessCode : array();
    }

    /**
     * To get first user
     * @return void
     * @author Amit Panchal <amit@unoindia.co>
     * @version 0.0.1          
     */
    public function getUser() {
        $user = User::with(['vehicles' => function($query) {
                        $query->with('dealerVehicles')->first();
                    }])->where('password', '!=', "")->first()->toArray();
        return (count($user) > 0) ? $user : array();
    }

}
