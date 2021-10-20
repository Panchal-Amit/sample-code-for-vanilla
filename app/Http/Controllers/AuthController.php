<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use GuzzleHttp\Client as GuzzleHttpClient;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ConnectException;
use App\Models\SalesRep;
use App\Models\DealerShips;
use App\Models\BrandProperty;
use App\Exceptions\DfxException;
use App\Helpers\ErrorCodes;

class AuthController extends Controller {

    /**
     * Instance of GuzzleHttpClient
     *
     * @var client
     */
    private $client;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct() {
        $this->client = new GuzzleHttpClient([
            'base_uri' => config('dfx.df_protocol') . '://' . config('dfx.df_domain'),
        ]);
    }




    /**
     * Sales-Rep login.
     * Makes POST request to '/oauth/token'
     * Makes POST request to '/framework/validateSaleRepresentative'
     *
     * 
     * @param   Request $request
     * @return  \Illuminate\Http\Response
     * @author  Amandeep Singh <amandeep@unoapp.com>
     * @version 0.0.1
     */
    public function login(Request $request) {

        $this->validate($request, [
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $username = $request->input('username');
        $password = $request->input('password');

        $params = [
            'form_params' => [
                'grant_type' => 'client_credentials',
                'client_id' => config('dfx.client_id'),
                'client_secret' => config('dfx.client_secret'),
                'scope' => config('dfx.scope')
            ],
        ];

        try {
            $response_1 = $this->client->request('POST', "/oauth/token", $params);
            $decoded_response_1 = json_decode($response_1->getBody()->getContents());

            $params = [
                'headers' => [
                    'Authorization' => $decoded_response_1->token_type . ' ' . $decoded_response_1->access_token,
                    'Accept' => 'application/json'
                ],
                'json' => [
                    'username' => $username,
                    'password' => $password,
                ],
            ];

            $response_2 = $this->client->request('POST', "/framework/validateSaleRepresentative", $params);
            $decoded_response_2 = json_decode($response_2->getBody()->getContents());

            // Not valid user
            if (!$decoded_response_2->isValid) {
                return response()->json([
                            'status' => 'error',
                            'declaration' => 'invalid_user',
                            'payload' => [
                                'message' => 'Username and password does not match our records. Please try again.'
                            ],
                                ], 404);
            }

            // TODO: Database transaction.
            $create_dealership = [
                'dfx_id' => $decoded_response_2->dealerId,
                'status' => 1
            ];

            $dealership = DealerShips::firstOrCreate($create_dealership);

            $create_sales_rep = [
                'dfx_id' => $decoded_response_2->dfxUserId,
                'dealership_id' => $dealership->id,
                'username' => $username,
                'first_name' => $decoded_response_2->firstName,
                'last_name' => $decoded_response_2->lastName
            ];

            $sales_rep = SalesRep::updateOrCreate(['dfx_id' => $decoded_response_2->dfxUserId, 'dealership_id' => $dealership->id], $create_sales_rep);

            // To get survey credential 
            $params = [
                'headers' => [
                    'app-id' => config('survey.app_id'),
                    'app-secret' => config('survey.app_secret')
                ],
                'json' => ["access_code" => config('survey.form_access_code')],
            ];
            $this->client = new GuzzleHttpClient(['base_uri' => config('survey.survey_protocol') . '://' . config('survey.survey_domain')]);
            $survey_request = $this->client->request('POST', "/form/access_code", $params);
            $response = json_decode($survey_request->getBody()->getContents());


            $return = [
                'declaration' => 'user_found',
                'status' => 'success',
                'payload' => [
                    'token' => $decoded_response_1,
                    'user' => $decoded_response_2,
                    'survey'=>$response->payload
                ],
            ];
            return response()->json($return, 200);
            
        } catch (ConnectException $e) {
            $log_message = ErrorCodes::GATEWAY_TIMEOUT . ' : ' . $e->getMessage();
            throw new DfxException(ErrorCodes::GATEWAY_TIMEOUT, 'gateway_timeout', $log_message, 504);
        } catch (RequestException $e) {
            DfxException::dfxExceptionHandler($e);
        }
    }

    /**
     * Get configuration
     * Makes POST request to '/sales/configuration'
     *
     * @return  void
     * @author  Amit Panchal <amit@unoindia.co>
     * @version 0.0.1
     */
    public function getConfiguration(Request $request) {

        //Scope
        $scope = json_decode($request->header('scope'), true);

        $dealerId = $scope['DealerId'];

        // User Login, should create dealership in system.
        $dealership = DealerShips::where('dfx_id', '=', $dealerId)->first();
        if (is_null($dealership)) {
            return response()->json([
                        'status' => 'error',
                        'declaration' => 'dealership_not_found',
                        'payload' => [
                            'message' => 'Sorry, dealer not found. Please try again.'
                        ]
                            ], 404);
        }

        // Scope Headers
        $scope_header = json_encode([
            'DealerId' => $dealerId,
            'Environment' => config('dfx.environment')
        ]);

        // Params
        $params = [
            'headers' => [
                'Authorization' => $request->header('Authorization'),
                'Scope' => $scope_header,
            ]
        ];

        try {

            $response = $this->client->request('GET', "configuration", $params);
            $decoded_response = json_decode($response->getBody()->getContents(), true);
                        
            
            $update_dealer = ['name' => isset($decoded_response['configurations']['dealerName']) ? $decoded_response['configurations']['dealerName'] : '',
                'phone_number' => isset($decoded_response['configurations']['phone']) ? $decoded_response['configurations']['phone'] : '',
                'email' => isset($decoded_response['configurations']['email']) ? $decoded_response['configurations']['email'] : '',
                'address_1' => isset($decoded_response['configurations']['address']) ? $decoded_response['configurations']['address'] : '',
                'oem_logo' => isset($decoded_response['configurations']['oemLogoUrl']) ? $decoded_response['configurations']['oemLogoUrl'] : '',
                'oem_name' => isset($decoded_response['configurations']['mainManufacturerName']) ? $decoded_response['configurations']['mainManufacturerName'] : '',
                'oem_id' => isset($decoded_response['configurations']['oemId']) ? $decoded_response['configurations']['oemId'] : '',
                'oem_video_url' => isset($decoded_response['configurations']['videoUrl']) ? $decoded_response['configurations']['videoUrl'] : ''
            ];
            DealerShips::where('dfx_id', $dealerId)->update($update_dealer);
            $mainManufacturerName = (strtoupper($decoded_response['configurations']['mainManufacturerName']) == 'CHRYSLER') ? 'MOPAR' : $decoded_response['configurations']['mainManufacturerName'];
            $brandProperty = BrandProperty::where('oem_name', $mainManufacturerName)->first();
            if (!is_null($brandProperty)) {
                $brandProperty->toArray();
                $brandProperty['logo'] = isset($decoded_response['configurations']['oemLogoUrl']) ? $decoded_response['configurations']['oemLogoUrl'] : '';
                $decoded_response['configurations']['brand_property'] = $brandProperty;
            }

            /* They want static for Magic Toyota
             * Confirmed with Arun.
             * Comments by: Amandeep Singh
             */
            if($dealerId == 100265){
                $decoded_response['configurations']['enrolledInIceApp'] = 'false';
            }
            //Temporary line added to check because its always coming false from DFX
            $decoded_response['configurations']['showPreDeliveryChecklist'] = 'True';

            return response()->json([
                        'status' => 'success',
                        'declaration' => 'get_configration',
                        'payload' => $decoded_response,
                            ], 200);
        } catch (ConnectException $e) {

            $log_message = ErrorCodes::GATEWAY_TIMEOUT . ' : ' . $e->getMessage();
            throw new DfxException(ErrorCodes::GATEWAY_TIMEOUT, 'gateway_timeout', $log_message, 504);
        } catch (RequestException $e) {
            DfxException::dfxExceptionHandler($e);
        }
    }

    /**
     * Get token without username and password
     * Makes POST request to 'oauth/token'
     *
     * @return  void
     * @author  Amit Panchal <amit@unoindia.co>
     * @version 0.0.1
     */
    public function getToken(Request $request) {


        $params = [
            'form_params' => [
                'grant_type' => 'client_credentials',
                'client_id' => config('dfx.client_id'),
                'client_secret' => config('dfx.client_secret'),
                'scope' => config('dfx.scope')
            ],
        ];

        try {

            $response = $this->client->request('POST', "oauth/token", $params);
            $decoded_response = json_decode($response->getBody()->getContents());

            return response()->json([
                        'status' => 'success',
                        'declaration' => 'token_got',
                        'payload' => $decoded_response,
                            ], 200);
        } catch (ConnectException $e) {
            $log_message = ErrorCodes::GATEWAY_TIMEOUT . ' : ' . $e->getMessage();
            throw new DfxException(ErrorCodes::GATEWAY_TIMEOUT, 'gateway_timeout', $log_message, 504);
        } catch (RequestException $e) {
            DfxException::dfxExceptionHandler($e);
        }
    }

    /**
     * Get dealer location  detail
     * Makes GET request to '/dealer/locations'
     *
     * @return  void
     * @author  Amit Panchal <amit@unoindia.co>
     * @version 0.0.1
     */
    public function getDealerLocation(Request $request) {

        //Scope
        $scope = json_decode($request->header('scope'), true);
        $dealerId = $scope['DealerId'];
        $dfxUserId = $scope['DfxUserId'];


        // User Login, should create dealership in system.
        $dealership = DealerShips::where('dfx_id', '=', $dealerId)->first();
        if (is_null($dealership)) {
            return response()->json([
                        'status' => 'error',
                        'declaration' => 'dealership_not_found',
                        'payload' => [
                            'message' => 'Sorry, dealer not found. Please try again.'
                        ]
                            ], 404);
        }

        // Scope Headers
        $scope_header = json_encode([
            'DealerId' => $dealerId,
            'Environment' => config('dfx.environment'),
            'IceUserId' => $dfxUserId
        ]);

        // Params
        $params = [
            'headers' => [
                'Authorization' => $request->header('Authorization'),
                'Scope' => $scope_header,
            ]
        ];
        try {
            $this->client = new GuzzleHttpClient(['base_uri' => config('dfx.df_protocol') . '://' . config('dfx.df_domain')]);
            $response = $this->client->request('GET', "/dealer/locations", $params);
            $dealerLocation = json_decode($response->getBody()->getContents(), true);

            //===================Start Temporary Code===================================
            if (count($dealerLocation['dealerLocations']) <= 0) {
                $dealerLocation['dealerLocations'][] = [
                    "dealerId" => $dealerId,
                    "dealerName" => "Griffin Chrysler Jeep Dodge",
                    "countryId" => "us",
                    "address" => "961 E US Hwy 74",
                    "city" => "Rockingham",
                    "state" => "NC",
                    "zip" => "28379",
                    "latitude" => "34.952565",
                    "longitude" => "-79.843338",
                    "latitudeR" => "0.610037",
                    "longitudeR" => "-1.393529",
                    "phone" => "9105821200",
                    "email" => "andy@griffinchrysler.com",
                    "directions" => "",
                    "roadSideAssistanceNumber" => "6288888888",
                    "smsNumber" => "+16478888888",
                    "isDefaultDealership" => false
                ];
            }
            //===================End Temporary Code===================================

            if (count($dealerLocation['dealerLocations']) > 0) {
                foreach ($dealerLocation['dealerLocations'] as $key => $val) {

                    DealerShips::where(['dfx_id' => $dealerId])->update([
                        'address_1' => $val['address'],
                        'city' => $val['city'],
                        'postal_code' => $val['zip'],
                        'latitude' => $val['latitude'],
                        'longitude' => $val['longitude'],
                        'email' => $val['email'],
                        'phone_number' => $val['phone'],
                        'sms_number' => $val['smsNumber'],
                        'roadside_assist' => $val['roadSideAssistanceNumber']
                    ]);
                }
            }

            return response()->json([
                        'status' => 'success',
                        'declaration' => 'location_found',
                        'payload' => $dealerLocation['dealerLocations'],
                            ], 200);
        } catch (ConnectException $e) {
            $log_message = ErrorCodes::GATEWAY_TIMEOUT . ' : ' . $e->getMessage();
            throw new DfxException(ErrorCodes::GATEWAY_TIMEOUT, 'gateway_timeout', $log_message, 504);
        } catch (RequestException $e) {
            DfxException::dfxExceptionHandler($e);
        }
    }

}
