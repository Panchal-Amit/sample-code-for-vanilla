<?php

namespace App\Http\Controllers;

use GuzzleHttp\Client as GuzzleHttpClient;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ConnectException;
use App\Models\AccessCode;
use App\Models\User\User;
use App\Models\User\AuthToken;
use App\Models\User\UserAddress;
use App\Models\User\UserDealerShips;
use App\Models\Manufacturers;
use App\Models\DealerShips;
use App\Models\UserVehicles;
use App\Models\BrandProperty;
use App\Models\CommContents;
use App\Models\CommChannels;
use App\Models\User\UserContentChannels;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Carbon;
use App\Exceptions\DfxException;
use App\Helpers\ErrorCodes;
use App\Mail\SendMail;
use App\Mail\SendDemoMail;

class UserController extends Controller {

    public function __construct() {

        parent::__construct();
    }

    /**
     * Validate user exists with given code
     * 
     * @return  void
     * @author  Amit Panchal <amit@unoindia.co>
     * @version 0.0.1
     */
    public function validateCode(Request $request) {

        $this->validate($request, [
            'code' => 'required|size:6',
        ]);

        $code = $request->input('code');

        try {
            // Check for user exists with curent access code
            $accessCode = AccessCode::where('code', '=', $code)->where('status', '!=', 'cancelled')->first();
            if (is_null($accessCode)) {
                return response()->json([
                            'status' => 'error',
                            'declaration' => 'user_not_found',
                            'payload' => ['message' => 'Sorry, customer not found. Please try again.'],
                                ], 404);
            }
            if ($accessCode->status == 'used') {
                return response()->json([
                            'status' => 'error',
                            'declaration' => 'user_code_used',
                            'payload' => ['message' => 'Sorry, code is already used. Please try other.'],
                                ], 403); //forbidden
            }
            $accessCode->status = 'used';
            $accessCode->save();

            $user_token = [
                'user_id' => $accessCode->user_id,
                'token' => md5(time() . $accessCode->code),
                'expiry' => Carbon::now()->addDay(30)
            ];

            // As to manage how many time user logged in make only insertion rather than update
            $userToken = AuthToken::Create($user_token);

            //To get latest attached vehicle with usre
            $userVehicleInfo = userVehicles::with('vehicles')->where('user_id', $accessCode->user_id)->orderBy('updated_at', 'DESC')->first();
            if (!is_null($userVehicleInfo)) {
                $user_token['vehicle'] = $userVehicleInfo['vehicles'];
            }

            $userInfo = User::with('userAddresses')->where('id', $accessCode->user_id)->first()->toArray();
            $userInfo = array_merge($userInfo, $userInfo['user_addresses'][0]);
            unset($userInfo['user_addresses']);

            return response()->json([
                        'status' => 'success',
                        'declaration' => 'user_found',
                        'payload' => $user_token,
                            ], 200);
        } catch (ConnectException $e) {
            $return = [
                'declaration' => 'gateway_timeout',
                'status' => 'error',
                'payload' => ['message' => $e->getMessage()],
            ];
            return response($return, 504);
        } catch (RequestException $e) {
            return response()->json([
                        'status' => 'error',
                        'declaration' => 'invalid_request',
                        'payload' => json_decode($e->getResponse()->getBody()->getContents()),
                            ], 404);
        }
    }

    /**
     * Send access code on user mobile
     * 
     * @return  void
     * @author  Amit Panchal <amit@unoindia.co>
     * @version 0.0.1
     */
    public function sendCode(Request $request) {
        $this->validate($request, [
            'phone_number' => 'required|integer|digits:10',
        ]);
        try {
            $phone = $request->input('phone_number');
            $userProfObj = User::where('phone', '=', $phone)->first();
            if (is_null($userProfObj)) {
                return response()->json([
                            'status' => 'error',
                            'declaration' => 'user_number_not_found',
                            'payload' => ['message' => 'Sorry, customer number not found. Please try again.'],
                                ], 404);
            }
            do {
                $otp = numericRand(6);
                $accessCodeObj = AccessCode::where('code', '=', $otp)->first();
            } while (!is_null($accessCodeObj));

            $user = $userProfObj->toArray();
            $message = "Your OTP is : " . $otp;
            $subject = "Forgot Password";

            $user['message'] = "Your OTP is : " . $otp;
            $user['otp'] = $otp;
            Mail::to($user['email'])->send(new SendMail($user));

            /* Send SMS */
            $smsResult = sendSMS($user['phone'], $message);
            if (is_object($smsResult) && $smsResult->original['status'] == 'error') {
                return $smsResult->original;
            }

            /*
             * Need to add twilio code to send msg this $code
             */

            //After Message has been sent need to update code to table
            AccessCode::where('user_id', $userProfObj->id)->update([
                'user_id' => $userProfObj->id,
                'code' => $otp,
                'status' => 'active'
            ]);

            return response()->json([
                        'status' => 'success',
                        'declaration' => 'msg_sent',
                        'payload' => ['code' => $otp],
                            ], 200);
        } catch (ConnectException $e) {
            $return = [
                'declaration' => 'gateway_timeout',
                'status' => 'error',
                'payload' => ['message' => $e->getMessage()],
            ];
            return response($return, 504);
        } catch (RequestException $e) {
            return response()->json([
                        'status' => 'error',
                        'declaration' => 'invalid_request',
                        'payload' => json_decode($e->getResponse()->getBody()->getContents()),
                            ], 404);
        }
    }

