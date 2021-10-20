<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\App;
use Illuminate\Http\Request;
use GuzzleHttp\Client as GuzzleHttpClient;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ConnectException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;
use App\Models\Vehicles;
use App\Models\DealerShips;
use App\Models\CommContents;
use App\Models\CommChannels;
use App\Models\User\User;
use App\Models\UserVehicles;
use App\Models\User\UserAddress;
use App\Models\User\UserContentChannels;
use App\Models\AccessCode;
use App\Models\Questions;
use App\Models\QuestionsResponse;
use App\Models\User\UserDealerShips;
use App\Models\PreDelivery;
use App\Models\Manufacturers;
use App\Exceptions\DfxException;
use App\Helpers\ErrorCodes;
use App\Models\DealerVehicle;
use App\Models\QuestionsOptions;
use App\Models\UserDocument;
use App\Models\Notifications;
use App\Http\Controllers\NotificationController;

/**
 * Customer Controller
 */
class CustomerController extends Controller {

    /**
     * Instance of GuzzleHttpClient
     *
     * @var client
     */
    private $client;

    /**
     * Constructor.
     *
     * @return void
     */
    public function __construct() {
        $this->client = new GuzzleHttpClient([
            'base_uri' => config('dfx.df_protocol') . '://' . config('dfx.df_domain'),
        ]);
    }

