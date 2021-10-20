<?php

namespace App\Http\Controllers;

use GuzzleHttp\Client as GuzzleHttpClient;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use App\Models\Vehicles;
use App\Models\UserVehicles;
use App\Models\DealerShips;
use App\Models\User\UserDealerShips;
use App\Models\User\User;
use App\Models\Manufacturers;
use App\Exceptions\DfxException;
use App\Helpers\ErrorCodes;
use App\Models\BrandProperty;
use \App\Models\DealerVehicle;

class UserVehicleController extends Controller {

    /**
     * Instance of GuzzleHttpClient
     *
     * @var client
     */
    private $client;

    public function __construct() {

        $this->client = new GuzzleHttpClient([
            'base_uri' => config('dfx.df_protocol') . '://' . config('dfx.df_domain'),
        ]);
        parent::__construct();
    }

    /**
     * Get user vehicle
     * 
     * @return  void
     * @author  Amit Panchal <amit@unoindia.co>
     * @version 0.0.1
     */
    public function getMyVehicle(Request $request) {
        try {
            $authUser = App::make('AuthenticatedUser');
            $userVehicleArr = User::with(['vehicles' => function($query) {
                            
                        }])->where('id', '=', $authUser->id)->first()->toArray();

            $payload = [];
            $manufacturer = [];
            if (count($userVehicleArr) > 0) {
                foreach ($userVehicleArr['vehicles'] as $key => $val) {
                    $vehicleDealerArr = DealerVehicle::with('dealerships', 'manufacturers')->where('vehicle_id', '=', $val['id'])->first()->toArray();
                    
                    $val['dealer_id'] = $vehicleDealerArr['dealer_id'];
                    $val['manufacturer_id'] = $vehicleDealerArr['manufacturer_id'];
                    $val['dfx_dealer_id'] = $vehicleDealerArr['dealerships']['dfx_id'];
                    $val['make'] = $vehicleDealerArr['manufacturers']['name'];
                    $val['dfx_manufacturer'] = $vehicleDealerArr['manufacturers']['dfx_id'];
                    $val['oem_id'] = $vehicleDealerArr['dealerships']['oem_id'];
                    $manufacturer[$key] = $val['manufacturer_id'];
                    
                    //To get brand property for each dealer
                    $mainManufacturerName = (strtoupper($vehicleDealerArr['dealerships']['oem_name']) == 'CHRYSLER') ? 'MOPAR' : strtoupper($vehicleDealerArr['dealerships']['oem_name']);
                    $brandProperty = BrandProperty::where('oem_name', $mainManufacturerName)->first();
                    $brandProperty['logo'] = $vehicleDealerArr['dealerships']['oem_logo'];
                    if (!is_null($brandProperty)) {
                        $brandProperty->toArray();
                        $val['brand_property'] = $brandProperty;
                    }

                    unset($val['pivot']);
                    $payload[] = $val;
                }
            }
            if (count($payload) > 0) {
                $i = 0;
                foreach ($payload as $key => $val) {
                    // To reduce number of token generate for same brand and same user
                    $prev = ($i > 0) ? $i - 1 : 0;
                    if ($i == 0 || ($val['make'] != $payload[$prev]['make'] || $val['dfx_dealer_id'] != $payload[$prev]['dfx_dealer_id'] || $val['manufacturer_id'] != $payload[$prev]['manufacturer_id'])) {
                        //========================= Ecomm dealer information============================//
                        $oemId = $val['oem_id'];
                        $BrandInfo = BrandProperty::select('ecomm_brand_id')->where('oem_id', $oemId)->first();

                        $userController = new UserController;
                        $ecommUser = $userController->getEcommToken($authUser->email, $BrandInfo->ecomm_brand_id);

                        $ecommArr['user'] = (isset($ecommUser->payload)) ? $ecommUser->payload : [];
                        $ecommArr['session'] = (isset($ecommUser->session)) ? $ecommUser->session : [];
                        $val['i'] = $i;
                        //========================= Ecomm dealer information============================//
                    } else {
                        $val['i'] = $i;
                        $val['ecomm_user_detail'] = $payload[$i - 1]['ecomm_user_detail'];
                    }
                    $payload[$i]['ecomm_user_detail'] = $ecommArr;
                    $i++;
                }
            }
            array_multisort($manufacturer, SORT_DESC, $payload);

            return response()->json([
                        'status' => 'success',
                        'declaration' => 'user_vehicle_loaded',
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
     * Vehicle should be connect to user
     * Need to Apply databased related changes on this function
     * @return  void
     * @author  Amit Panchal <amit@unoindia.co>
     * @version 0.0.1
     */
    public function assignVehicle(Request $request) {
        try {
            $this->validate($request, [
                'vin' => 'required|alpha_num',
                'dealer_id' => 'required|integer',
                'manufacturer_id' => 'required',
                'model' => 'required',
                'year' => 'required|date_format:"Y"',
                'transmissions' => 'required',
                'cylinder' => 'required',
                'drive' => 'required'
            ]);

            $vin = $request->input('vin');
            $manufacturerId = $request->input('manufacturer_id');
            $model = $request->input('model');
            $year = $request->input('year');
            $transmissions = $request->input('transmissions');
            $year = $request->input('year');
            $cylinder = $request->input('cylinder');
            $drive = $request->input('drive');
            $dealer_id = $request->input('dealer_id');

            // Vehicle should have dealer id
            $dealership = DealerShips::where('dfx_id', '=', $dealer_id)->first();
            if (is_null($dealership)) {
                return response()->json([
                            'status' => 'error',
                            'declaration' => 'dealership_not_found',
                            'payload' => ['message' => 'Sorry, dealer not found. Please try again.'],
                                ], 404);
            }

            $vehicleObj = $dealership->vehicles()->firstOrCreate(['vin' => $vin], ['vin' => $vin, 'manufacturer_id' => $manufacturerId, 'model' => $model, 'year' => $year, 'transmissions' => $transmissions, 'cylinder' => $cylinder, 'drive' => $drive]);
            $authUser = App::make('AuthenticatedUser');
            $userVehicleArr = [
                'user_id' => $authUser->id,
                'vehicle_id' => $vehicleObj->id,
            ];

            $userVehicle = UserVehicles::updateOrCreate(['vehicle_id' => $vehicleObj->id], $userVehicleArr);
            return response()->json([
                        'status' => 'success',
                        'declaration' => 'user_vehicle_connected',
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
     * Unlink relation between user and vehicle
     * 
     * @return  void
     * @author  Amit Panchal <amit@unoindia.co>
     * @version 0.0.1
     */
    public function removeVehicle(Request $request) {
        try {
            $this->validate($request, [
                'vin' => 'required|alpha_num'
            ]);

            $vin = $request->input('vin');
            $vehicleObj = Vehicles::where(['vin' => $vin])->first();
            if (is_null($vehicleObj)) {
                return response()->json([
                            'status' => 'error',
                            'declaration' => 'vehicle_not_found',
                            'payload' => ['message' => 'Sorry, vehicle not found. Please try again.'],
                                ], 404);
            }
            $authUser = App::make('AuthenticatedUser');
            $userVehicleArr = [
                'user_id' => $authUser->id,
                'vehicle_id' => $vehicleObj->id,
            ];
            $existsUserVehicle = UserVehicles::where($userVehicleArr)->first();
            if (is_null($existsUserVehicle)) {
                return response()->json([
                            'status' => 'error',
                            'declaration' => 'user_vehicle_association_not_found',
                            'payload' => ['message' => 'Sorry, vehicle not connected to this user found. Please try again.'],
                                ], 404);
            }
            $existsUserVehicle->delete();
            return response()->json([
                        'status' => 'success',
                        'declaration' => 'user_vehicle_association_deleted',
                        'payload' => ['message' => 'Removed user and vehicle association'],
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
     * Get makes.     
     *
     * @param   Request $request
     * @return  \Illuminate\Http\Response
     * @author  Amit Panchal <amit@unoindia.co>
     * @version 0.0.1
     */
    public function getMakes(Request $request) {
        $this->validate($request, [
            'dealer_id' => 'required|integer',
        ]);
        try {

            $dealer_id = $request->input('dealer_id');

            $dealership = DealerShips::where('id', '=', $dealer_id)->first();
            if (is_null($dealership)) {
                return response()->json([
                            'status' => 'error',
                            'declaration' => 'dealership_not_found',
                            'payload' => ['message' => 'Sorry, dealer not found. Please try again.'],
                                ], 404);
            }
            $makes = Manufacturers::select('id', 'name', 'dfx_id')->where('dealership_id', '=', $dealer_id)->get()->toArray();
            return response()->json([
                        'status' => 'success',
                        'declaration' => 'makes_found',
                        'payload' => $makes,
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
     * Fetch years of make.     
     * @NOTE Currently we are not storing year in database to we are using S2S we must have to change later on as we get it
     * 
     * @param   Request $request
     * @param   integer  $make_id
     * @return  \Illuminate\Http\Response
     * @author  Amit Panchal <amit@unoindia.co>
     * @version 0.0.1
     */
    public function getYearsOfMakes(Request $request, $make_id) {
        try {
            $dfxToken = config('dfx.dfx_token');
            $params = [
                'headers' => [
                    'Authorization' => $dfxToken->token_type . ' ' . $dfxToken->access_token,
                ]
            ];

            $manufacturer = Manufacturers::where('id', '=', $make_id)->first();
            if (is_null($manufacturer)) {
                return response()->json([
                            'status' => 'error',
                            'declaration' => 'manufacturer_not_found',
                            'payload' => ['message' => 'Sorry, manufacturer not found. Please try again.'],
                                ], 404);
            }
            $dfxManufacturerId = $manufacturer->dfx_id;

            $response = $this->client->request('GET', "/vehicle/make/$dfxManufacturerId/year", $params);
            $years = json_decode($response->getBody()->getContents());

            return response()->json([
                        'status' => 'success',
                        'declaration' => 'years_found',
                        'payload' => $years,
                            ], 200);
        } catch (ConnectException $e) {
            $log_message = ErrorCodes::GATEWAY_TIMEOUT . ' : ' . $e->getMessage();
            throw new DfxException(ErrorCodes::GATEWAY_TIMEOUT, 'gateway_timeout', $log_message, 504);
        } catch (RequestException $e) {
            DfxException::dfxExceptionHandler($e);
        }
    }

    /**
     * Fetching models.     
     * @NOTE Currently there are static data becuase we cant use S2S call
     * 
     * @param   Request $request
     * @return  \Illuminate\Http\Response
     * @author  Amit Panchal <amit@unoindia.co>
     * @version 0.0.1
     */
    public function getModels(Request $request) {
        // TODO: Update validations.
        $this->validate($request, [
            'manufacture_id' => 'required|integer|min:1',
            'year' => 'required|integer|min:1'
        ]);

        $make_id = $request->input('manufacture_id');
        $year = $request->input('year');
        $dfxToken = config('dfx.dfx_token');

        $manufacturer = Manufacturers::where('id', '=', $make_id)->first();
        if (is_null($manufacturer)) {
            return response()->json([
                        'status' => 'error',
                        'declaration' => 'manufacturer_not_found',
                        'payload' => ['message' => 'Sorry, manufacturer not found. Please try again.'],
                            ], 404);
        }
        $dfxManufacturerId = $manufacturer->dfx_id;

        $params = [
            'headers' => [
                'Authorization' => $dfxToken->token_type . ' ' . $dfxToken->access_token,
            ]
        ];
        try {

            $response = $this->client->request('GET', "/vehicle/make/$dfxManufacturerId/year/$year/model", $params);
            $models = json_decode($response->getBody()->getContents());

            return response()->json([
                        'status' => 'success',
                        'declaration' => 'models_found',
                        'payload' => $models->models,
                            ], 200);
        } catch (ConnectException $e) {
            $log_message = ErrorCodes::GATEWAY_TIMEOUT . ' : ' . $e->getMessage();
            throw new DfxException(ErrorCodes::GATEWAY_TIMEOUT, 'gateway_timeout', $log_message, 504);
        } catch (RequestException $e) {
            DfxException::dfxExceptionHandler($e);
        }
    }

    /**
     * Get Transmission.         
     * 
     * @param   Request $request
     * @return  \Illuminate\Http\Response
     * @author  Amit Panchal <amit@unoindia.co>
     * @version 0.0.1
     */
    public function getTransmission(Request $request) {
        // TODO: Update validations.
        $this->validate($request, [
            'manufacture_id' => 'required|integer|min:1',
            'year' => 'required|integer|min:1',
            'model_name' => 'required|string',
        ]);

        $make_id = $request->input('manufacture_id');
        $year = $request->input('year');
        $model_name = $request->input('model_name');
        $dfxToken = config('dfx.dfx_token');

        $manufacturer = Manufacturers::where('id', '=', $make_id)->first();
        if (is_null($manufacturer)) {
            return response()->json([
                        'status' => 'error',
                        'declaration' => 'manufacturer_not_found',
                        'payload' => ['message' => 'Sorry, manufacturer not found. Please try again.'],
                            ], 404);
        }
        $dfxManufacturerId = $manufacturer->dfx_id;

        $params = [
            'headers' => [
                'Authorization' => $dfxToken->token_type . ' ' . $dfxToken->access_token,
            ]
        ];

        try {

            $response = $this->client->request('GET', "/vehicle/make/$dfxManufacturerId/year/$year/model/$model_name/transmission", $params);
            $transmissions = json_decode($response->getBody()->getContents());

            return response()->json([
                        'status' => 'success',
                        'declaration' => 'transmission_found',
                        'payload' => $transmissions,
                            ], 200);
        } catch (ConnectException $e) {
            $log_message = ErrorCodes::GATEWAY_TIMEOUT . ' : ' . $e->getMessage();
            throw new DfxException(ErrorCodes::GATEWAY_TIMEOUT, 'gateway_timeout', $log_message, 504);
        } catch (RequestException $e) {
            DfxException::dfxExceptionHandler($e);
        }
    }

    /**
     * Get Recall
     * Makes GET request to '/schedule/recalls/:vin'
     * 
     * @param   Request $request
     * @return  \Illuminate\Http\Response
     * @author  Amit Panchal <amit@unoindia.co>
     * @version 0.0.1
     */
    public function getRecall(Request $request) {
        $this->validate($request, [
            'vin' => 'required||alpha_num',
            'dealer_id' => 'required||integer',
        ]);

        $vin = $request->input('vin');
        $dealerId = $request->input('dealer_id');
        $dfxToken = config('dfx.dfx_token');

        $dealership = DealerShips::where('id', '=', $dealerId)->first();
        if (is_null($dealership)) {
            return response()->json([
                        'status' => 'error',
                        'declaration' => 'dealership_not_found',
                        'payload' => ['message' => 'Sorry, dealer not found. Please try again.'],
                            ], 404);
        }

        $vehicle = Vehicles::where('vin', '=', $vin)->first();
        if (is_null($vehicle)) {
            return response()->json([
                        'status' => 'error',
                        'declaration' => 'vehicle_not_found',
                        'payload' => ['message' => 'Sorry, vehicle not found. Please try again.'],
                            ], 404);
        }

        $scope_header = json_encode([
            'DealerId' => $dealership->dfx_id,
            'Environment' => config('dfx.environment'),
        ]);

        $params = [
            'headers' => [
                'Authorization' => $dfxToken->token_type . ' ' . $dfxToken->access_token,
                'Scope' => $scope_header,
            ]
        ];
        try {

            $response = $this->client->request('GET', "/schedule/recalls/$vin", $params);
            $recalls = json_decode($response->getBody()->getContents());

            return response()->json([
                        'status' => 'success',
                        'declaration' => 'recall_found',
                        'payload' => $recalls,
                            ], 200);
        } catch (ConnectException $e) {
            $log_message = ErrorCodes::GATEWAY_TIMEOUT . ' : ' . $e->getMessage();
            throw new DfxException(ErrorCodes::GATEWAY_TIMEOUT, 'gateway_timeout', $log_message, 504);
        } catch (RequestException $e) {
            DfxException::dfxExceptionHandler($e);
        }
    }

    /**
     * Get Vehicle Milege
     * Makes GET request to '/schedule/vehiclemileageinterval/{manufacturerId}/{year}/{model}/{transmission}/{engine}/{driveTrain}'
     * 
     * @param   Request $request
     * @return  \Illuminate\Http\Response
     * @author  Amit Panchal <amit@unoindia.co>
     * @version 0.0.1
     */
    public function getVehicleMileage(Request $request) {
        $this->validate($request, [
            'manufacturer_id' => 'required||integer',
            'dealer_id' => 'required||integer',
            'model' => 'required',
            'year' => 'required|integer',
            'transmission' => 'required',
            'engine' => 'required',
            'driveTrain' => 'required'
        ]);

        $manufacturerId = $request->input('manufacturer_id');
        $dealerId = $request->input('dealer_id');
        $model = $request->input('model');
        $year = $request->input('year');
        $transmission = $request->input('transmission');
        $engine = $request->input('engine');
        $driveTrain = $request->input('driveTrain');


        $dfxToken = config('dfx.dfx_token');

        $dealership = DealerShips::where('id', '=', $dealerId)->first();
        if (is_null($dealership)) {
            return response()->json([
                        'status' => 'error',
                        'declaration' => 'dealership_not_found',
                        'payload' => ['message' => 'Sorry, dealer not found. Please try again.'],
                            ], 404);
        }

        //To identify Dfx manufacturer id
        $manufacturer = Manufacturers::where('id', '=', $manufacturerId)->first();
        if (is_null($manufacturer)) {
            return response()->json([
                        'status' => 'error',
                        'declaration' => 'manufacturer_not_found',
                        'payload' => ['message' => 'Sorry, manufacturer not found. Please try again.'],
                            ], 404);
        }
        $dfxManufacturerId = $manufacturer->dfx_id;


        $scope_header = json_encode([
            'DealerId' => $dealership->dfx_id,
            'CultureCode' => $request->input('culture_code'),
            'Environment' => config('dfx.environment'),
        ]);
        $params = [
            'headers' => [
                'Authorization' => $dfxToken->token_type . ' ' . $dfxToken->access_token,
                'Scope' => $scope_header,
            ]
        ];
        try {
            $response = $this->client->request('GET', "/schedule/vehiclemileageinterval/$dfxManufacturerId/$year/$model/$transmission/$engine/$driveTrain", $params);
            $mileage = json_decode($response->getBody()->getContents());

            return response()->json([
                        'status' => 'success',
                        'declaration' => 'milege_found',
                        'payload' => $mileage,
                            ], 200);
        } catch (ConnectException $e) {
            $log_message = ErrorCodes::GATEWAY_TIMEOUT . ' : ' . $e->getMessage();
            throw new DfxException(ErrorCodes::GATEWAY_TIMEOUT, 'gateway_timeout', $log_message, 504);
        } catch (RequestException $e) {
            DfxException::dfxExceptionHandler($e);
        }
    }

    /**
     * Get Maintenance Service
     * Makes GET request to '/schedule/maintenanceservice/{serviceType}/{manufacturerId}/{year}/{model}/{transmission}/{engine}/{driveTrain}/{mileage}'
     * @Note will move to service controller once get an idea about DMPI
     * @param   Request $request
     * @return  \Illuminate\Http\Response
     * @author  Amit Panchal <amit@unoindia.co>
     * @version 0.0.1
     */
    public function getMaintenanceService(Request $request) {

        $this->validate($request, [
            'manufacturer_id' => 'required||integer',
            'dealer_id' => 'required||integer',
            'servicetype' => 'required',
            'model' => 'required',
            'year' => 'required|integer',
            'transmission' => 'required',
            'engine' => 'required',
            'driveTrain' => 'required',
            'mileage' => 'required|integer|min:1'
        ]);

        $manufacturerId = $request->input('manufacturer_id');
        $dealerId = $request->input('dealer_id');
        $model = $request->input('model');
        $year = $request->input('year');
        $transmission = $request->input('transmission');
        $engine = $request->input('engine');
        $driveTrain = $request->input('driveTrain');
        $serviceType = $request->input('servicetype');



        $dfxToken = config('dfx.dfx_token');

        $dealership = DealerShips::where('id', '=', $dealerId)->first();
        if (is_null($dealership)) {
            return response()->json([
                        'status' => 'error',
                        'declaration' => 'dealership_not_found',
                        'payload' => ['message' => 'Sorry, dealer not found. Please try again.'],
                            ], 404);
        }

        //To identify Dfx manufacturer id
        $manufacturer = Manufacturers::where('id', '=', $manufacturerId)->first();
        if (is_null($manufacturer)) {
            return response()->json([
                        'status' => 'error',
                        'declaration' => 'manufacturer_not_found',
                        'payload' => ['message' => 'Sorry, manufacturer not found. Please try again.'],
                            ], 404);
        }
        $dfxManufacturerId = $manufacturer->dfx_id;

        $scope_header = json_encode([
            'DealerId' => $dealership->dfx_id,
            'CultureCode' => $request->input('culture_code'),
            'Environment' => config('dfx.environment'),
        ]);
        $params = [
            'headers' => [
                'Authorization' => $dfxToken->token_type . ' ' . $dfxToken->access_token,
                'Scope' => $scope_header,
            ]
        ];
        try {
            $url = "schedule/maintenanceservice/$serviceType/$dfxManufacturerId/$year/$model/$transmission/$engine/$driveTrain";
            if (!is_null($request->input('mileage'))) {
                $mileage = $request->input('mileage');
                $url .= "/$mileage";
            }

            $response = $this->client->request('GET', $url, $params);
            $maintenanceService = json_decode($response->getBody()->getContents());


            return response()->json([
                        'status' => 'success',
                        'declaration' => 'maintenance_service_found',
                        'payload' => $maintenanceService,
                            ], 200);
        } catch (ConnectException $e) {
            $log_message = ErrorCodes::GATEWAY_TIMEOUT . ' : ' . $e->getMessage();
            throw new DfxException(ErrorCodes::GATEWAY_TIMEOUT, 'gateway_timeout', $log_message, 504);
        } catch (RequestException $e) {
            DfxException::dfxExceptionHandler($e);
        }
    }

    /**
     * Get Vehicle Upcoming Appointments
     * Makes GET request to '/schedule/upcomingappointment/:vin'
     * 
     * @param   Request $request
     * @return  \Illuminate\Http\Response
     * @author  Amit Panchal <amit@unoindia.co>
     * @version 0.0.1
     */
    public function getUpcomingAppointment(Request $request) {
        // TODO: Update validations.
        $this->validate($request, [
            'vin' => 'required|alpha_num',
            'dealer_id' => 'required|integer',
            'culture_code' => 'required|string'
        ]);

        $vin = $request->input('vin');
        $dealer_id = $request->input('dealer_id');
        $dealership = DealerShips::where('id', '=', $dealer_id)->first();
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
            $response = $this->client->request('GET', "/schedule/upcomingappointment/$vin", $params);
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
     * Get Vehicle Status
     * Makes GET request to '/vehicle/vehiclestatus/:vin'
     * 
     * @param   Request $request
     * @return  \Illuminate\Http\Response
     * @author  Amit Panchal <amit@unoindia.co>
     * @version 0.0.1
     */
    public function getVehicleStatus(Request $request) {
        $this->validate($request, [
            'vin' => 'required|alpha_num',
            'dealer_id' => 'required|integer',
            'culture_code' => 'required|string'
        ]);

        $vin = $request->input('vin');
        $dealer_id = $request->input('dealer_id');
        $dealership = DealerShips::where('id', '=', $dealer_id)->first();
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
            $response = $this->client->request('GET', "/vehicle/vehiclestatus/$vin", $params);
            $vehiclestatus = json_decode($response->getBody()->getContents());


            return response()->json([
                        'status' => 'success',
                        'declaration' => 'vehicle_status_found',
                        'payload' => $vehiclestatus
                            ], 200);
        } catch (ConnectException $e) {
            $log_message = ErrorCodes::GATEWAY_TIMEOUT . ' : ' . $e->getMessage();
            throw new DfxException(ErrorCodes::GATEWAY_TIMEOUT, 'gateway_timeout', $log_message, 504);
        } catch (RequestException $e) {
            DfxException::dfxExceptionHandler($e);
        }
    }

    /**
     * Get Vehicle Health 
     * Makes GET request to '/vehicle/health/:vin'
     * 
     * @param   Request $request
     * @return  \Illuminate\Http\Response
     * @author  Amit Panchal <amit@unoindia.co>
     * @version 0.0.1
     */
    public function getVehicleHealth(Request $request) {
        $this->validate($request, [
            'vin' => 'required|alpha_num',
            'dealer_id' => 'required|integer',
            'culture_code' => 'required|string'
        ]);

        $vin = $request->input('vin');
        $dealer_id = $request->input('dealer_id');

        $dealership = DealerShips::where('id', '=', $dealer_id)->first();
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
            $response = $this->client->request('GET', "/vehicle/$vin/health", $params);
            $vehiclehealth = json_decode($response->getBody()->getContents());


            return response()->json([
                        'status' => 'success',
                        'declaration' => 'vehicle_health_found',
                        'payload' => $vehiclehealth
                            ], 200);
        } catch (ConnectException $e) {
            $log_message = ErrorCodes::GATEWAY_TIMEOUT . ' : ' . $e->getMessage();
            throw new DfxException(ErrorCodes::GATEWAY_TIMEOUT, 'gateway_timeout', $log_message, 504);
        } catch (RequestException $e) {
            DfxException::dfxExceptionHandler($e);
        }
    }

    /**
     * Get Vehicle Service History 
     * Makes GET request to '/document/servicehistory/:serviceHistoryType'
     * 
     * @param   Request $request
     * @return  \Illuminate\Http\Response
     * @author  Amit Panchal <amit@unoindia.co>
     * @version 0.0.1
     */
    public function getVehicleServiceHistory(Request $request) {
        $this->validate($request, [
            'vin' => 'required|alpha_num|max:17',
            'dealers' => 'required|array',
            'culture_code' => 'required|string',
            'service_history_type' => 'required|string',
        ]);

        $vin = $request->input('vin');
        $dealers = $request->input('dealers');
        $serviceHistoryType = $request->input('service_history_type');
        $dealerIds = array_column($dealers, 'dealer_id');

        $dealership = DealerShips::whereIn('id', $dealerIds)->get();
        if (is_null($dealership)) {
            return response()->json([
                        'status' => 'error',
                        'declaration' => 'dealership_not_found',
                        'payload' => ['message' => 'Sorry, dealer not found. Please try again.'],
                            ], 404);
        }
        $dealerArr = json_decode($dealership, true);
        // Scope Headers in array
        $scope_header = [];
        if (count($dealerArr) > 0) {
            foreach ($dealerArr as $key => $val) {
                $scope_header[] = ['DealerId' => $val['dfx_id'], 'CultureCode' => $request->input('culture_code'), 'Environment' => config('dfx.environment'), 'vin' => $vin];
            }
        }

        $dfxToken = config('dfx.dfx_token');
        $params = [
            'headers' => [
                'Authorization' => $dfxToken->token_type . ' ' . $dfxToken->access_token,
                'Scope' => json_encode($scope_header)
            ]
        ];

        try {
            $notification = array();
            $this->client = new GuzzleHttpClient(['base_uri' => config('dfx.df_protocol') . '://' . config('dfx.df_domain')]);
            $response = $this->client->request('GET', "/document/servicehistory/$serviceHistoryType", $params);
            $vehiclServiceHistory = json_decode($response->getBody()->getContents());
            return response()->json([
                        'status' => 'success',
                        'declaration' => 'vehicle_service_history_found',
                        'payload' => $vehiclServiceHistory
                            ], 200);
        } catch (ConnectException $e) {
            $log_message = ErrorCodes::GATEWAY_TIMEOUT . ' : ' . $e->getMessage();
            throw new DfxException(ErrorCodes::GATEWAY_TIMEOUT, 'gateway_timeout', $log_message, 504);
        } catch (RequestException $e) {
            DfxException::dfxExceptionHandler($e);
        }
    }

    /**
     * Update vehicle odometer
     *
     * @return  void
     * @author  Amit Panchal <amit@unoindia.co>
     * @version 0.0.1
     */
    public function updateVehicleOdometer(Request $request) {
        $this->validate($request, [
            'vin' => 'required|alpha_num|max:17',
            'dealer_id' => 'required|integer',
            'odometer' => 'required|integer'
        ]);
        $vin = $request->input('vin');
        $dealerId = $request->input('dealer_id');
        $odometer = $request->input('odometer');

        $dealership = DealerShips::where('id', $dealerId)->first();
        if (is_null($dealership)) {
            return response()->json([
                        'status' => 'error',
                        'declaration' => 'dealership_not_found',
                        'payload' => ['message' => 'Sorry, dealer not found. Please try again.'],
                            ], 404);
        }

        $vehicle = Vehicles::where('vin', $vin)->first();
        if (is_null($vehicle)) {
            return response()->json([
                        'status' => 'error',
                        'declaration' => 'vehicle_not_found',
                        'payload' => ['message' => 'Sorry, vehicle not found. Please try again.'],
                            ], 404);
        }


        try {
            $result = Vehicles::where(['vin' => $vin])->update(['odometer' => $odometer]);
            return response()->json([
                        'status' => 'success',
                        'declaration' => 'vehicle_odometer_updated',
                        'payload' => ['message' => 'Vehicle odometer has been updated', 'status' => $result]
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
     * Decode Writeup Url
     * Makes GET request to '/document/writeup/{id}'
     *
     * @return  void
     * @author  Amit Panchal <amit@unoindia.co>
     * @version 0.0.1
     */
    public function getWriteupPdf(Request $request) {
        $this->validate($request, [
            'url' => 'required',
        ]);
        try {
            $url = $request->input('url');
            $dealerId = $request->input('dealer_id');
            $dfxToken = config('dfx.dfx_token');

            $dealership = DealerShips::where('id', '=', $dealerId)->first();
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

            $params = [
                'headers' => [
                    'Authorization' => $dfxToken->token_type . ' ' . $dfxToken->access_token,
                    'Scope' => $scope_header
                ]
            ];

            $urlBreakDown = explode('/', $url);
            $id = end($urlBreakDown);

            $response = $this->client->request('GET', '/document/writeup/' . $id, $params);
            $decodedUrl = json_decode($response->getBody()->getContents());

            return response()->json([
                        'status' => 'success',
                        'declaration' => 'url_decoded',
                        'payload' => $decodedUrl->writeUpPdfUrl,
                            ], 200);
        } catch (ConnectException $e) {
            $log_message = ErrorCodes::GATEWAY_TIMEOUT . ' : ' . $e->getMessage();
            throw new DfxException(ErrorCodes::GATEWAY_TIMEOUT, 'gateway_timeout', $log_message, 504);
        } catch (RequestException $e) {
            DfxException::dfxExceptionHandler($e);
        }
    }

}