    /**
     * Get user Profile
     * 
     * @return  void
     * @author  Amit Panchal <amit@unoindia.co>
     * @version 0.0.1
     */
    public function getProfile(Request $request) {
        try {
            $authUser = App::make('AuthenticatedUser');
            $response = $authUser->load('userDealerships.dealerships', 'userAddresses', 'userBillings');
            $decoded_response = json_decode($response, true);

            // Conver as per mobile requirement
            $payload = array();
            $payload['id'] = $decoded_response['id'];
            $payload['password'] = $decoded_response['password'];
            $payload['enrolled_in_ice_app'] = $decoded_response['enrolled_in_ice_app'];
            $payload['user_profile']['user_id'] = $decoded_response['id'];
            $payload['user_profile']['first_name'] = $decoded_response['first_name'];
            $payload['user_profile']['last_name'] = $decoded_response['last_name'];
            $payload['user_profile']['image'] = $decoded_response['image'];
            $payload['user_profile']['email'] = $decoded_response['email'];
            $payload['user_profile']['phone'] = $decoded_response['phone'];
            $payload['user_profile']['home'] = $decoded_response['home'];
            $payload['user_profile']['dob'] = $decoded_response['dob'];
            $payload['user_addresses'] = $decoded_response['user_addresses'];
            $payload['user_billings'] = $decoded_response['user_billings'];
            $payload['user_dealerships'] = $decoded_response['user_dealerships'];

            if (count($payload['user_dealerships']) > 0) {
                $i = 0;
                foreach ($payload['user_dealerships'] as $key => $val) {
                    $payload['user_dealerships'][$i]['dfx_dealer_id'] = $val['dealerships']['dfx_id'];
                    $payload['user_dealerships'][$i]['oem_id'] = $val['dealerships']['oem_id'];
                    $payload['user_dealerships'][$i]['name'] = $val['dealerships']['name'];
                    $payload['user_dealerships'][$i]['email'] = $val['dealerships']['email'];
                    $payload['user_dealerships'][$i]['phone_number'] = $val['dealerships']['phone_number'];
                    $payload['user_dealerships'][$i]['sms_number'] = $val['dealerships']['sms_number'];
                    $payload['user_dealerships'][$i]['roadside_assist'] = $val['dealerships']['roadside_assist'];
                    $payload['user_dealerships'][$i]['address_1'] = $val['dealerships']['address_1'];
                    $payload['user_dealerships'][$i]['address_2'] = $val['dealerships']['address_2'];
                    $payload['user_dealerships'][$i]['city'] = $val['dealerships']['city'];
                    $payload['user_dealerships'][$i]['postal_code'] = $val['dealerships']['postal_code'];
                    $payload['user_dealerships'][$i]['country'] = $val['dealerships']['country'];
                    $payload['user_dealerships'][$i]['latitude'] = $val['dealerships']['latitude'];
                    $payload['user_dealerships'][$i]['longitude'] = $val['dealerships']['longitude'];
                    unset($payload['user_dealerships'][$i]['dealerships']);
                    $i++;
                }
            }

            return response()->json([
                        'status' => 'success',
                        'declaration' => 'user_loaded',
                        'payload' => $payload,
                            ], 200);
        } catch (ConnectException $e) {
            $return = [
                'declaration' => 'gateway_timeout',
                'status' => 'error',
                'payload' => ['message' => $e->getMessage()],
            ];
            return response($return, 504);
        } catch (RequestException $e) {
            return response()->json([
                        'status' => 'error',
                        'declaration' => 'invalid_request',
                        'payload' => json_decode($e->getResponse()->getBody()->getContents()),
                            ], 404);
        }
    }

    /**
     * Update user Profile
     * 
     * @return  void
     * @author  Amit Panchal <amit@unoindia.co>
     * @version 0.0.1
     */
    public function updateProfile(Request $request) {

        $this->validate($request, [
            'full_name' => 'required|string',
            'email' => 'required|email',
            'phone' => 'required||integer|digits:10',
            'address' => 'required',
            'city' => 'required|string',
            'province' => 'required|string',
            'country' => 'required|string',
            'postalCode' => 'required',
            'password' => 'required',
            'confirmed_password' => 'required',
            'oem_id' => 'required'
        ]);
        try {
            $password = $request->input('password');
            $confirmedPass = $request->input('confirmed_password');
            $oemId = $request->input('oem_id');
            if ($password != $confirmedPass) {
                return response()->json([
                            'status' => 'error',
                            'declaration' => 'invalid_credential',
                            'payload' => ['message' => 'Sorry, invalid credential. Please try again.'],
                                ], 401);
            }
            $authUser = App::make('AuthenticatedUser');
            $name = explode(" ", $request->input('full_name'));
            $firstName = $name[0];
            $lasttName = (count($name) > 1) ? $name[1] : '';

            User::where('id', $authUser->id)->update([
                'first_name' => $firstName,
                'last_name' => $lasttName,
                'email' => $request->input('email'),
                'phone' => $request->input('phone'),
                'password' => Hash::make($request->input('password'))
            ]);

            UserAddress::where('user_id', $authUser->id)->update([
                'address_1' => $request->input('address'),
                'city' => $request->input('city'),
                'province' => $request->input('province'),
                'country' => $request->input('country'),
                'postal_code' => $request->input('postalCode')
            ]);
            //To Do : Optimise this
            $userInfo = $authUser->toArray();
            $userInfo['address_1'] = $request->input('address');
            $userInfo['address_2'] = '';
            $userInfo['city'] = $request->input('city');
            $userInfo['province'] = $request->input('province');
            $userInfo['postal_code'] = $request->input('postalCode');
            $userInfo['country'] = $request->input('country');

            // Need to create user for all dealer brand
            $userDealersArr = [];
            $userDealers = $authUser->load('userDealerships.dealerships');
            if (!is_null($userDealers)) {
                $userDealersArr = $userDealers->toArray();
                if (count($userDealersArr['user_dealerships']) > 0) {
                    $i = 0;
                    foreach ($userDealersArr['user_dealerships'] as $key => $val) {

                        $oemId = $val['dealerships']['oem_id'];
                        $BrandInfo = BrandProperty::select('ecomm_brand_id')->where('oem_id', $oemId)->first();
                        $userInfo['ecomm_brand_id'] = (!is_null($BrandInfo)) ? $BrandInfo->ecomm_brand_id : '';

                        //Create or update ecomm user            
                        $ecommUser = $this->createUpdateEcommUser($userInfo);
                        if ($val['preferred'] > 0) {
                            $user_token['ecomm_token'] = (isset($ecommUser->session)) ? $ecommUser->session : [];
                        }
                    }
                }
            }


            User::where('id', $authUser->id)->update(['created_ecomm_user' => 1]);


            return response()->json([
                        'status' => 'success',
                        'declaration' => 'user_updated',
                        'payload' => $authUser->id
                            ], 200);
        } catch (ConnectException $e) {
            $return = [
                'declaration' => 'gateway_timeout',
                'status' => 'error',
                'payload' => ['message' => $e->getMessage()],
            ];
            return response($return, 504);
        } catch (RequestException $e) {
            return response()->json([
                        'status' => 'error',
                        'declaration' => 'invalid_request',
                        'payload' => json_decode($e->getResponse()->getBody()->getContents()),
                            ], 404);
        }
    }