    /**
     * Creates new customer.
     * Makes POST request to '/sale2servicesession'
     * @note   As per discussion with @Amandeep if user change customer information at post user what ever information get at time of get by vin then need to update user table instead of create new user
     * @return  void
     * @author  Amandeep Singh <amandeep@unoapp.com>
     * @version 0.0.1
     *
     * [Aman]: Need to remove customer preference loops. DB transaction.
     */
    public function createNewCustomer(Request $request) {
        // Input validations
        $this->validate($request, [
            'dealer_id' => 'required|integer',
            'culture_code' => 'required|string',
            'vehicle' => 'required|array',
            'vehicle.vin' => 'required|alpha_num|max:17',
            'vehicle.manufacturerId' => 'required|integer',
            'vehicle.manufacturerName' => 'nullable',
            'vehicle.year' => 'required',
            'vehicle.model' => 'required',
            'vehicle.transmission' => 'required',
            'vehicle.engine' => 'required',
            'vehicle.driveTrain' => 'required',
            'vehicle.mileage' => 'required',
            'customer' => 'required|array',
            'customer.customerId' => 'nullable',
            'customer.firstName' => 'required|max:10|regex:/^[A-Za-z0-9\.\-\s]+$/',
            'customer.lastName' => 'required|max:15|regex:/^[A-Za-z0-9\.\-\s]+$/',
            'customer.email' => 'required|email',
            'customer.cellPhone' => 'required|max:10',
            'customer.address' => 'required|regex:/^[A-Za-z0-9\.\#\-\s]+$/',
            'customer.city' => 'required|max:15|regex:/^[a-zA-Z0-9\.\-_\s]+$/',
            'customer.state' => 'required|max:2|regex:/^[a-zA-Z]+$/',
            'customer.postalCode' => array('required', 'regex:/^((\d{5}-\d{4})|(\d{5})|(\d{9})|([A-Z]\d[A-Z]\s\d[A-Z]\d)|([A-Z]\d[A-Z]\d[A-Z]\d))$/'),
            'customer.country' => 'required|max:3',
            'customer.county' => 'required',
            'customerPreference' => 'required|array',
            'isNewVehicle' => 'required|boolean',
            'vehicleDeliveryDate' => 'required'
        ]);

        $input_request = $request->all();
        $scope = json_decode($request->header('scope'));        
        $dealer_id = $request->input('dealer_id');

        // AS per discussion with @Arun right now need to truncate address if its more than 28 character
        $originalAddress = $input_request['customer']['address'];
        $input_request['customer']['address'] = substr($input_request['customer']['address'], 0, 28);

        // User Login, should create dealership in system.
        $dealership = DealerShips::where('dfx_id', '=', $dealer_id)->first();
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
            'DealerId' => $dealer_id,
            'CultureCode' => $request->input('culture_code'),
            'Environment' => config('dfx.environment'),
            "dfxUserId" => $scope->DfxUserId
        ]);

        // Params
        $params = [
            'headers' => [
                'Authorization' => $request->header('Authorization'),
                'Scope' => $scope_header,
            ],
            'json' => $input_request,
        ];        
        // As per Discussion with @arun right now
        try {
            $response = $this->client->request('POST', "/sale2servicesession", $params);
            $decoded_response = json_decode($response->getBody()->getContents());

            if (is_null($decoded_response->customerId)) {
                return response()->json([
                            'status' => 'error',
                            'declaration' => 'customer_not_found',
                            'payload' => ['message' => 'Sorry, customer not found. Please try again.'],
                                ], 404);
            }

            // TODO: Database transaction.
            $manufacturerName = (isset($input_request['vehicle']['manufacturerName'])) ? $input_request['vehicle']['manufacturerName'] : '';
            $create_manufacturer = ['dfx_id' => $input_request['vehicle']['manufacturerId'], 'dealership_id' => $dealership->id, 'name' => $manufacturerName];
            $manufacturer = Manufacturers::firstOrCreate(['dfx_id' => $input_request['vehicle']['manufacturerId'], 'dealership_id' => $dealership->id], $create_manufacturer);

            $vehicleArr = [
                'model' => $input_request['vehicle']['model'],
                'year' => $input_request['vehicle']['year'],
                'transmissions' => $input_request['vehicle']['transmission'],
                'cylinder' => $input_request['vehicle']['engine'],
                'drive' => $input_request['vehicle']['driveTrain'],
                'odometer' => $input_request['vehicle']['mileage']
            ];
            $vehicle = Vehicles::updateOrCreate(['vin' => $input_request['vehicle']['vin']], $vehicleArr);

            if (is_null($vehicle)) {
                return response()->json([
                            'status' => 'error',
                            'declaration' => 'vehicle_not_found',
                            'payload' => ['message' => 'Sorry, vehicle not found. Please try again.'],
                                ], 404);
            }

            $create_vehicle_relation = [
                'vehicle_id' => $vehicle->id,
                'dealer_id' => $dealership->id,
                'manufacturer_id' => $manufacturer->id
            ];
            $relationVehicleDealer = DealerVehicle::firstOrCreate($create_vehicle_relation, $create_vehicle_relation);

            //$userVehicle = UserVehicles::where(['vehicle_id' => $vehicle->id])->first();            

            if (!is_null($decoded_response->customerId)) {

                // Check if user already exists in database.
                /* if (is_null($userVehicle)) {
                  $user = User::create(['dfx_id' => $decoded_response->customerId]);
                  } else {
                  $user = User::updateOrCreate(['id' => $userVehicle->user_id], [ 'dfx_id' => $decoded_response->customerId, 'password' => '']);
                  } */

                //$user = User::updateOrCreate(['dfx_id' => $decoded_response->customerId], [ 'dfx_id' => $decoded_response->customerId]);
                $userInformation = [
                    'first_name' => $input_request['customer']['firstName'],
                    'last_name' => $input_request['customer']['lastName'],
                    'email' => $input_request['customer']['email'],
                    'phone' => $input_request['customer']['cellPhone']
                ];
                $user = User::updateOrCreate(['phone' => $input_request['customer']['cellPhone']], $userInformation);

                $user->userAddresses()->updateOrCreate(['user_id' => $user->id], [
                    'user_id' => $user->id,
                    'address_1' => $originalAddress,
                    'address_2' => (isset($input_request['customer']['address_2'])) ? trim($input_request['customer']['address_2']) : "",
                    'city' => $input_request['customer']['city'],
                    'province' => $input_request['customer']['state'],
                    'country' => $input_request['customer']['country'],
                    'county' => $input_request['customer']['county'],
                    'postal_code' => $input_request['customer']['postalCode'],
                    'latitude' => $input_request['customer']['lat'],
                    'longitude' => $input_request['customer']['long']
                ]);


                $connectedVehicleOtherUser = UserVehicles::where('vehicle_id', '=', $vehicle->id)->where('user_id', '!=', $user->id)->select('id')->get()->toArray();
                $ids = array_column($connectedVehicleOtherUser, 'id');
                UserVehicles::whereIn('id', $ids)->delete();

                UserVehicles::firstOrCreate(['vehicle_id' => $vehicle->id], ['user_id' => $user->id, 'vehicle_id' => $vehicle->id]);

                //To Add dealer to user 
                // Need to ask regarding preferred                
                $userDealer = UserDealerShips::updateOrCreate(['user_id' => $user->id, 'dealership_id' => $dealership->id], ['user_id' => $user->id, 'dealership_id' => $dealership->id, 'user_dfx_id' => $decoded_response->customerId, 'preferred' => 1]);

                //At a time only one dealer can be set as preferred
                UserDealerShips::where('user_id', '=', $user->id)->where('dealership_id', '<>', $dealership->id)->update(['preferred' => 0]);

                // To Save Preferences
                if (count($input_request['customerPreference']) > 0) {
                    foreach ($input_request['customerPreference'] as $preferenceKey => $preferenceVal) {
                        $commContent = CommContents::firstOrCreate(['name' => $preferenceVal['notificationPreference']]);

                        $user_content_channel = array();
                        foreach ($preferenceVal as $preferenceChannelKey => $preferenceChannelVal) {
                            if ($preferenceChannelKey != 'notificationPreference') {
                                $commChannel = CommChannels::firstOrCreate(['name' => $preferenceChannelKey]);

                                $user_content_channel = [
                                    'user_id' => $user->id,
                                    'content_id' => $commContent->id,
                                    'channel_id' => $commChannel->id,
                                    'status' => (boolean) $preferenceChannelVal
                                ];
                                $userContentChangel = UserContentChannels::updateOrCreate(['user_id' => $user->id, 'content_id' => $commContent->id, 'channel_id' => $commChannel->id], $user_content_channel);
                            }
                        }
                    }
                }
            }

            return response()->json([
                        'status' => 'success',
                        'declaration' => 'customer_created',
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
     * Get welcome pdf link
     * Makes POST request to '/document/welcomepackage'
     *
     * @return  void
     * @author  Amit Panchal <amit@unoindia.co>
     * @version 0.0.1
     */
    public function getWelcomePackage(Request $request) {

        $this->validate($request, [
            'token' => 'required|integer',
            'customerId' => 'required|integer'
        ]);

        $token = $request->input('token');
        $customerId = $request->input('customerId');
        $scope = json_decode($request->header('Scope'), true);
        $dealerId = $scope['DealerId'];

        $dealer = DealerShips::where('dfx_id', '=', $dealerId)->first();
        if (is_null($dealer)) {
            return response()->json([
                        'status' => 'error',
                        'declaration' => 'user_not_found',
                        'payload' => ['message' => 'Sorry, dealer not found. Please try again.'],
                            ], 404);
        }

        $user = UserDealerShips::where(['user_dfx_id' => $customerId, 'dealership_id' => $dealer->id])->first();
        if (is_null($user)) {
            return response()->json([
                        'status' => 'error',
                        'declaration' => 'user_not_found',
                        'payload' => ['message' => 'Sorry, customer not found. Please try again.'],
                            ], 404);
        }
        // Params
        $params = [
            'headers' => [
                'Authorization' => $request->header('Authorization'),
                'Scope' => $request->header('Scope'),
            ],
            'json' => $token, // DFX Needs just ID in request.
        ];

        try {
            $response = $this->client->request('POST', "/document/welcomepackage", $params);
            $urlObj = json_decode($response->getBody()->getContents());

            $userDocumentArr = [
                'user_id' => $user->user_id,
                'title' => 'Welcome pdf',
                'type' => 'pdf',
                'url' => $urlObj->url
            ];
            $userDocument = UserDocument::firstOrCreate($userDocumentArr, $userDocumentArr);

            return response()->json([
                        'status' => 'success',
                        'declaration' => 'package_found',
                        'payload' => $urlObj,
                            ], 200);
        } catch (ConnectException $e) {
            $log_message = ErrorCodes::GATEWAY_TIMEOUT . ' : ' . $e->getMessage();
            throw new DfxException(ErrorCodes::GATEWAY_TIMEOUT, 'gateway_timeout', $log_message, 504);
        } catch (RequestException $e) {
            DfxException::dfxExceptionHandler($e);
        }
    }

    /**
     * Send welcome pdf
     * Makes POST request to '/welcomepdf/$customerToken/send'
     *
     * @return  void
     * @author  Amit Panchal <amit@unoindia.co>
     * @version 0.0.1
     */
    public function sendWelcomePdf(Request $request) {

        $this->validate($request, [
            'token' => 'required|integer',
        ]);

        $token = $request->input('token');
        $pdfUrl = (is_null($request->input('PreDeliveryCheckListUrl'))) ? "" : $request->input('PreDeliveryCheckListUrl');

        // Params
        $params = [
            'headers' => [
                'Authorization' => $request->header('Authorization'),
                'Scope' => $request->header('Scope'),
            ],
            'json' => [
                'S2SsessionId' => $token,
                'PreDeliveryCheckListUrl' => $pdfUrl
            ]
        ];

        try {
            $response = $this->client->request('POST', "/document/welcomepackageemail", $params);
            return response()->json([
                        'status' => 'success',
                        'declaration' => 'sent_welcome_pdf',
                        'payload' => json_decode($response->getBody()->getContents()),
                            ], 200);
        } catch (ConnectException $e) {
            $log_message = ErrorCodes::GATEWAY_TIMEOUT . ' : ' . $e->getMessage();
            throw new DfxException(ErrorCodes::GATEWAY_TIMEOUT, 'gateway_timeout', $log_message, 504);
        } catch (RequestException $e) {
            DfxException::dfxExceptionHandler($e);
        }
    }

    /**
     * Send Pre-Delivery Checklist
     * Makes POST request to '/predeliverychecklist/$customerToken/send'
     *
     * @return  void
     * @author  Jenish Panchal <jenishp@unoindia.co>
     * @version 0.0.1
     */
    public function sendPreDeliveryChecklist(Request $request) {

        $this->validate($request, [
            'to' => 'required|string',
            'attachment_url' => 'required|string',
        ]);

        //Scope
        $scope = json_decode($request->header('scope'), true);
        $dealerId = $scope['DealerId'];


        $pdfUrl = (is_null($request->input('attachment_url'))) ? "" : $request->input('attachment_url');
        $to = $request->input('to');
        $subject = "PreDeliveryChecklist Pdf File";
        $from = "source@dealer-fx.com";
        $body = "Hello $to, <pre>Here is your pre delivery checklist. <a href=\"$pdfUrl\" target=\"_blank\">Click Here</a> to open.</pre>";
        // Params
        $params = [
            'headers' => [
                'Authorization' => $request->header('Authorization'),
                'Scope' => $request->header('Scope'),
            ],
            'json' => [
                "from" => $from,
                'to' => $to,
                'subject' => $subject,
                'isBodyHtml' => true,
                'body' => $body,
                'attachmentUrl' => $pdfUrl,
            ]
        ];

        try {
            $response = $this->client->request('POST', "/document/email/predeliverychecklist", $params);


            /* They want static for Magic Toyota
             * Confirmed with Arun.
             * Comments by: Amandeep Singh
             */
            if($dealerId == 100265){
                $this->sendEmail($pdfUrl, $to = 'delv@magictoyota.com', $subject = "Forwarded Pre-Delivery Checklist", $body = "Please find attached Pre-Delivery checklist");
            }

            return response()->json([
                        'status' => 'success',
                        'declaration' => 'sent_pre_delivery_checklist',
                        'payload' => json_decode($response->getBody()->getContents()),
                            ], 200);
        } catch (ConnectException $e) {
            $log_message = ErrorCodes::GATEWAY_TIMEOUT . ' : ' . $e->getMessage();
            throw new DfxException(ErrorCodes::GATEWAY_TIMEOUT, 'gateway_timeout', $log_message, 504);
        } catch (RequestException $e) {
            DfxException::dfxExceptionHandler($e);
        }
    }

    /**
     * Send sms to customer
     * Makes POST request to 'customer/:customer_id/sendSms'
     *
     * @return  void
     * @author  Amit Panchal <amit@unoindia.co>
     * @version 0.0.1
     */
    public function sendSms(Request $request) {
        $this->validate($request, [
            'customerPhone' => 'required|integer',
            'customerId' => 'required|integer',
            'vin' => 'required|alpha_num|max:17',
            'is_enrolled' => 'required|boolean'
        ]);

        $customerId = $request->input('customerId');
        $isEnrolled = $request->input('is_enrolled');
        $vin = $request->input('vin');
        $scope = json_decode($request->header('Scope'), true);
        $dealer_id = $scope['DealerId'];

        $dealership = DealerShips::where('dfx_id', '=', $dealer_id)->first();
        if (is_null($dealership)) {
            return response()->json([
                        'status' => 'error',
                        'declaration' => 'dealership_not_found',
                        'payload' => ['message' => 'Sorry, dealer not found. Please try again.'],
                            ], 404);
        }

        //Here concatenate +1 as send require it and mobile team cant pass +1 as there is 10 digit validation on dfx side at time of post customer

        $customerPhone = '1' . $request->input('customerPhone');
        $userObj = UserDealerShips::where(['user_dfx_id' => $customerId, 'dealership_id' => $dealership->id])->orderBy('updated_at', 'DESC')->first();

        if (is_null($userObj)) {
            return response()->json([
                        'status' => 'error',
                        'declaration' => 'user_not_found',
                        'payload' => ['message' => 'Sorry, customer not found. Please try again.'],
                            ], 404);
        }

        User::where('id', $userObj->user_id)->update(['enrolled_in_ice_app' => $isEnrolled]);
        if (!$isEnrolled) {
            return response()->json([
                        'status' => 'success',
                        'declaration' => 'sms_not_sent',
                        'payload' => ['message' => 'Sorry, SMS is not sent as not enrolled for ICE app'],
                            ], 200); //code is 200 as this is not error
        }
        //Code should not be assigned to any other user
        do {
            $code = numericRand(6);
            $accessCodeObj = AccessCode::where('code', '=', $code)->first();
        } while (!is_null($accessCodeObj));

        //This is static message right now with dynamic 6 digit code and url is just dummy
        $customerMsg = "Welcome to " . ucfirst(strtolower($dealership->oem_name)) . ". Please download the " . ucfirst(strtolower($dealership->oem_name)) . "App here " . env('SMS_LINK') . " then use the 6 digit code $code";

        $access_code = [
            'user_id' => $userObj->user_id,
            'code' => $code,
            'status' => 'active'
        ];
        $userAccessCode = AccessCode::updateOrCreate(['user_id' => $userObj->user_id], $access_code);
        if ($userAccessCode) {

            // To add push notification for video
            $action_type = 'video';
            $notification = Notifications::where('action_type', '=', $action_type)->first();
            $vehicle = Vehicles::where('vin', '=', $vin)->first();


            $data = json_encode([
                "url" => $dealership->oem_video_url,
                "time_duration" => "1:31",
                "title" => "Intro to " . ucfirst(strtolower($dealership->oem_name)) . " Concierge"
            ]);
            $notificationArr = [
                'vehicle_id' => $vehicle->id,
                'user_id' => $userObj->user_id,
                'data' => $data,
                'status' => 'unread',
                'priority' => 1
            ];
            $notificationData = $notification->notificationDatas()->Create($notificationArr);
        }

        // Params
        $params = [
            'headers' => [
                'Authorization' => $request->header('Authorization'),
                'Scope' => $request->header('Scope'),
            ],
            'json' => [
                'customerPhone' => $customerPhone,
                'message' => $customerMsg,
            ],
        ];

        try {

            $response = $this->client->request('POST', "customer/$customerId/sendSms", $params);
            $decoded_response = json_decode($response->getBody()->getContents());

            // This is for Mobile team to ease identify 6 digit code as sms is not comming on indian number
            $decoded_response->code = $code;


            return response()->json([
                        'status' => 'success',
                        'declaration' => 'sent_sms',
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
     * get communication preferences
     * Makes POST request to '/customer/:customerId/communicationPreferences'
     *
     * @return  void
     * @author  Amit Panchal <amit@unoindia.co>
     * @version 0.0.1
     */
    public function communicationPreferences(Request $request) {
        $this->validate($request, [
            'customerId' => 'required|integer'
        ]);

        $customerId = $request->input('customerId');

        // Params
        $params = [
            'headers' => [
                'Authorization' => $request->header('Authorization'),
                'Scope' => $request->header('Scope'),
            ]
        ];

        try {
            $response = $this->client->request('GET', "/customer/$customerId/communicationPreferences", $params);
            $decoded_response = json_decode($response->getBody()->getContents(), true);

            // TO DO : Fetching user's channel and content from database
            $userAllPreferences = userDealerships::with(['users' => function($query) {
                            $query->with('UserContentChannels', 'UserContentChannels.CommContents', 'UserContentChannels.CommChannels');
                        }])->where('user_dfx_id', $customerId)->first();
            $allPreferences = array();
            $userPreferencesRes = array('customerPreference' => array());
            if (!is_null($userAllPreferences)) {
                $userPreferencesObj = $userAllPreferences->users;
                $userPreferences = $userPreferencesObj->toArray();
                $i = 0;
                foreach ($userPreferences['user_content_channels'] as $userContentChannelKey => $userContentChannelVal) {
                    $currentPreference = $userContentChannelVal['comm_contents']['name'];
                    $previousPreference = ($i > 0) ? $userPreferences['user_content_channels'][$i - 1]['comm_contents']['name'] : '';
                    if ($i == 0 || trim($currentPreference) != trim($previousPreference)) {
                        $allPreferences[$userContentChannelVal['comm_contents']['name']] = array(
                            'notificationPreference' => $userContentChannelVal['comm_contents']['name'],
                            $userContentChannelVal['comm_channels']['name'] => (boolean) $userContentChannelVal['status']
                        );
                    } else {
                        $allPreferences[$userContentChannelVal['comm_contents']['name']] = array_merge($allPreferences[$userContentChannelVal['comm_contents']['name']], array($userContentChannelVal['comm_channels']['name'] => (boolean) $userContentChannelVal['status']));
                    }
                    $i++;
                }
                // Convert to expected result                
                if (count($allPreferences) > 0) {
                    foreach ($allPreferences as $key => $val) {

                        if ($key == 'AppointmentReminderAndConfirmations') {
                            $userPreferencesRes['customerPreference'][] = ['notificationPreference' => "AppointmentReminderAndConfirmations",
                                'callFromDealership' => $decoded_response['customerPreferences'][0]['callFromDealership'],
                                'email' => $decoded_response['customerPreferences'][0]['email'],
                                'pushNotification' => $decoded_response['customerPreferences'][0]['pushNotification'],
                                'sms' => $decoded_response['customerPreferences'][0]['sms']
                            ];
                        } else {
                            $userPreferencesRes['customerPreference'][] = $val;
                        }
                    }
                }
            } else if (!is_null($decoded_response['customerPreferences'])) {
                $userPreferencesRes['customerPreference'][] = ['notificationPreference' => "AppointmentReminderAndConfirmations",
                    'callFromDealership' => $decoded_response['customerPreferences'][0]['callFromDealership'],
                    'email' => $decoded_response['customerPreferences'][0]['email'],
                    'pushNotification' => $decoded_response['customerPreferences'][0]['pushNotification'],
                    'sms' => $decoded_response['customerPreferences'][0]['sms']];
            }
            return response()->json([
                        'status' => 'success',
                        'declaration' => 'communication_preferences',
                        'payload' => $userPreferencesRes,
                            ], 200);
        } catch (ConnectException $e) {
            $log_message = ErrorCodes::GATEWAY_TIMEOUT . ' : ' . $e->getMessage();
            throw new DfxException(ErrorCodes::GATEWAY_TIMEOUT, 'gateway_timeout', $log_message, 504);
        } catch (RequestException $e) {
            DfxException::dfxExceptionHandler($e);
        }
    }

    /**
     * update communication preferences on dfx server
     * Makes POST request to '/customer/:customerId/communicationPreferences'
     *
     * @return  void
     * @author  Amit Panchal <amit@unoindia.co>
     * @version 0.0.1
     */
    public function updateCommunicationPreferences(Request $request) {
        $this->validate($request, [
            'customerId' => 'required|integer',
            'customerPreference' => 'required|array'
        ]);

        $customerId = $request->input('customerId');
        $input_request['customerPreferences'] = $request->input('customerPreference');

        // Params
        $params = [
            'headers' => [
                'Authorization' => $request->header('Authorization'),
                'Scope' => $request->header('Scope'),
            ],
            'json' => $input_request
        ];
        try {
            //$response = $this->client->request('PUT', "/customer/$customerId/communicationPreferences", $params);
            //$decoded_response = json_decode($response->getBody()->getContents());
            // To Do : Update to our database too
            
            //Temporary success message
            $decoded_response["message"] = "OK";
            return response()->json([
                        'status' => 'success',
                        'declaration' => 'communication_preferences',
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
     * To schedule appointment
     * Makes POST request to '/schedule/appointment/1/25/firstAppointment'
     *
     * @return  void
     * @author  Amit Panchal <amit@unoindia.co>
     * @version 0.0.1
     */
    public function getFirstAppointment(Request $request, $appointmentType, $token) {
        // Params
        $params = [
            'headers' => [
                'Authorization' => $request->header('Authorization'),
                'Scope' => $request->header('Scope'),
            ]
        ];
        try {
            $response = $this->client->request('GET', "/schedule/appointment/$appointmentType/$token/firstAppointment", $params);
            $json_response = json_decode($response->getBody()->getContents());

            $dfxDateTime = $json_response->appointmentDateTimeStr;
            $json_response->appointmentDateTimeStr = Carbon::parse($dfxDateTime)->format('m-d-Y H:i:s');
            $json_response->dfxAppointmentDateTimeStr = $dfxDateTime;

            return response()->json([
                        'status' => 'success',
                        'declaration' => 'get_first_appointment',
                        'payload' => $json_response,
                            ], 200);
        } catch (ConnectException $e) {
            $log_message = ErrorCodes::GATEWAY_TIMEOUT . ' : ' . $e->getMessage();
            throw new DfxException(ErrorCodes::GATEWAY_TIMEOUT, 'gateway_timeout', $log_message, 504);
        } catch (RequestException $e) {
            DfxException::dfxExceptionHandler($e);
        }
    }

    /**
     * To schedule appointment time slots
     * Makes POST request to '/schedule/appointment/:appointmentType/timeslots/:date'
     *
     * @return  void
     * @author  Amit Panchal <amit@unoindia.co>
     * @version 0.0.1
     */
    public function scheduleAppointmentTimeSlots(Request $request) {
        $this->validate($request, [
            'appointment_type' => 'required|integer',
            'appointment_date' => 'required|date_format:"Y-m-d"'
        ]);

        $appointmentType = $request->input('appointment_type');
        $appointmentDate = $request->input('appointment_date');


        // Params
        $params = [
            'headers' => [
                'Authorization' => $request->header('Authorization'),
                'Scope' => $request->header('Scope'),
            ]
        ];
        try {
            $advisor = 0;
            $url = "/schedule/appointment/$appointmentType/timeslots/$appointmentDate";
            if (!is_null($request->input('advisor'))) {
                $advisor = $request->input('advisor');
                $url .= "/$advisor";
            }
            $response = $this->client->request('GET', $url, $params);
            $decoded_response = json_decode($response->getBody()->getContents(), true);



            $timeSlotsArr = array();
            if (count($decoded_response['timeSlots']) > 0) {
                foreach ($decoded_response['timeSlots'] as $key => $val) {
                    $dfxDateTime = $val['timeStr'];
                    $val['timeStr'] = Carbon::parse($dfxDateTime)->format('m-d-Y H:i:s');
                    $val['dfxTimeStr'] = $dfxDateTime;
                    $timeSlotsArr['timeSlots'][] = $val;
                }
            }
            $transportation = '';
            if (!is_null($request->input('transportationOption'))) {
                $transportation = trim($request->input('transportationOption'));
            }
            if ($advisor > 0) {
                $timeSlots = array();
                foreach ($timeSlotsArr['timeSlots'] as $key => $val) {
                    if (in_array($advisor, $val['advisors'])) {
                        $timeSlots[] = $timeSlotsArr['timeSlots'][$key];
                    }
                }
                $timeSlotsArr['timeSlots'] = $timeSlots;
            }

            if ($transportation != '') {
                $timeSlots = array();
                foreach ($timeSlotsArr['timeSlots'] as $key => $val) {
                    if (in_array($transportation, $val['transpotationOptions'])) {
                        $timeSlots[] = $timeSlotsArr['timeSlots'][$key];
                    }
                }
                $timeSlotsArr['timeSlots'] = $timeSlots;
            }
            return response()->json([
                        'status' => 'success',
                        'declaration' => 'get_appointment_slots',
                        'payload' => $timeSlotsArr,
                            ], 200);
        } catch (ConnectException $e) {
            $log_message = ErrorCodes::GATEWAY_TIMEOUT . ' : ' . $e->getMessage();
            throw new DfxException(ErrorCodes::GATEWAY_TIMEOUT, 'gateway_timeout', $log_message, 504);
        } catch (RequestException $e) {
            DfxException::dfxExceptionHandler($e);
        }
    }

    /**
     * To get Advisor
     * Makes POST request to '/schedule/appointment/:appointmentType/advisors'
     *
     * @return  void
     * @author  Amit Panchal <amit@unoindia.co>
     * @version 0.0.1
     */
    public function getAdvisors(Request $request, $appointmentType) {

        // Params
        $params = [
            'headers' => [
                'Authorization' => $request->header('Authorization'),
                'Scope' => $request->header('Scope'),
            ]
        ];
        try {
            $response = $this->client->request('GET', "/schedule/appointment/$appointmentType/advisors", $params);
            $decoded_response = json_decode($response->getBody()->getContents());

            return response()->json([
                        'status' => 'success',
                        'declaration' => 'get_advisors',
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
     * get Transportation Option
     * Makes POST request to '/schedule/transportationOptions/:date'
     *
     * @return  void
     * @author  Amit Panchal <amit@unoindia.co>
     * @version 0.0.1
     */
    public function scheduleTransportationOption(Request $request) {
        $this->validate($request, [
            'transportation_date' => 'required|date_format:"Y-m-d"'
        ]);

        $transportaionDate = $request->input('transportation_date');

        // Params
        $params = [
            'headers' => [
                'Authorization' => $request->header('Authorization'),
                'Scope' => $request->header('Scope'),
            ]
        ];
        try {

            $response = $this->client->request('GET', "/schedule/transportationOptions/$transportaionDate", $params);
            $decoded_response = json_decode($response->getBody()->getContents(), true);
            $transportation = [];
            foreach ($decoded_response['transportationOptions'] as $key => $val) {
                $val['logo'] = str_replace("http://","https://" , $request->root()) . '/images/transportation_option/' . $val['code'] . '.png';
                $val['active_logo'] = str_replace("http://","https://" , $request->root()) . '/images/transportation_option/' . 'white-' . $val['code'] . '.png';
                $transportation['transportationOptions'][] = $val;
            }

            return response()->json([
                        'status' => 'success',
                        'declaration' => 'get_transportation',
                        'payload' => $transportation,
                            ], 200);
        } catch (ConnectException $e) {
            $log_message = ErrorCodes::GATEWAY_TIMEOUT . ' : ' . $e->getMessage();
            throw new DfxException(ErrorCodes::GATEWAY_TIMEOUT, 'gateway_timeout', $log_message, 504);
        } catch (RequestException $e) {
            DfxException::dfxExceptionHandler($e);
        }
    }

    /**
     * Search customer
     * Makes POST request to '/customer/CustomerSearch?searchtext={{customer_search_params}}'
     *
     * @return  void
     * @author  Amit Panchal <amit@unoindia.co>
     * @version 0.0.1
     */
    public function searchCustomer(Request $request) {
        $this->validate($request, [
            'searchtext' => 'required'
        ]);

        $searchCriteria = $request->input('searchtext');

        // Params
        $params = [
            'headers' => [
                'Authorization' => $request->header('Authorization'),
                'Scope' => $request->header('Scope'),
            ]
        ];
        try {
            $response = $this->client->request('GET', "/customer/CustomerSearch?searchtext=$searchCriteria", $params);
            $decoded_response = json_decode($response->getBody()->getContents());
            if (count($decoded_response->customers) <= 0) {
                return response()->json([
                            'status' => 'error',
                            'declaration' => 'customer_not_found',
                            'payload' => ['search_count' => count($decoded_response->customers), 'message' => "Sorry no search results were found based on your input.  Please click on 'I am a new customer' button to create a new customer profile."],
                                ], 404);
            }

            if (count($decoded_response->customers) > 10) {
                $search = array_slice($decoded_response->customers, 0, 10);
                return response()->json([
                            'status' => 'success',
                            'declaration' => 'customer_found',
                            'payload' => [
                                'search_count' => count($decoded_response->customers),
                                'message' => 'We can not display all the results because the search criteria is too broad.  Please try refining your search',
                                'customers' => $search
                            ],
                                ], 200);
            }

            return response()->json([
                        'status' => 'success',
                        'declaration' => 'customer_found',
                        'payload' => ['search_count' => count($decoded_response->customers), 'customers' => $decoded_response->customers],
                            ], 200);
        } catch (ConnectException $e) {
            $log_message = ErrorCodes::GATEWAY_TIMEOUT . ' : ' . $e->getMessage();
            throw new DfxException(ErrorCodes::GATEWAY_TIMEOUT, 'gateway_timeout', $log_message, 504);
        } catch (RequestException $e) {
            DfxException::dfxExceptionHandler($e);
        }
    }

    /**
     * Book appointment for user on selected date and time 
     * Makes POST request to '/schedule/appointment/:appointmentType/:sale2ServicesessionId/bookSalesToService'
     *
     * @return  void
     * @author  Amit Panchal <amit@unoindia.co>
     * @version 0.0.1
     */
    public function bookAppointment(Request $request) {
        // Input validations
        $this->validate($request, [
            'customerId' => 'required|integer',
            'appointment_type' => 'required|integer',
            'token' => 'required|integer',
            'appointmentDateTimeStr' => 'required',
            'mileage' => 'required|integer',
            'vin' => 'required|alpha_num|max:17'
        ]);

        //Scope
        $scope = json_decode($request->header('scope'), true);

        $dealer_id = $scope['DealerId'];
        $appointmentType = $request->input('appointment_type');
        $token = $request->input('token');
        $dfxId = $request->input('customerId');
        $vin = $request->input('vin');
        $transportation_options = $request->input('transportationOption');
        $advisor_id = $request->input('advisor_id');

        // Scope Headers
        $scope_header = json_encode([
            'DealerId' => $dealer_id,
            'CultureCode' => $scope['CultureCode'],
            'Environment' => config('dfx.environment'),
            'DfxUserId' => $dfxId
        ]);

        $inputParam = [
            "appointmentDateTimeStr" => $request->input('appointmentDateTimeStr'),
            "mileage" => $request->input('mileage'),
            "transportationOption" => ' ',
        ];

        if(!is_null($transportation_options) && !empty($transportation_options)) {
            $inputParam['transportationOption'] = $transportation_options;
        }

        if(!is_null($advisor_id)) {
            $inputParam['advisor_id'] = $advisor_id;
        }

        // Params
        $params = [
            'headers' => [
                'Authorization' => $request->header('Authorization'),
                'Scope' => $scope_header,
            ],
            'json' => $inputParam,
        ];


        try {

            $response = $this->client->request('POST', "/schedule/appointment/$appointmentType/$token/bookSalesToServiceAppointment", $params);
            $decoded_response = json_decode($response->getBody()->getContents());

            if (!is_null($decoded_response) && $decoded_response->appointment->appointmentId > 0) {
                // To add push notification for Service Remainder
                $action_type = 'reminder';
                $appointmentId = $decoded_response->appointment->appointmentId;
                $notification = Notifications::where('action_type', '=', $action_type)->first();
                $vehicle = Vehicles::where('vin', '=', $vin)->first();
                $userDealer = UserDealerShips::whereHas(
                                'dealerships', function ($query) use ($dfxId, $dealer_id) {
                            $query->where(['dfx_id' => $dealer_id]);
                        }
                        )->where(['user_dfx_id' => $dfxId])->first();
                $data = json_encode([
                    "title" => "Service Remainder",
                    'Body' => [
                        "appointment_id" => $appointmentId,
                        "appointment_date" => Carbon::parse($request->input('appointmentDateTimeStr'))->format('d-m-Y H:i')
                    ]
                ]);
                $notificationArr = [
                    'vehicle_id' => $vehicle->id,
                    'user_id' => $userDealer->user_id,
                    'data' => $data,
                    'status' => 'unread',
                    'priority' => 1
                ];
                $notificationData = $notification->notificationDatas()->Create($notificationArr);
            }

            return response()->json([
                        'status' => 'success',
                        'declaration' => 'appointment_booked',
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
     * send password on mail for forgot password
     * Makes POST request to '/framework/sendForgotPasswordEmail'
     *
     * @return  void
     * @author  Amit Panchal <amit@unoindia.co>
     * @author  Jenish Panchal <jenishp@unoindia.co>
     * @version 0.0.1
     */
    public function forgotPassword(Request $request) {
        // Input validations
        $this->validate($request, [
            'username' => 'required|string'
        ]);

        $userName = $request->input('username');

        //Scope                        
        $scope = json_decode($request->header('scope'), true);
        
        // Scope Headers        
        $scope_header = json_encode([
            'Environment' => config('dfx.environment'), 
            //As per communicate with @aman right now set it static because Mobile team not able to pass it 
            'CultureCode' => 'en-US'
        ]);
        
        // Params
        $params = [
            'headers' => [
                'Authorization' => $request->header('Authorization'),
                'Scope' => $scope_header
            ],
            'json' => $userName,
        ];

        
        try {

            $response = $this->client->request('POST', "framework/sendForgotPasswordEmail", $params);            
            $decoded_response = json_decode($response->getBody()->getContents());
            
            return response()->json([
                        'status' => 'success',
                        'declaration' => 'sent_email',
                        'payload' => $decoded_response,
                            ], 200);
        } catch (ConnectException $e) {
            $log_message = ErrorCodes::GATEWAY_TIMEOUT . ' : ' . $e->getMessage();
            throw new DfxException(ErrorCodes::GATEWAY_TIMEOUT, 'gateway_timeout', $log_message, 504);
        } catch (RequestException $e) {
            $log_message = 'User not found';
            throw new DfxException(ErrorCodes::UNOAPP_CUSTOM_LOG, 'invalid_request', $log_message, 400);
        }
    }

    /**
     * get list of Question     
     *
     * @return  void
     * @author  Amit Panchal <amit@unoindia.co>
     * @version 0.0.1
     */
    public function getQuestion(Request $request) {

        $this->validate($request, [
            'manufacturerId' => 'required|integer',
            'dealer_id' => 'required|integer',
            'make' => 'required',
            'year' => 'required',
            'model' => 'required'
        ]);

        $manufacturerId = $request->input('manufacturerId');
        $dealerId = $request->input('dealer_id');
        $make = $request->input('make');
        $year = $request->input('year');
        $model = $request->input('model');

        $dealership = DealerShips::where('dfx_id', '=', $dealerId)->first();
        if (is_null($dealership)) {
            return response()->json([
                        'status' => 'error',
                        'declaration' => 'dealership_not_found',
                        'payload' => ['message' => 'Sorry, dealer not found. Please try again.'],
                            ], 404);
        }

        $manufacturer = Manufacturers::where('dfx_id', '=', $manufacturerId)->first();
        if (is_null($manufacturer)) {
            return response()->json([
                        'status' => 'error',
                        'declaration' => 'manufacturer_not_found',
                        'payload' => ['message' => 'Sorry, Manufacturer not found. Please try again.'],
                            ], 404);
        }

        try {
            $result = Questions::whereHas(
                            'manufacturers', function ($query) use ($manufacturerId, $dealership) {
                        $query->where(['dfx_id' => $manufacturerId, 'dealership_id' => $dealership->id]);
                    }
                    )->with('questionOptions')->get();
            $questionArr = json_decode($result, true);
            if (count($questionArr) > 0) {
                $i = 0;
                foreach ($questionArr as $key => $val) {
                    preg_match_all('/{{(.*?)}}/', $val['question_text'], $matches);
                    if (count($matches) > 0) {
                        $replace = array("{{make}}" => ucfirst(strtolower($make)), "{{year}}" => $year, "{{model}}" => $model);
                        $questionArr[$i]['question_text'] = strtr($val['question_text'], $replace);
                    }
                    $i++;
                }
            }
            $question['question'] = $questionArr;
            $question['owner_term'] = 'https://www.dealer-fx.com/privacy-policy/';
            $question['representative_term'] = 'https://www.dealer-fx.com/privacy-policy/';

            return response()->json([
                        'status' => 'success',
                        'declaration' => 'questions_found',
                        'payload' => $question,
                            ], 200);
        } catch (ConnectException $e) {
            $log_message = ErrorCodes::GATEWAY_TIMEOUT . ' : ' . $e->getMessage();
            throw new DfxException(ErrorCodes::GATEWAY_TIMEOUT, 'gateway_timeout', $log_message, 504);
        } catch (RequestException $e) {
            DfxException::dfxExceptionHandler($e);
        }
    }

    /**
     * Save Question Response
     *
     * @return  void
     * @author  Amit Panchal <amit@unoindia.co>
     * @version 0.0.1
     */
    public function saveQuestionResponse(Request $request) {

        $this->validate($request, [
            'customerId' => 'required|integer',
            'manufacturerId' => 'required|integer',
            'dealer_title' => 'required',
            'dealer_logo' => 'required',
            'customer_vehicle_info' => 'required|array',
            'questions_info' => 'required|array',
            'signature' => 'required|array'
        ]);
        $customerId = $request->input('customerId');
        $questionResponse = $request->input('questions_info')['question'];
        $manufacturerId = $request->input("manufacturerId");
        $signature = $request->input("signature");

        try {
            //Here only user id is require so not fetched user object
            $user = UserDealerShips::where('user_dfx_id', '=', $customerId)->first();
            if (is_null($user)) {
                return response()->json([
                            'status' => 'error',
                            'declaration' => 'user_not_found',
                            'payload' => ['message' => 'Sorry, customer not found. Please try again.'],
                                ], 404);
            }

            //Verifying User
            $manufacturer = Manufacturers::where('dfx_id', '=', $manufacturerId)->first();
            if (is_null($manufacturer)) {
                return response()->json([
                            'status' => 'error',
                            'declaration' => 'manufacturer_not_found',
                            'payload' => ['message' => 'Sorry, Manufacturer not found. Please try again.'],
                                ], 404);
            }

            //Looking for mask update or create            
            if (count($questionResponse) > 0) {
                foreach ($questionResponse as $key => $val) {
                    if (count($val['option']) > 0) {

                        foreach ($val['option'] as $keyOption => $valOption) {
                            $question_response = [
                                'user_id' => $user->user_id,
                                'question_id' => $val['question_id'],
                                'question_option_id' => $valOption['question_option_id'],
                                'response' => (boolean) $valOption['reply'],
                            ];
                            $questionResponse = QuestionsResponse::updateOrCreate(['user_id' => $user->user_id, 'question_id' => $val['question_id'], 'question_option_id' => $valOption['question_option_id']], $question_response);
                        }
                    } else {
                        $question_response = [
                            'user_id' => $user->user_id,
                            'question_id' => $val['question_id'],
                            'question_option_id' => NULL,
                            'response' => (boolean) $val['reply'],
                        ];
                        $questionResponse = QuestionsResponse::updateOrCreate(['user_id' => $user->user_id, 'question_id' => $val['question_id'], 'question_option_id' => NULL], $question_response);
                    }
                }
            }

            $uid = $user->user_id;
            $owner_sign = $signature['owner'];
            $representative_sign = $signature['representative'];
            $owner_sign_fname = '';
            $folderName = '/images/sign/';
            $destinationPath = public_path() . $folderName;

            if (!empty($owner_sign)) {
                $file = base64_decode($owner_sign);
                $owner_sign_fname = $uid . '_' . time() . '_' . $customerId . '_owner.png';
                file_put_contents($destinationPath . $owner_sign_fname, $file);
            }

            if (!empty($representative_sign)) {
                $file2 = base64_decode($representative_sign);
                $representative_sign_fname = $uid . '_' . time() . '_' . $customerId . '_representative.png';
                file_put_contents($destinationPath . $representative_sign_fname, $file2);
            }

            // Need to check old signature
            $PreDelivery = PreDelivery::where([['user_id', '=', $user->user_id], ['manufacturer_id', '=', $manufacturer->id]])->get()->first();
            if ($PreDelivery) {
                // If old signature exists need to unlink
                $old_mg1 = $destinationPath . $PreDelivery->owner_sign;
                $old_mg2 = $destinationPath . $PreDelivery->representative_sign;
                if (file_exists($old_mg1)) {
                    unlink($old_mg1);
                }
                if (file_exists($old_mg2)) {
                    unlink($old_mg2);
                }
                $PreDelivery->id = $PreDelivery->id;
                $PreDelivery->owner_sign = $owner_sign_fname;
                $PreDelivery->representative_sign = $representative_sign_fname;
                $PreDelivery->save();
            } else {
                PreDelivery::updateOrCreate([
                    'user_id' => $user->user_id,
                    'manufacturer_id' => $manufacturer->id,
                    'owner_sign' => $owner_sign_fname,
                    'representative_sign' => $representative_sign_fname
                ]);
            }

            //////////////// START PDF CODE /////////////////////////////////////////////
            $html = $this->generateHTML($request);
            // $html = str_replace("{{ROOT_URL}}", $request->root(), $html);
            // $pdfFolderName = '/assets/pdf/pdf_' . $customerId . '_' . $manufacturerId . '.pdf';
            // $destinationPath = public_path() . $pdfFolderName;
            // if (file_exists($destinationPath)) {
            //     unlink($destinationPath);
            // }
            // generatePdf($html, $destinationPath);
            //////////////// END PDF CODE /////////////////////////////////////////////


            $file_name = $customerId;
            $url = saveHtmlFile($html, $file_name);

            $userDocumentArr = [
                'user_id' => $user->user_id,
                'title' => 'Pre delivery checklist',
                'type' => 'pdf',
                'url' => $url
            ];
            $userDocument = UserDocument::firstOrCreate($userDocumentArr, $userDocumentArr);

            return response()->json([
                        'status' => 'success',
                        'declaration' => 'questions_response_updated',
                        'payload' => ['pdf_link' => $url]
            ], 200);
        } catch (ConnectException $e) {
            $log_message = ErrorCodes::GATEWAY_TIMEOUT . ' : ' . $e->getMessage();
            throw new DfxException(ErrorCodes::GATEWAY_TIMEOUT, 'gateway_timeout', $log_message, 504);
        } catch (RequestException $e) {
            DfxException::dfxExceptionHandler($e);
        }
    }

    /**
     * To Preview question response
     * 
     * @return  void
     * @author  Amit Panchal <amit@unoindia.co>
     * @version 0.0.1
     */
    public function previewQuestionResponse(Request $request) {
        $this->validate($request, [
            'customerId' => 'required|integer',
            'manufacturerId' => 'required|integer',
            'dealer_title' => 'required',
            'dealer_logo' => 'required',
            'customer_vehicle_info' => 'required|array',
            'questions_info' => 'required|array',
            'signature' => 'required|array'
        ]);
        $inputParam = $request->all();
        try {

            $html = $this->generateHTML($request);

            $file_name = $inputParam['customerId'];
            $url = saveHtmlFile($html, $file_name);

            return response()->json([
                        'status' => 'success',
                        'declaration' => 'questions_response_previewed',
                        // 'pdf_link' => $request->root() . $pdfFolderName,
                        'pdf_link' => $url,
                        'payload' => true,
            ], 200);
        } catch (ConnectException $e) {
            $log_message = ErrorCodes::GATEWAY_TIMEOUT . ' : ' . $e->getMessage();
            throw new DfxException(ErrorCodes::GATEWAY_TIMEOUT, 'gateway_timeout', $log_message, 504);
        } catch (RequestException $e) {
            DfxException::dfxExceptionHandler($e);
        }
    }

    /**
     * To Build up HTML for genrate pdf file
     *
     * @return  html string
     * @author  Jenish Panchal <jenishp@unoindia.co>
     * @version 0.0.1
     */
    public function generateHTML($request) {
        $data = $request->all();
        $dealerName = $data['dealer_title'];
        $dealerLogo = $data['dealer_logo'];
        $customerVehicleInfo = $data['customer_vehicle_info'];
        $questionInfo = $data['questions_info'];
        $signatureInfo = $data['signature'];

        $html = '<html>';
        $html .= '<head>
                        <style>
                            .main-container{
                                margin: 0 auto;
                                width: 100%;
                                max-width: 990px;
                            }
                            table {
                                    font-family: Helvetica;
                                    border-collapse: collapse;
                                    border-spacing: 0;
                                    width: 100%;
                            }

                            .clearfix {
                                clear: both;
                                display: block;
                            }

                            .center-block {
                                margin-right: auto;
                                margin-left: auto;
                                display: block;
                            }
                            .text-center {
                                text-align: center;
                            }

                            .main_heder td {
                                    padding: 0 10px;
                            }
                            .main_heder label {
                                    font-size: 26px;
                                    font-weight: 700;
                                    text-transform: uppercase;
                                vertical-align: top;
                            }
                            .form_style tr td {
                                    padding: 0 10px;
                            }
                            .form_style tr td label {
                                    font-size: 15px;
                                    text-align: left;
                                    margin-top: 20px;
                                    display: block;
                                    font-weight: 700;
                            }
                            .form_style tr td input {
                                    height: 24px;
                                    width: 100%;
                                    vertical-align:middle;
                                    display: block;
                            }
                            .service_wrapper_1 {
                                    margin-top: 20px;
                            }
                            .service_wrapper_1 tr td p {
                                    font-size: 15px;
                                    padding-top:10px;
                                    font-weight: 700;
                                    margin: 0;
                            }

                            .service_wrapper_2 tr td {
                                    font-size: 15px;
                                    padding-top: 20px;
                                    font-weight: 700;
                                    padding-right: 60px;
                            }
                            .service_wrapper_3 tr td,
                            .service_wrapper_4 tr td,
                            .service_wrapper_5 tr td,
                             .service_wrapper_6 tr td {
                                    font-size: 15px;
                                    padding-top: 20px;
                                    font-weight: 700;
                                    padding-right: 60px;
                                    vertical-align: top;
                                width: 33.3333%;
                            }
                             .service_wrapper_6 tr.sign td {
                                    font-weight: normal;
                                    padding-right: 60px;
                                    vertical-align: top;
                            }
                             .service_wrapper_6 label {
                                    margin-bottom: 10px;
                                    display: block;
                             }
                            .list {
                                    list-style: none;
                                margin: 0;
                                padding: 0;

                            }
                            .list li {
                                position: relative;
                                padding-right: 40px;
                                margin-bottom: 15px;
                                line-height: 20px;
                            }
                            .list input {
                                        margin-left: 30px;
                                        vertical-align: middle;
                                        float: right;
                                        position: absolute;
                                        right: 0;
                                        top: 0;
                            }
                            .list label {
                            vertical-align: top;
                                display: inline-block;
                            }
                        </style>
                    </head>';

        $html.='<body>
                    <div class="main-container">                    
                            <table>
                                    <tr class="main_heder">
                                      <td><img src="' . $dealerLogo . '" alt="" width="50px; height="35px;/>&nbsp;&nbsp;                                      
                                      <label>' . $dealerName . '</label></td> 
                                    </tr>
                            </table>
                            <table class="form_style">
                            <tr>
				<td>
					<label>First Name</label>
					<input type="text" value="' . $customerVehicleInfo['first_name'] . '"/>
				</td>
				<td>
					<label>Last Name</label>
					<input type="text" value="' . $customerVehicleInfo['last_name'] . '"/>
				</td>
				<td>
					<label>Email Address</label>
					<input type="text" value="' . $customerVehicleInfo['email'] . '"/>
				</td>
				<td>
					<label>Phone Number</label>
					<input type="text" value="' . $customerVehicleInfo['phone_number'] . '"/>
				</td>
                            </tr>
                            <tr>
				<td>
					<label>Model</label>
					<input type="text" value="' . $customerVehicleInfo['model'] . '"/>
				</td>
				<td>
					<label>Color</label>
					<input type="text" value="' . $customerVehicleInfo['color'] . '"/>
				</td>
				<td>
					<label>Stock Number</label>
					<input type="text" value="' . $customerVehicleInfo['stock_number'] . '"/>
				</td>
				<td>
					<label>VIN</label>
					<input type="text" value="' . $customerVehicleInfo['vin'] . '" />
				</td>
                            </tr>
                            <tr>
                                <td>
                                        <label>Delivery Date</label>
                                        <input type="text" value="' . $customerVehicleInfo['delivery_date'] . '"/>
                                </td>
                                <td colspan="2">
                                        <label>Sales Person</label>
                                        <input type="text" value="' . $customerVehicleInfo['sales_person'] . '" />
                                </td>
                            </tr>
                            <tr>
                                <td colspan="3">
                                        <label>Notes</label>
                                        <input type="text" value="' . $customerVehicleInfo['notes'] . '"/>
                                </td>
                            </tr>
                        </table>';
        $html .= '<table class="service_wrapper_1">
			<tr>
				<td>
					<p>' . $questionInfo['tag_head'] . '</p>
				</td>
			</tr>
			<tr>
				<td>
					<p>' . $questionInfo['tag_line'] . '</p>
				</td>
			</tr>			
		</table>';
        if (count($questionInfo['question']) > 0) {
            $i = 1;
            foreach ($questionInfo['question'] as $key => $val) {
                $html .= '<table class="service_wrapper_2">';
                $html .= '<tr><td >' . $i . '. &nbsp;' . $val['text'] . '</td></tr>';
                $html .= '<tr>';
                if (count($val['option']) > 0) {
                    $totalOption = count($val['option']);
                    $noOfTDPerTr = 3;
                    $noOfColumnPerTd = 3;
                    $totalTd = ceil($totalOption / $noOfTDPerTr);
                    $firstOption = true;
                    $j = 1;
                    foreach ($val['option'] as $optionKey => $optionVal) {
                        $checked = ($optionVal['reply'] == 'true') ? 'checked' : '';
                        if ($firstOption || $noOfColumnPerTd == 3) {
                            $html .= '<td>';
                            $html .='<ul class="list">';
                        }
                        $html .='<li>' . $optionVal['text'] . ' <input id="checkBox" type="checkbox" ' . $checked . '> </li>';
                        $noOfColumnPerTd--;
                        if ($noOfColumnPerTd <= 0 || $totalOption == $j) {
                            $html .= '</ul></td>';
                            $noOfColumnPerTd = 3;
                        }
                        $firstOption = false;
                        $j++;
                    }
                } else {
                    $reply = strtolower(trim($val['reply']));
                    $yesChecked = ($reply == 'true') ? 'checked' : '';
                    $noChecked = ($reply == 'false') ? 'checked' : '';
                    $html .= '<td>';
                    $html .= 'Yes <input id="checkBox" type="checkbox" ' . $yesChecked . '>';
                    $html .= '&nbsp;&nbsp;&nbsp;&nbsp;';
                    $html .= 'No<input id="checkBox" type="checkbox" ' . $noChecked . '>';
                    $html .= '</td>';
                }
                $html .= '</tr>';
                $html .= '</table>';

                $i++;
            }
        }
        /* As image portion is taking too much time to execute pdf temporary comment that code */
        if (!empty($signatureInfo['owner'])) {
            $ownerSign = base64_decode($signatureInfo['owner']);
            $destinationPath = public_path() . '/images/sign/owner.png';
            file_put_contents($destinationPath, $ownerSign);
        }

        if (!empty($signatureInfo['representative'])) {
            $representativeSign = base64_decode($signatureInfo['representative']);
            $destinationPath = public_path() . '/images/sign/representative.png';
            file_put_contents($destinationPath, $representativeSign);
        }

        $html .= '<table class="service_wrapper_6">
                                <tr class="sign">
                                    <td>
                                    <label><b> Signature</b></label>
                                    <label style="height: 50px;width: 200px;position: relative;"><img src="' . public_path() . '/images/sign/owner.png" style="max-width: 100%"></label>
				<label>owner</label>
				<label style="height: 50px;width: 200px;position: relative;"><img src="' . public_path() . '/images/sign/representative.png" style="max-width: 100%"></label>
				<label>delivery Specialist/Represenstive</label>
				<label>' . $signatureInfo['date'] . '</label>
				<label>date</label>
				</td>
			</tr>
            </table>';
        $html .= '</div>
	</body></html>';
        return $html;
    }

    /**
     * User will enrolled in ICE app
     *
     * @return  void
     * @author  Amit Panchal <amit@unoindia.co>
     * @version 0.0.1
     */
    public function toggleEnrolledIce(Request $request) {
        $this->validate($request, [
            'customerId' => 'required|integer',
            'is_enrolled' => 'required|boolean'
        ]);

        $customerId = $request->input('customerId');
        $isEnrolled = $request->input('is_enrolled');

        try {

            $user = User::where('dfx_id', '=', $customerId)->first();
            if (is_null($user)) {
                return response()->json([
                            'status' => 'error',
                            'declaration' => 'user_not_found',
                            'payload' => ['Sorry, customer not found. Please try again.'],
                                ], 404);
            }
            User::where('dfx_id', $customerId)->update(['enrolled_in_ice_app' => $isEnrolled]);
            return response()->json([
                        'status' => 'success',
                        'declaration' => 'enrolledInIceApp_updated',
                        'payload' => $isEnrolled,
                            ], 200);
        } catch (ConnectException $e) {
            $log_message = ErrorCodes::GATEWAY_TIMEOUT . ' : ' . $e->getMessage();
            throw new DfxException(ErrorCodes::GATEWAY_TIMEOUT, 'gateway_timeout', $log_message, 504);
        } catch (RequestException $e) {
            DfxException::dfxExceptionHandler($e);
        }
    }

    /**
     * Get Country
     *
     * @return  void
     * @author  Amit Panchal <amit@unoindia.co>
     * @version 0.0.1
     */
    public function getAllCountry(Request $request) {


        $scope = json_decode($request->header('scope'), true);
        $dealer_id = $scope['DealerId'];

        // User Login, should create dealership in system.
        $dealership = DealerShips::where('dfx_id', '=', $dealer_id)->first();
        if (is_null($dealership)) {
            return response()->json([
                        'status' => 'error',
                        'declaration' => 'dealership_not_found',
                        'payload' => ['message' => 'Sorry, dealer not found. Please try again.'],
                            ], 404);
        }

        $params = [
            'headers' => [
                'Authorization' => $request->header('Authorization'),
                'Scope' => $request->header('Scope'),
            ]
        ];

        try {
            $response = $this->client->request('GET', "/geo/country", $params);
            $country = json_decode($response->getBody()->getContents());
            return response()->json([
                        'status' => 'success',
                        'declaration' => 'country_found',
                        'payload' => $country,
                            ], 200);
        } catch (ConnectException $e) {
            $log_message = ErrorCodes::GATEWAY_TIMEOUT . ' : ' . $e->getMessage();
            throw new DfxException(ErrorCodes::GATEWAY_TIMEOUT, 'gateway_timeout', $log_message, 504);
        } catch (RequestException $e) {
            DfxException::dfxExceptionHandler($e);
        }
    }

    /**
     * Get country's province 
     *
     * @return  void
     * @author  Amit Panchal <amit@unoindia.co>
     * @version 0.0.1
     */
    public function getProvince(Request $request) {

        $this->validate($request, [
            'countryId' => 'required',
        ]);

        $countryId = $request->input('countryId');
        $scope = json_decode($request->header('scope'), true);
        $dealer_id = $scope['DealerId'];

        // User Login, should create dealership in system.
        $dealership = DealerShips::where('dfx_id', '=', $dealer_id)->first();
        if (is_null($dealership)) {
            return response()->json([
                        'status' => 'error',
                        'declaration' => 'dealership_not_found',
                        'payload' => ['message' => 'Sorry, dealer not found. Please try again.'],
                            ], 404);
        }

        $params = [
            'headers' => [
                'Authorization' => $request->header('Authorization'),
                'Scope' => $request->header('Scope'),
            ]
        ];

        try {
            $response = $this->client->request('GET', "/geo/country/$countryId/province", $params);
            $country = json_decode($response->getBody()->getContents());
            return response()->json([
                        'status' => 'success',
                        'declaration' => 'province_found',
                        'payload' => $country,
                            ], 200);
        } catch (ConnectException $e) {
            $log_message = ErrorCodes::GATEWAY_TIMEOUT . ' : ' . $e->getMessage();
            throw new DfxException(ErrorCodes::GATEWAY_TIMEOUT, 'gateway_timeout', $log_message, 504);
        } catch (RequestException $e) {
            DfxException::dfxExceptionHandler($e);
        }
    }

    /**
     * Get province's county
     *
     * @return  void
     * @author  Amit Panchal <amit@unoindia.co>
     * @version 0.0.1
     */
    public function getCounty(Request $request) {

        $this->validate($request, [
            'provinceId' => 'required',
        ]);

        $provinceId = $request->input('provinceId');
        $scope = json_decode($request->header('scope'), true);
        $dealer_id = $scope['DealerId'];

        // User Login, should create dealership in system.
        $dealership = DealerShips::where('dfx_id', '=', $dealer_id)->first();
        if (is_null($dealership)) {
            return response()->json([
                        'status' => 'error',
                        'declaration' => 'dealership_not_found',
                        'payload' => ['message' => 'Sorry, dealer not found. Please try again.'],
                            ], 404);
        }
        $params = [
            'headers' => [
                'Authorization' => $request->header('Authorization'),
                'Scope' => $request->header('Scope'),
            ]
        ];
        try {
            $response = $this->client->request('GET', "/geo/province/$provinceId/county", $params);
            $country = json_decode($response->getBody()->getContents());
            return response()->json([
                        'status' => 'success',
                        'declaration' => 'county_found',
                        'payload' => $country,
                            ], 200);
        } catch (ConnectException $e) {
            $log_message = ErrorCodes::GATEWAY_TIMEOUT . ' : ' . $e->getMessage();
            throw new DfxException(ErrorCodes::GATEWAY_TIMEOUT, 'gateway_timeout', $log_message, 504);
        } catch (RequestException $e) {
            DfxException::dfxExceptionHandler($e);
        }
    }

    /**
     * To replicate question from source manufacturer
     * @note $fromManufacturer=1 because at very first seeder will be executed with manufacturer =1
     * @return  void
     * @author  Amit Panchal <amit@unoindia.co>
     * @version 0.0.1
     */
    public function copyQuestion($toManufacturer, $fromManufacturer = 1) {
        try {
            $question = Questions::where('manufacturer_id', '=', $fromManufacturer)->get()->toArray();
            //TO DO : Need to remove loop
            if (count($question) > 0) {
                foreach ($question as $key => $val) {
                    $questionArr = [
                        'manufacturer_id' => $toManufacturer,
                        'type' => $val['type'],
                        'question_text' => $val['question_text'],
                        'is_required' => $val['is_required']
                    ];
                    $oldQuestionId = $val['id'];
                    $newQuestion = Questions::firstOrCreate($questionArr, $questionArr);

                    $questionOption = QuestionsOptions::where('question_id', '=', $oldQuestionId)->get()->toArray();
                    if (count($questionOption) > 0) {
                        foreach ($questionOption as $questionOptionKey => $questionOptionval) {
                            $questionOptionArr = [
                                'question_id' => $newQuestion->id,
                                'option_text' => $questionOptionval['option_text']
                            ];
                            QuestionsOptions::firstOrCreate($questionOptionArr, $questionOptionArr);
                        }
                    }
                }
            }
            return response()->json([
                        'status' => 'success',
                        'declaration' => 'question_copied'
                            ], 200);
        } catch (ConnectException $e) {
            $log_message = ErrorCodes::GATEWAY_TIMEOUT . ' : ' . $e->getMessage();
            throw new DfxException(ErrorCodes::GATEWAY_TIMEOUT, 'gateway_timeout', $log_message, 504);
        } catch (RequestException $e) {
            DfxException::dfxExceptionHandler($e);
        }
    }

}