    /**
     * Login User
     * 
     * @return  void
     * @author  Amit Panchal <amit@unoindia.co>
     * @version 0.0.1
     */
    public function login(Request $request) {

        $this->validate($request, [
            'phone_number' => 'required|integer|digits:10',
            'password' => 'required'
        ]);

        $phone = $request->input('phone_number');
        $password = $request->input('password');
        try {

            $userObj = User::with('AccessCode')->where('phone', $phone)->first();
            $user = json_decode($userObj, true);

            if (is_null($userObj) || !Hash::check($password, $user['password'])) {
                return response()->json([
                            'status' => 'error',
                            'declaration' => 'invalid_credential',
                            'payload' => ['message' => 'Sorry, invalid credential. Please try again.']
                                ], 401);
            }

            $user_token = [
                'user_id' => $user['id'],
                'token' => md5(time() . $user['access_code']['code']),
                'expiry' => Carbon::now()->addDay(30)
            ];

            // As to manage how many time user logged in make only insertion rather than update
            $userToken = AuthToken::Create($user_token);
            $userInfo = User::with(['userDealerships.dealerships',
                        'userAddresses' => function($query) {
                            $query->select('id', 'user_id', 'address_1', 'address_2', 'city', 'province', 'postal_code', 'country', 'latitude', 'longitude');
                        },
                    ])->where('id', $user['id'])->first()->toArray();

            //Always at a time only one dealer should be default
            $oemId = '';
            if (count($userInfo['user_dealerships']) > 0) {
                $i = 0;
                foreach ($userInfo['user_dealerships'] as $key => $val) {

                    if ($val['preferred'] > 0) {
                        $oemId = $val['dealerships']['oem_id'];
                    }
                    $userInfo['user_dealerships'][$i]['dfx_dealer_id'] = $val['dealerships']['dfx_id'];
                    $userInfo['user_dealerships'][$i]['oem_id'] = $val['dealerships']['oem_id'];
                    $userInfo['user_dealerships'][$i]['name'] = $val['dealerships']['name'];
                    $userInfo['user_dealerships'][$i]['email'] = $val['dealerships']['email'];
                    $userInfo['user_dealerships'][$i]['phone_number'] = $val['dealerships']['phone_number'];
                    $userInfo['user_dealerships'][$i]['sms_number'] = $val['dealerships']['sms_number'];
                    $userInfo['user_dealerships'][$i]['roadside_assist'] = $val['dealerships']['roadside_assist'];
                    $userInfo['user_dealerships'][$i]['address_1'] = $val['dealerships']['address_1'];
                    $userInfo['user_dealerships'][$i]['address_2'] = $val['dealerships']['address_2'];
                    $userInfo['user_dealerships'][$i]['city'] = $val['dealerships']['city'];
                    $userInfo['user_dealerships'][$i]['postal_code'] = $val['dealerships']['postal_code'];
                    $userInfo['user_dealerships'][$i]['country'] = $val['dealerships']['country'];
                    $userInfo['user_dealerships'][$i]['latitude'] = $val['dealerships']['latitude'];
                    $userInfo['user_dealerships'][$i]['longitude'] = $val['dealerships']['longitude'];

                    //To get brand property for each dealer
                    $mainManufacturerName = (strtoupper($val['dealerships']['oem_name']) == 'CHRYSLER') ? 'MOPAR' : strtoupper($val['dealerships']['oem_name']);
                    $brandProperty = BrandProperty::where('oem_name', $mainManufacturerName)->first();
                    $brandProperty['logo'] = $val['dealerships']['oem_logo'];
                    if (!is_null($brandProperty)) {
                        $brandProperty->toArray();
                        $userInfo['user_dealerships'][$i]['brand_property'] = $brandProperty;
                    }


                    unset($userInfo['user_dealerships'][$i]['dealerships']);
                    $i++;
                }
            }

            $userArr = array();
            $userArr['id'] = $userInfo['id'];

            $userArr['user_profile']['id'] = $userInfo['id'];
            $userArr['user_profile']['user_id'] = $userInfo['id'];
            $userArr['user_profile']['first_name'] = $userInfo['first_name'];
            $userArr['user_profile']['last_name'] = $userInfo['last_name'];
            $userArr['user_profile']['image'] = $userInfo['image'];
            $userArr['user_profile']['email'] = $userInfo['email'];
            $userArr['user_profile']['phone'] = $userInfo['phone'];
            $userArr['user_profile']['home'] = $userInfo['home'];
            $userArr['user_profile']['dob'] = $userInfo['dob'];

            $userArr['user_addresses'] = $userInfo['user_addresses'];
            $userArr['user_dealerships'] = $userInfo['user_dealerships'];

            $payload = array();
            $payload = $user_token;
            $payload['user'] = $userArr;



            return response()->json([
                        'code' => 200,
                        'status' => 'success',
                        'declaration' => 'user_found',
                        'payload' => $payload,
                            ], 200);
        } catch (ConnectException $e) {
            $return = [
                'declaration' => 'gateway_timeout',
                'status' => 'error',
                'payload' => ['message' => $e->getMessage()],
            ];
            return response($return, 504);
        } catch (RequestException $e) {
            return response()->json([
                        'status' => 'error',
                        'declaration' => 'invalid_request',
                        'payload' => json_decode($e->getResponse()->getBody()->getContents()),
                            ], 404);
        }
    }

    /**
     * Forgot password
     * 
     * @return  void
     * @author  Amit Panchal <amit@unoindia.co>
     * @version 0.0.1
     */
    public function forgotPassword(Request $request) {
        $this->validate($request, [
            'phone_number' => 'required|integer|digits:10',
        ]);
        try {
            $phone = $request->input('phone_number');
            $userProfObj = User::where('phone', $phone)->first();

            if (is_null($userProfObj)) {
                return response()->json([
                            'status' => 'error',
                            'declaration' => 'user_number_not_found',
                            'payload' => ['message' => 'Sorry, customer number not found. Please try again.'],
                                ], 404);
            }

            do {
                $otp = numericRand(6);
                $accessCodeObj = AccessCode::where('code', '=', $otp)->first();
            } while (!is_null($accessCodeObj));

            $user = $userProfObj->toArray();
            $message = "Your OTP is : " . $otp;
            $subject = "Forgot Password";

            $user['message'] = "Your OTP is : " . $otp;
            $user['otp'] = $otp;
            Mail::to($user['email'])->send(new SendMail($user));

            /* Send SMS */
            $smsResult = sendSMS($user['phone'], $message);
            if (is_object($smsResult) && $smsResult->original['status'] == 'error') {
                return $smsResult->original;
            }

            AccessCode::where('user_id', $userProfObj->id)->update([
                'user_id' => $user['id'],
                'code' => $otp,
                'status' => 'active'
            ]);

            return response()->json([
                        'status' => 'success',
                        'declaration' => 'email_sms_sent',
                        'payload' => ['opt' => $otp],
                            ], 200);
        } catch (ConnectException $e) {
            $return = [
                'declaration' => 'gateway_timeout',
                'status' => 'error',
                'payload' => ['message' => $e->getMessage()],
            ];
            return response($return, 504);
        } catch (RequestException $e) {
            return response()->json([
                        'status' => 'error',
                        'declaration' => 'invalid_request',
                        'payload' => json_decode($e->getResponse()->getBody()->getContents()),
                            ], 404);
        }
    }

    /**
     * Get User's dealer
     * 
     * @return  void
     * @author  Amit Panchal <amit@unoindia.co>
     * @version 0.0.1
     */
    public function getMyDealer(Request $request) {
        try {

            $authUser = App::make('AuthenticatedUser');
            $userDealer = UserDealerShips::with('dealerships')->where('user_id', $authUser->id)->get()->toArray();
            $playload = [];
            if (count($userDealer) > 0) {
                foreach ($userDealer as $key => $val) {
                    $val['dealerships']['preferred'] = $val['preferred'];
                    $playload[] = $val['dealerships'];
                }
            }
            $dealership = DealerShips::get()->toArray();
            return response()->json([
                        'status' => 'success',
                        'declaration' => 'dealer_found',
                        'payload' => $playload,
                            ], 200);
        } catch (ConnectException $e) {
            $return = [
                'declaration' => 'gateway_timeout',
                'status' => 'error',
                'payload' => ['message' => $e->getMessage()],
            ];
            return response($return, 504);
        } catch (RequestException $e) {
            return response()->json([
                        'status' => 'error',
                        'declaration' => 'invalid_request',
                        'payload' => json_decode($e->getResponse()->getBody()->getContents()),
                            ], 404);
        }
    }

    /**
     * Assign dealer to user
     * 
     * @return  void
     * @author  Amit Panchal <amit@unoindia.co>
     * @version 0.0.1
     */
    public function assignUserDealer(Request $request) {
        try {
            $this->validate($request, [
                'dealer_id' => 'required|integer',
                'preferred' => 'required'
            ]);
            $dealer_id = $request->input('dealer_id');
            $dealership = DealerShips::where('id', '=', $dealer_id)->first();
            if (is_null($dealership)) {
                return response()->json([
                            'status' => 'error',
                            'declaration' => 'dealership_not_found',
                            'payload' => ['message' => 'Sorry, dealer not found. Please try again.'],
                                ], 404);
            }

            $authUser = App::make('AuthenticatedUser');
            $userDealerArr = [
                'user_id' => $authUser->id,
                'dealership_id' => $dealership->id,
                'preferred' => $request->input('preferred')
            ];

            $userVehicle = UserDealerShips::updateOrCreate(['user_id' => $authUser->id, 'dealership_id' => $dealership->id], $userDealerArr);
            return response()->json([
                        'status' => 'success',
                        'declaration' => 'user_dealer_connected',
                        'payload' => [$userVehicle->id],
                            ], 200);
        } catch (ConnectException $e) {
            $return = [
                'declaration' => 'gateway_timeout',
                'status' => 'error',
                'payload' => ['message' => $e->getMessage()],
            ];
            return response($return, 504);
        } catch (RequestException $e) {
            return response()->json([
                        'status' => 'error',
                        'declaration' => 'invalid_request',
                        'payload' => json_decode($e->getResponse()->getBody()->getContents()),
                            ], 404);
        }
    }

    /**
     * Book appointment for user on selected date and time 
     * Makes POST request to '/schedule/bookappointment/{departmentId}'
     * 
     * @return  void
     * @author  Amit Panchal <amit@unoindia.co>
     * @version 0.0.1
     */
    public function bookAppointment(Request $request) {
        // Input validations
        $this->validate($request, [
            'customerId' => 'required|integer',
            'vin' => 'required|alpha_num',
            'mileage' => 'required|integer',
            'appointmentDateTimeStr' => 'required',
            'culture_code' => 'required',
            'dealer_id' => 'required|integer'
        ]);

        $dealer_id = $request->input('dealer_id');
        $dealership = DealerShips::where('id', '=', $dealer_id)->first();
        if (is_null($dealership)) {
            return response()->json([
                        'status' => 'error',
                        'declaration' => 'dealership_not_found',
                        'payload' => ['message' => 'Sorry, dealer not found. Please try again.'],
                            ], 404);
        }

        $dfxToken = config('dfx.dfx_token');

        $scope_header = json_encode([
            'DealerId' => $dealership->dfx_id,
            'CultureCode' => $request->input('culture_code'),
            'Environment' => config('dfx.environment'),
        ]);

        $inputParam = [
            "CustomerId" => $request->input('customerId'),
            "Vin" => $request->input('vin'),
            "AppointmentDateTimeStr" => $request->input('appointmentDateTimeStr'),
            "Mileage" => $request->input('mileage'),
            "TransportationOption" => $request->input('transportationOption'),
            "advisor_id" => $request->input('advisor_id'),
        ];
        if (!is_null($request->input('Drs'))) {
            $inputParam["drs"] = $request->input('Drs');
        }
        if (!is_null($request->input('Frs'))) {
            $inputParam["frs"] = $request->input('Frs');
        }
        if (!is_null($request->input('Repairs'))) {
            $inputParam["repairs"] = $request->input('Repairs');
        }
        if (!is_null($request->input('Recalls'))) {
            $inputParam["recalls"] = $request->input('Recalls');
        }


        // Params
        $params = [
            'headers' => [
                'Authorization' => $dfxToken->token_type . ' ' . $dfxToken->access_token,
                'Scope' => $scope_header,
            ],
            'json' => $inputParam,
        ];


        try {
            $this->client = new GuzzleHttpClient(['base_uri' => config('dfx.df_protocol') . '://' . config('dfx.df_domain')]);
            $response = $this->client->request('POST', "/schedule/bookappointment", $params);
            $decoded_response = json_decode($response->getBody()->getContents());

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
     * Get Appointment by appointment id
     * Makes POST request to '/schedule/appointmentDetails/:appointmentId'
     * @Note : Giving error right now waiting for DFX to solve
     * @return  void
     * @author  Amit Panchal <amit@unoindia.co>
     * @version 0.0.1
     */
    public function getAppointmentById(Request $request) {
        // TODO: Update validations.
        $this->validate($request, [
            'appointment_id' => 'required|integer',
            'dealer_id' => 'required|integer',
            'culture_code' => 'required'
        ]);

        $appointmentId = $request->input('appointment_id');
        $dealerId = $request->input('dealer_id');
        $dealership = DealerShips::where('id', '=', $dealerId)->first();
        if (is_null($dealership)) {
            return response()->json([
                        'status' => 'error',
                        'declaration' => 'dealership_not_found',
                        'payload' => ['message' => 'Sorry, dealer not found. Please try again.'],
                            ], 404);
        }

        // Scope Headers
        $scope_header = json_encode([
            'DealerId' => $dealership->dfx_id,
            'CultureCode' => $request->input('culture_code'),
            'Environment' => config('dfx.environment'),
        ]);

        $dfxToken = config('dfx.dfx_token');
        $params = [
            'headers' => [
                'Authorization' => $dfxToken->token_type . ' ' . $dfxToken->access_token,
                'Scope' => $scope_header
            ]
        ];
        try {
            $notification = array();

            $this->client = new GuzzleHttpClient(['base_uri' => config('dfx.df_protocol') . '://' . config('dfx.df_domain')]);
            $response = $this->client->request('GET', "/schedule/appointmentDetails/$appointmentId", $params);
            $appointment = json_decode($response->getBody()->getContents());


            return response()->json([
                        'status' => 'success',
                        'declaration' => 'appointment_found',
                        'payload' => $appointment
                            ], 200);
        } catch (ConnectException $e) {
            $log_message = ErrorCodes::GATEWAY_TIMEOUT . ' : ' . $e->getMessage();
            throw new DfxException(ErrorCodes::GATEWAY_TIMEOUT, 'gateway_timeout', $log_message, 504);
        } catch (RequestException $e) {
            DfxException::dfxExceptionHandler($e);
        }
    }

    /**
     * Post user
     * Makes POST request to '/iceuser'
     * @note Giving error 
     * @return  void
     * @author  Amit Panchal <amit@unoindia.co>
     * @version 0.0.1
     */
    public function postUser(Request $request) {
        // TODO: Update validations.
        $this->validate($request, [
            'dealer_id' => 'required|integer',
            'culture_code' => 'required',
            'customer_id' => 'required|integer',
            'program_id' => 'required|integer',
            'dealer_program_id' => 'required|integer'
        ]);

        $dealerId = $request->input('dealer_id');
        $dealership = DealerShips::where('id', '=', $dealerId)->first();
        if (is_null($dealership)) {
            return response()->json([
                        'status' => 'error',
                        'declaration' => 'dealership_not_found',
                        'payload' => ['message' => 'Sorry, dealer not found. Please try again.'],
                            ], 404);
        }

        // Scope Headers
        $scope_header = json_encode([
            'DealerId' => $dealership->dfx_id,
            'CultureCode' => $request->input('culture_code'),
            'Environment' => config('dfx.environment'),
            'CustomerId' => $request->input('customer_id'),
            'ProgramId' => $request->input('program_id'),
            'DealerProgramId' => $request->input('dealer_program_id')
        ]);

        $dfxToken = config('dfx.dfx_token');
        $params = [
            'headers' => [
                'Authorization' => $dfxToken->token_type . ' ' . $dfxToken->access_token,
                'Scope' => $scope_header
            ]
        ];
        try {
            $this->client = new GuzzleHttpClient(['base_uri' => config('dfx.df_protocol') . '://' . config('dfx.df_domain')]);
            $response = $this->client->request('POST', "/iceuser", $params);
            $appointment = json_decode($response->getBody()->getContents());

            return response()->json([
                        'status' => 'success',
                        'declaration' => 'user_posted',
                        'payload' => $appointment
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

        $this->validate($request, [
            'dealer_id' => 'required|integer',
            'culture_code' => 'required'
        ]);
        $dealer_id = $request->input('dealer_id');
        $dealership = DealerShips::where('id', '=', $dealer_id)->first();
        if (is_null($dealership)) {
            return response()->json([
                        'status' => 'error',
                        'declaration' => 'dealership_not_found',
                        'payload' => ['message' => 'Sorry, dealer not found. Please try again.'],
                            ], 404);
        }
        $dfxToken = config('dfx.dfx_token');
        $scope_header = json_encode([
            'DealerId' => $dealership->dfx_id,
            'CultureCode' => $request->input('culture_code'),
            'Environment' => config('dfx.environment'),
        ]);

        $params = [
            'headers' => [
                'Authorization' => $dfxToken->token_type . ' ' . $dfxToken->access_token,
                'Scope' => $scope_header
            ]
        ];
        try {
            $this->client = new GuzzleHttpClient(['base_uri' => config('dfx.df_protocol') . '://' . config('dfx.df_domain')]);
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
     * To get appointment time slots
     * Makes POST request to '/schedule/appointment/:appointmentType/timeslots/:date'
     *
     * @return  void
     * @author  Amit Panchal <amit@unoindia.co>
     * @version 0.0.1
     */
    public function getAppointmentTimeSlots(Request $request) {
        $this->validate($request, [
            'dealer_id' => 'required|integer',
            'culture_code' => 'required',
            'appointment_type' => 'required|integer',
            'appointment_date' => 'required|date_format:"Y-m-d"'
        ]);
        $appointmentType = $request->input('appointment_type');
        $appointmentDate = $request->input('appointment_date');
        $dfxToken = config('dfx.dfx_token');
        $dealer_id = $request->input('dealer_id');
        $dealership = DealerShips::where('id', '=', $dealer_id)->first();
        if (is_null($dealership)) {
            return response()->json([
                        'status' => 'error',
                        'declaration' => 'dealership_not_found',
                        'payload' => ['message' => 'Sorry, dealer not found. Please try again.'],
                            ], 404);
        }
        $scope_header = json_encode([
            'DealerId' => $dealership->dfx_id,
            'CultureCode' => $request->input('culture_code'),
            'Environment' => config('dfx.environment'),
        ]);
        // Params
        $params = [
            'headers' => [
                'Authorization' => $dfxToken->token_type . ' ' . $dfxToken->access_token,
                'Scope' => $scope_header,
            ]
        ];
        try {
            $url = "/schedule/appointment/$appointmentType/timeslots/$appointmentDate";
            if (!is_null($request->input('advisor'))) {
                $advisor = $request->input('advisor');
                $url .= "/$advisor";
            }
            $this->client = new GuzzleHttpClient(['base_uri' => config('dfx.df_protocol') . '://' . config('dfx.df_domain')]);
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
     * get Transportation Option
     * Makes POST request to '/schedule/transportationOptions/:date'
     *
     * @return  void
     * @author  Amit Panchal <amit@unoindia.co>
     * @version 0.0.1
     */
    public function getTransportationOption(Request $request) {
        $this->validate($request, [
            'transportation_date' => 'required|date_format:"Y-m-d"',
            'dealer_id' => 'required|integer',
            'culture_code' => 'required',
        ]);

        $transportaionDate = $request->input('transportation_date');

        $dfxToken = config('dfx.dfx_token');
        $dealer_id = $request->input('dealer_id');
        $dealership = DealerShips::where('id', '=', $dealer_id)->first();
        if (is_null($dealership)) {
            return response()->json([
                        'status' => 'error',
                        'declaration' => 'dealership_not_found',
                        'payload' => ['message' => 'Sorry, dealer not found. Please try again.'],
                            ], 404);
        }
        $scope_header = json_encode([
            'DealerId' => $dealership->dfx_id,
            'CultureCode' => $request->input('culture_code'),
            'Environment' => config('dfx.environment'),
        ]);
        // Params
        $params = [
            'headers' => [
                'Authorization' => $dfxToken->token_type . ' ' . $dfxToken->access_token,
                'Scope' => $scope_header,
            ]
        ];
        try {
            $this->client = new GuzzleHttpClient(['base_uri' => config('dfx.df_protocol') . '://' . config('dfx.df_domain')]);
            $response = $this->client->request('GET', "/schedule/transportationOptions/$transportaionDate", $params);
            $decoded_response = json_decode($response->getBody()->getContents(), true);
            if (count($decoded_response['transportationOptions']) > 0) {
                $i = 0;
                foreach ($decoded_response['transportationOptions'] as $key => $val) {
                    $decoded_response['transportationOptions'][$i]['icon'] = $request->root() . '/images/transportation_option/' . $val['code'] . '.png';
                    $decoded_response['transportationOptions'][$i]['active_logo'] = $request->root() . '/images/transportation_option/' . 'white-' . $val['code'] . '.png';
                    $i++;
                }
            }
            return response()->json([
                        'status' => 'success',
                        'declaration' => 'get_transportation',
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
     * Set default dealer for user     
     *
     * @return  void
     * @author  Amit Panchal <amit@unoindia.co>
     * @version 0.0.1
     */
    public function makeDefaultDealer(Request $request) {
        $this->validate($request, [
            'dealer_id' => 'required|integer',
            'default' => 'required|boolean',
        ]);
        $preferred = $request->input('default');
        $dealerId = $request->input('dealer_id');

        $dealership = DealerShips::where('id', '=', $dealerId)->first();
        if (is_null($dealership)) {
            return response()->json([
                        'status' => 'error',
                        'declaration' => 'dealership_not_found',
                        'payload' => ['message' => 'Sorry, dealer not found. Please try again.'],
                            ], 404);
        }


        try {
            $authUser = App::make('AuthenticatedUser');

            $userDealer = UserDealerShips::where('user_id', $authUser->id)->get()->toArray();
            if (!$preferred && count($userDealer) <= 1) {
                return response()->json([
                            'status' => 'error',
                            'declaration' => 'default_dealer_not_set',
                            'payload' => ['message' => 'Sorry, One dealer must be set as default'],
                                ], 400);
            } else if (!$preferred && count($userDealer) > 1) {
                $default = false;
                foreach ($userDealer as $key => $val) {
                    if ($val['preferred']) {
                        $default = true;
                        break;
                    }
                }
                if (!$default) {
                    return response()->json([
                                'status' => 'error',
                                'declaration' => 'default_dealer_not_set',
                                'payload' => ['message' => 'Sorry, One dealer must be set as default'],
                                    ], 400);
                }
            }
            $result = UserDealerShips::where(['user_id' => $authUser->id, 'dealership_id' => $dealerId])->update(['preferred' => $preferred]);
            UserDealerShips::where('user_id', '=', $authUser->id)->where('dealership_id', '<>', $dealerId)->update(['preferred' => 0]);

            return response()->json([
                        'status' => 'success',
                        'declaration' => 'default_dealer_set',
                        'payload' => ['message' => 'Dealer preferences has been updated', 'status' => $result],
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
     *      
     * @return  void
     * @author  Amit Panchal <amit@unoindia.co>
     * @version 0.0.1
     */
    public function communicationPreferences(Request $request) {
        $this->validate($request, [
            'dealer_id' => 'required|integer',
        ]);

        $dealerId = $request->input('dealer_id');
        $dealership = DealerShips::where('id', '=', $dealerId)->first();
        if (is_null($dealership)) {
            return response()->json([
                        'status' => 'error',
                        'declaration' => 'dealership_not_found',
                        'payload' => ['message' => 'Sorry, dealer not found. Please try again.'],
                            ], 404);
        }
        try {
            $authUser = App::make('AuthenticatedUser');
            // TO DO : Fetching user's channel and content from database
            $userAllPreferences = userDealerships::with(['users' => function($query) {
                            $query->with('UserContentChannels', 'UserContentChannels.CommContents', 'UserContentChannels.CommChannels');
                        }])->where(['user_id' => $authUser->id, 'dealership_id' => $dealerId])->first();
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
                        $userPreferencesRes['customerPreference'][] = $val;
                    }
                }
            }
            return response()->json([
                        'status' => 'success',
                        'declaration' => 'communication_preferences',
                        'payload' => $userPreferencesRes,
                            ], 200);
        } catch (ConnectException $e) {
            $return = [
                'declaration' => 'gateway_timeout',
                'status' => 'error',
                'payload' => ['message' => $e->getMessage()],
            ];
            return response($return, 504);
        } catch (RequestException $e) {
            return response()->json([
                        'status' => 'error',
                        'declaration' => 'invalid_request',
                        'payload' => json_decode($e->getResponse()->getBody()->getContents()),
                            ], 404);
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
            'dealer_id' => 'required|integer',
            'culture_code' => 'required',
            'customerPreference' => 'required|array'
        ]);

        $dealer_id = $request->input('dealer_id');
        $dealership = DealerShips::where('id', '=', $dealer_id)->first();
        if (is_null($dealership)) {
            return response()->json([
                        'status' => 'error',
                        'declaration' => 'dealership_not_found',
                        'payload' => ['message' => 'Sorry, dealer not found. Please try again.'],
                            ], 404);
        }

        $authUser = App::make('AuthenticatedUser');
        $userDealer = UserDealerShips::with('dealerships')->where(['user_id' => $authUser->id, 'dealership_id' => $dealership->id])->first();

        if (is_null($userDealer)) {
            return response()->json([
                        'status' => 'error',
                        'declaration' => 'customer_not_found',
                        'payload' => ['message' => 'Sorry, customer not found. Please try again.'],
                            ], 404);
        }

        $scope_header = json_encode([
            'DealerId' => $dealership->dfx_id,
            'CultureCode' => $request->input('culture_code'),
            'Environment' => config('dfx.environment'),
        ]);


        $input_request['customerPreference'] = $request->input('customerPreference');
        $dfxToken = config('dfx.dfx_token');

        // Params
        $params = [
            'headers' => [
                'Authorization' => $dfxToken->token_type . ' ' . $dfxToken->access_token,
                'Scope' => $scope_header,
            ],
            'json' => $input_request
        ];
        try {

            $customerId = $userDealer->user_dfx_id;

            /*
              Call is not working from DFX side

              $this->client = new GuzzleHttpClient(['base_uri' => config('dfx.df_protocol') . '://' . config('dfx.df_domain')]);
              $response = $this->client->request('PUT', "/customer/$customerId/communicationPreferences", $params);
              $decoded_response = json_decode($response->getBody()->getContents());

             */

            if (count($input_request['customerPreference'] > 0)) {
                foreach ($input_request['customerPreference'] as $preferenceKey => $preferenceVal) {
                    $commContent = CommContents::where(['name' => $preferenceVal['notificationPreference']])->first();
                    $user_content_channel = array();
                    foreach ($preferenceVal as $preferenceChannelKey => $preferenceChannelVal) {
                        if ($preferenceChannelKey != 'notificationPreference') {
                            $commChannel = CommChannels::where(['name' => $preferenceChannelKey])->first();
                            $user_content_channel = [
                                'user_id' => $authUser->id,
                                'content_id' => $commContent->id,
                                'channel_id' => $commChannel->id,
                                'status' => (boolean) $preferenceChannelVal
                            ];
                            $userContentChangel = UserContentChannels::updateOrCreate(['user_id' => $authUser->id, 'content_id' => $commContent->id, 'channel_id' => $commChannel->id], $user_content_channel);
                        }
                    }
                }
            }


            $decoded_response["message"] = "Commnication preferences has been updated";
            return response()->json([
                        'status' => 'success',
                        'declaration' => 'updated_communication_preferences',
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
     * create User inside ecomm application
     *
     * @return  void
     * @author  Amit Panchal <amit@unoindia.co>
     * @version 0.0.1
     */
    public function createUpdateEcommUser($userInfo = array()) {
        try {
            //Ecomm Registration                                            
            $body = [
                'brand_id' => $userInfo['ecomm_brand_id'],
                'first_name' => $userInfo['first_name'],
                "last_name" => $userInfo['last_name'],
                "email" => $userInfo['email'],
                "phone" => $userInfo['phone'],
                "address" => $userInfo['address_1'],
                "address2" => $userInfo['address_2'],
                "city" => $userInfo['city'],
                "province" => $userInfo['province'],
                "postal_code" => $userInfo['postal_code'],
                "country" => $userInfo['country'],
                "password" => "Dfx@123",
                "password_confirmation" => "Dfx@123",
                "ref_code" => "",
                "send_notification" => 1,
                "send_email" => 1,
                "register_from" => "web",
                "term_and_condition" => 1
            ];
            $params = [
                'headers' => [
                    'app-id' => env('ECOMM_APP_ID'),
                    'app-secret' => env('ECOMM_APP_SECRET'),
                    'Content-Type' => 'application/json'
                ],
                'json' => $body,
            ];
            $this->client = new GuzzleHttpClient();
            $response = $this->client->request('POST', env('ECOMM_URL') . "/api/app/user/updateUserDFX?platform=web&app_version=1.1.10", $params);
            return json_decode($response->getBody()->getContents());
        } catch (ConnectException $e) {
            $return = [
                'declaration' => 'gateway_timeout',
                'status' => 'error',
                'payload' => ['message' => $e->getMessage()],
            ];
            return response($return, 504);
        } catch (RequestException $e) {
            return response()->json([
                        'status' => 'error',
                        'declaration' => 'invalid_request',
                        'payload' => json_decode($e->getResponse()->getBody()->getContents()),
                            ], 404);
        }
    }

    /**
     * Login to ecomm
     *
     * @return  void
     * @author  Amit Panchal <amit@unoindia.co>
     * @version 0.0.1
     */
    public function getEcommToken($email, $brandId = 0) {
        try {
            //Ecomm Registration                                            
            $body = [
                'user_name' => $email,
                'password' => 'Dfx@123',
                "brand_id" => $brandId
            ];
            $params = [
                'headers' => [
                    'app-id' => env('ECOMM_APP_ID'),
                    'app-secret' => env('ECOMM_APP_SECRET'),
                    'Content-Type' => 'application/json'
                ],
                'json' => $body,
            ];
            $this->client = new GuzzleHttpClient();
            $response = $this->client->request('POST', env('ECOMM_URL') . "/api/app/user/login", $params);
            return json_decode($response->getBody()->getContents());
        } catch (ConnectException $e) {
            $return = [
                'declaration' => 'gateway_timeout',
                'status' => 'error',
                'payload' => ['message' => $e->getMessage()],
            ];
            return response($return, 504);
        } catch (RequestException $e) {
            return response()->json([
                        'status' => 'error',
                        'declaration' => 'invalid_request',
                        'payload' => json_decode($e->getResponse()->getBody()->getContents()),
                            ], 404);
        }
    }

    /**
     * Get dealer location
     * Makes GET request to '/dealer/locations/{iceuserid}'
     * @note not completed yet
     * @return  void
     * @author  Amit Panchal <amit@unoindia.co>
     * @version 0.0.1
     *     
     */
    public function getDealerLocation(Request $request) {
        $this->validate($request, [
            'dealer_id' => 'required|integer',
        ]);
        try {

            $authUser = App::make('AuthenticatedUser');
            $dealer_id = $request->input('dealer_id');
            $dealership = DealerShips::where('id', '=', $dealer_id)->first();

            if (is_null($dealership)) {
                return response()->json([
                            'status' => 'error',
                            'declaration' => 'dealership_not_found',
                            'payload' => ['message' => 'Sorry, dealer not found. Please try again.'],
                                ], 404);
            }

            $userVehicle = UserDealerShips::where(['user_id' => $authUser->id, 'dealership_id' => $dealership->id])->first();
            $dfxToken = config('dfx.dfx_token');
            $scope_header = json_encode([
                'Environment' => config('dfx.environment'),
                'IceUserId' => $userVehicle->user_dfx_id
            ]);
            $params = [
                'headers' => [
                    'Authorization' => $dfxToken->token_type . ' ' . $dfxToken->access_token,
                    'Scope' => $scope_header
                ]
            ];
            $this->client = new GuzzleHttpClient(['base_uri' => config('dfx.df_protocol') . '://' . config('dfx.df_domain')]);
            $response = $this->client->request('GET', "/dealer/locations", $params);
            $dealerLocation = json_decode($response->getBody()->getContents(), true);


            //===================Start Temporary Code===================================
            if (count($dealerLocation['dealerLocations']) <= 0) {
                $dealerLocation['dealerLocations'][] = [
                    "dealerId" => $dealership->dfx_id,
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

                    DealerShips::where(['dfx_id' => $dealership->dfx_id])->update([
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
                        'payload' => $dealerLocation,
                            ], 200);
        } catch (ConnectException $e) {
            $log_message = ErrorCodes::GATEWAY_TIMEOUT . ' : ' . $e->getMessage();
            throw new DfxException(ErrorCodes::GATEWAY_TIMEOUT, 'gateway_timeout', $log_message, 504);
        } catch (RequestException $e) {
            DfxException::dfxExceptionHandler($e);
        }
    }

    /**
     * Get invoice
     * Makes GET request to 'user/get_invoice'
     * @note not completed yet
     * @return  void
     * @author  Amit Panchal <amit@unoindia.co>
     * @version 0.0.1
     *     
     */
    public function getInvoice(Request $request) {
        $this->validate($request, [
            'dealer_id' => 'required|integer',
            'vin' => 'required|array',
        ]);
        $dealer_id = $request->input('dealer_id');
        $dealership = DealerShips::where('id', '=', $dealer_id)->first();
        if (is_null($dealership)) {
            return response()->json([
                        'status' => 'error',
                        'declaration' => 'dealership_not_found',
                        'payload' => ['message' => 'Sorry, dealer not found. Please try again.'],
                            ], 404);
        }

        $vinArr = $request->input('vin');
        if (count($vinArr) > 0) {
            foreach ($vinArr as $key => $val) {

                $scope_header[] = [
                    'cultureCode' => $request->input('culture_code'),
                    'environment' => config('dfx.environment'),
                    'vin' => $val,
                    'dealerId' => $dealership->dfx_id
                ];
            }
        }
        try {

            $dfxToken = config('dfx.dfx_token');
            $params = [
                'headers' => [
                    'Authorization' => $dfxToken->token_type . ' ' . $dfxToken->access_token,
                    'Scope' => json_encode($scope_header)
                ]
            ];

            $this->client = new GuzzleHttpClient(['base_uri' => config('dfx.df_protocol') . '://' . config('dfx.df_domain')]);
            $response = $this->client->request('GET', "/document/invoices", $params);
            $invoice = json_decode($response->getBody()->getContents());

            return response()->json([
                        'status' => 'success',
                        'declaration' => 'invoice_found',
                        'payload' => $invoice,
                            ], 200);
        } catch (ConnectException $e) {
            $log_message = ErrorCodes::GATEWAY_TIMEOUT . ' : ' . $e->getMessage();
            throw new DfxException(ErrorCodes::GATEWAY_TIMEOUT, 'gateway_timeout', $log_message, 504);
        } catch (RequestException $e) {
            DfxException::dfxExceptionHandler($e);
        }
    }

    /**
     * Get user document     
     * @return  void
     * @author  Amit Panchal <amit@unoindia.co>
     * @version 0.0.1
     *     
     */
    public function getDocument(Request $request) {
        try {
            $authUser = App::make('AuthenticatedUser');
            $document = $authUser->load('userDocuments');
            $documentArr = json_decode($document, true);
            $payload = [];
            if (count($documentArr['user_documents']) > 0) {
                $payload = $documentArr['user_documents'];
            }
            return response()->json([
                        'status' => 'success',
                        'declaration' => 'document_loaded',
                        'payload' => $payload,
                            ], 200);
        } catch (ConnectException $e) {
            $return = [
                'declaration' => 'gateway_timeout',
                'status' => 'error',
                'payload' => ['message' => $e->getMessage()],
            ];
            return response($return, 504);
        } catch (RequestException $e) {
            return response()->json([
                        'status' => 'error',
                        'declaration' => 'invalid_request',
                        'payload' => json_decode($e->getResponse()->getBody()->getContents()),
                            ], 404);
        }
    }

    /**
     * This is for demo mail as per requested by @arun
     * @return  void
     * @author  Amit Panchal <amit@unoindia.co>
     * @version 0.0.1
     *     
     */
    public function sendMail($type) {
        try {
            $message = '';
            if (strtolower(trim($type)) == 'predelivery1') {
                $message = 'Satisfied with the condition of your Toyota at the time of delivery?';
            } else {
                $message = 'Vehicle delivered with a full tank of gas or a gas voucher?';
            }

            Mail::to('oli@unoapp.com')->bcc('uno.arun20@gmail.com')->send(new SendDemoMail($message));

            return response()->json([
                        'status' => 'success',
                        'declaration' => 'mail_sent'
                            ], 200);
        } catch (ConnectException $e) {
            $return = [
                'declaration' => 'gateway_timeout',
                'status' => 'error',
                'payload' => ['message' => $e->getMessage()],
            ];
            return response($return, 504);
        } catch (RequestException $e) {
            return response()->json([
                        'status' => 'error',
                        'declaration' => 'invalid_request',
                        'payload' => json_decode($e->getResponse()->getBody()->getContents()),
                            ], 404);
        }
    }

}
