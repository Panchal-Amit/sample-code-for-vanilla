<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use GuzzleHttp\Client as GuzzleHttpClient;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ConnectException;
use App\Models\Vehicles;
use App\Models\User\User;
use App\Models\User\UserAddress;
use App\Models\User\UserDealerShips;
use App\Models\DealerShips;
use App\Models\UserVehicles;
use App\Models\Manufacturers;
use App\Exceptions\DfxException;
use App\Helpers\ErrorCodes;
use App\Models\DealerVehicle;
use App\Models\MainManufacturer;

/**
 * Vehicle Controller
 */
class VehicleController extends Controller {

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
     * Get vehicle info with VIN.
     * Makes GET request to '/vehicle/dmsSearch/:vin'
     *
     * @return  void
     * @author  Amandeep Singh <amandeep@unoapp.com>
     * @version 0.0.1
     *
     */
    public function getByVin(Request $request) {
        // Input validations
        $this->validate($request, [
            'vin' => 'required|alpha_num|min:17|max:17',
            'dealer_id' => 'required|integer',
            'culture_code' => 'required|string',
        ]);

        $vin = $request->input('vin');
        $dealer_id = $request->input('dealer_id');

        // User Login, should create dealership in system.
        $dealership = DealerShips::where('dfx_id', '=', $dealer_id)->first();
        if (is_null($dealership)) {
            return response()->json([
                        'status' => 'error',
                        'declaration' => 'dealership_not_found',
                        'payload' => ['message' => 'Sorry, dealer not found. Please try again.'],
                            ], 404);
        }

        // Scope Headers
        $scope_header = json_encode([
            'DealerId' => $dealer_id,
            'CultureCode' => $request->input('culture_code'),
            'Environment' => config('dfx.environment'),
        ]);

        // Params
        $params = [
            'headers' => [
                'Authorization' => $request->header('Authorization'),
                'Scope' => $scope_header,
            ]
        ];

        try {
            $response = $this->client->request('GET', "/vehicle/DmsSearch/$vin", $params);
            $decoded_response = json_decode($response->getBody()->getContents());

            if (is_null($decoded_response->vehicle->id)) {
                return response()->json([
                            'status' => 'error',
                            'declaration' => 'vin_not_found',
                            'payload' => ['message' => 'Invalid VIN'],
                                ], 404);
            }
            $create_manufacturer = [
                'dfx_id' => $decoded_response->vehicle->manufacturerId,
                'dealership_id' => $dealership->id,
                'name' => $decoded_response->vehicle->manufacturerName
            ];
            $manufacturer = Manufacturers::firstOrCreate($create_manufacturer, $create_manufacturer);
            if (is_null($manufacturer)) {

                return response()->json([
                            'status' => 'error',
                            'declaration' => 'manufacturer_not_found',
                            'payload' => ['message' => 'Sorry, manufacturer not found. Please try again.'],
                                ], 404);
            }
            //Generate question for this manufacturer if question is not exists            
            $questionExists = $manufacturer->questions()->get()->toArray();
            if (empty($questionExists)) {
                $customer = new CustomerController();
                $customer->copyQuestion($manufacturer->id);
            }

            // TODO: Database transaction.

            $create_vehicle = [
                'vin' => $vin,
                'image' => $decoded_response->vehicle->imageUrl,
                'dfx_id' => $decoded_response->vehicle->id,
                'model' => $decoded_response->vehicle->model,
                'year' => $decoded_response->vehicle->year,
                'transmissions' => $decoded_response->vehicle->transmission,
                'cylinder' => $decoded_response->vehicle->vehicleCylinder,
                'drive' => $decoded_response->vehicle->drive,
                'color' => $decoded_response->vehicle->color,
            ];
            $vehicle = Vehicles::updateOrCreate(['vin' => $vin], $create_vehicle);

            $create_vehicle_relation = [
                'vehicle_id' => $vehicle->id,
                'dealer_id' => $dealership->id,
                'manufacturer_id' => $manufacturer->id
            ];
            $vehicle_relation = DealerVehicle::firstOrCreate($create_vehicle_relation, $create_vehicle_relation);

            return response()->json([
                        'status' => 'success',
                        'declaration' => 'vehicle_found',
                        'payload' => $decoded_response,
                            ], 200);
        } catch (ConnectException $e) {
            $log_message = ErrorCodes::GATEWAY_TIMEOUT . ' : ' . $e->getMessage();
            throw new DfxException(ErrorCodes::GATEWAY_TIMEOUT, 'gateway_timeout', $log_message, 504);
        } catch (RequestException $e) {
            /*
              @Notes : We have written this code here because for invalid vin status code is coming 400 from dfx and we override 400 as "Oops! Something went wrong'"
              and mobile team require message "Invalid VIN. Please try another VIN" .
             */
            $message = $e->getResponse()->getBody()->getContents();
            if (stripos(strtolower(trim($message)), 'invalid vin') !== false) {
                $status_code = 404;
                $log_message = $e->getResponse()->getStatusCode() . ' : ' . $message;
                throw new DfxException(ErrorCodes::DFX_NOTFOUND, 'invalid_request', $log_message, $status_code);
            } else {
                DfxException::dfxExceptionHandler($e);
            }
        }
    }

    /**
     * Get vehicle makes.
     * Makes GET request to '/vehicle/make'
     *
     * @param   Request $request
     * @return  \Illuminate\Http\Response
     * @author  Amandeep Singh <amandeep@unoapp.com>
     * @version 0.0.1
     */
    public function getMakes(Request $request) {
        // TODO: Pass scope header (optional).                
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
            // 'Scope' => $request->header('Scope'),
            ]
        ];

        try {
            $response = $this->client->request('GET', "/vehicle/make", $params);
            $makes = json_decode($response->getBody()->getContents());

            $payload = [];
            foreach ($makes->makes as $make) {
                $payload[] = [
                    'id' => $make->manufacturerId,
                    'name' => $make->name,
                ];
                // Will remove query from loop later on
                $manufacturer = $dealership->manufacturers()->firstOrCreate(['dfx_id' => $make->manufacturerId, 'dealership_id' => $dealership->id, 'name' => $make->name], ['dfx_id' => $make->manufacturerId, 'dealership_id' => $dealership->id, 'name' => $make->name]);
            }
            return response()->json([
                        'status' => 'success',
                        'declaration' => 'makes_found',
                        'payload' => $payload,
                            ], 200);
        } catch (ConnectException $e) {
            $log_message = ErrorCodes::GATEWAY_TIMEOUT . ' : ' . $e->getMessage();
            throw new DfxException(ErrorCodes::GATEWAY_TIMEOUT, 'gateway_timeout', $log_message, 504);
        } catch (RequestException $e) {
            DfxException::dfxExceptionHandler($e);
        }
    }

    /**
     * Fetch years of make.
     * Makes GET request to 'vehicle/make/:make_id/year'
     * 
     * @param   Request $request
     * @param   integer  $make_id
     * @return  \Illuminate\Http\Response
     * @author  Amandeep Singh <amandeep@unoapp.com>
     * @version 0.0.1
     */
    public function getYearsOfMakes(Request $request, $make_id) {
        // TODO: Pass scope header (optional).
        $params = [
            'headers' => [
                'Authorization' => $request->header('Authorization'),
            // 'Scope' => $request->header('Scope'),
            ]
        ];

        try {
            $response = $this->client->request('GET', "/vehicle/make/$make_id/year", $params);
            $years = json_decode($response->getBody()->getContents());

            $payload = [];
            foreach ($years->years as $year) {
                $payload[] = $year;
            }
            return response()->json([
                        'status' => 'success',
                        'declaration' => 'years_found',
                        'payload' => $payload,
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
     * Makes GET request to 'vehicle/make/:make_id/year/:year/model'
     * 
     * @param   Request $request
     * @return  \Illuminate\Http\Response
     * @author  Amandeep Singh <amandeep@unoapp.com>
     * @version 0.0.1
     */
    public function getModels(Request $request) {
        // TODO: Update validations.
        $this->validate($request, [
            'make_id' => 'required|integer|min:1',
            'year' => 'required|integer|min:1',
        ]);

        $make_id = $request->input('make_id');
        $year = $request->input('year');

        // TODO: Pass scope header (optional).
        $params = [
            'headers' => [
                'Authorization' => $request->header('Authorization'),
            // 'Scope' => $request->header('Scope'),
            ]
        ];

        try {
            $response = $this->client->request('GET', "/vehicle/make/$make_id/year/$year/model", $params);
            $models = json_decode($response->getBody()->getContents());

            $payload = [];
            foreach ($models->models as $model) {
                $payload[] = [
                    'name' => $model->name,
                    'image' => $model->imageUrl,
                ];
            }
            return response()->json([
                        'status' => 'success',
                        'declaration' => 'models_found',
                        'payload' => $payload,
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
     * Makes GET request to 'vehicle/make/:make_id/year/:year/model/:model_name/transmission'
     * 
     * @param   Request $request
     * @return  \Illuminate\Http\Response
     * @author  Amandeep Singh <amandeep@unoapp.com>
     * @version 0.0.1
     */
    public function getTransmission(Request $request) {
        // TODO: Update validations.
        $this->validate($request, [
            'make_id' => 'required|integer|min:1',
            'year' => 'required|integer|min:1',
            'model_name' => 'required|string',
        ]);

        $make_id = $request->input('make_id');
        $year = $request->input('year');
        $model_name = $request->input('model_name');

        // TODO: Pass scope header (optional).
        $params = [
            'headers' => [
                'Authorization' => $request->header('Authorization'),
            // 'Scope' => $request->header('Scope'),
            ]
        ];

        try {
            $response = $this->client->request('GET', "/vehicle/make/$make_id/year/$year/model/$model_name/transmission", $params);
            $transmissions = json_decode($response->getBody()->getContents());

            $payload = [];
            foreach ($transmissions->transmissions as $transmission) {
                $payload[] = [
                    'name' => $transmission->name,
                    'cylinders' => $transmission->vehicleCylinders,
                ];
            }

            return response()->json([
                        'status' => 'success',
                        'declaration' => 'transmission_found',
                        'payload' => $payload,
                            ], 200);
        } catch (ConnectException $e) {
            $log_message = ErrorCodes::GATEWAY_TIMEOUT . ' : ' . $e->getMessage();
            throw new DfxException(ErrorCodes::GATEWAY_TIMEOUT, 'gateway_timeout', $log_message, 504);
        } catch (RequestException $e) {
            DfxException::dfxExceptionHandler($e);
        }
    }

    /**
     * Get Main Manufacturer.
     * 
     * 
     * @param   Request $request
     * @return  \Illuminate\Http\Response
     * @author  Amit panchal <amit@unoindia.co>
     * @version 0.0.1
     */
    public function GetMainManufacturer(Request $request) {

        $manufacturer = MainManufacturer::get();
        if (is_null($manufacturer)) {
            return response()->json([
                        'status' => 'error',
                        'declaration' => 'dealership_not_found',
                        'payload' => ['message' => 'Sorry, Manufacturer not found. Please try again.'],
                            ], 404);
        }
        try {
            return response()->json([
                        'status' => 'success',
                        'declaration' => 'makes_found',
                        'payload' => $manufacturer,
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
     * 
     * @param   Request $request
     * @return  \Illuminate\Http\Response
     * @author  Amit panchal <amit@unoindia.co>
     * @version 0.0.1
     */
    public function getMaintenanceService(Request $request) {

        $this->validate($request, [
            'manufacturerId' => 'required||integer',
            'servicetype' => 'required',
            'model' => 'required',
            'year' => 'required|integer',
            'transmission' => 'required',
            'engine' => 'required',
            'driveTrain' => 'required',
            'mileage' => 'required|integer|min:1'
        ]);

        $manufacturerId = $request->input('manufacturerId');
        $model = $request->input('model');
        $year = $request->input('year');
        $transmission = $request->input('transmission');
        $engine = $request->input('engine');
        $driveTrain = $request->input('driveTrain');
        $serviceType = $request->input('servicetype');


        $params = [
            'headers' => [
                'Authorization' => $request->header('Authorization'),
                'Scope' => $request->header('Scope'),
            ]
        ];
        try {
            $url = "schedule/maintenanceservice/$serviceType/$manufacturerId/$year/$model/$transmission/$engine/$driveTrain";
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
    
    public function getOcrContent(Request $request) {


        $this->validate($request, [
            'file' => 'required|file',
            'image_url' => 'nullable|string',
        ]);

        $file = $request->file('file');
        
        try {
                            
            $business_id = 1; //UNOapp
            $folder_id = 20; //survey-images

            $url = config('survey.resources_endpoint') . "/resources/business_file/$business_id/$folder_id";
            $request_type = "POST";
            $client = new GuzzleHttpClient();
            $params = [
                'multipart' => [
                    [
                        'contents' => fopen($file->path(), 'r'),
                        'name' => 'file',
                    ]
                ],
            ];
            $client_request = $client->request($request_type, $url, $params);
            $client_response = json_decode($client_request->getBody()->getContents(), true);

            if ($client_response['status'] != 'success') {
                $return = [
                    'declaration' => 'gateway_timeout',
                    'status' => 'error',
                    'payload' => [
                        'message' => $e->getMessage(),
                    ],
                ];
                return response($return, 504);
            }

            $file_url = $client_response['payload']['file']['url'];
            
            $ocr_params = [
                'headers' => [
                    'app-id' => config('dfx.ocr_app_id'),
                    'app-secret' => config('dfx.ocr_app_secret'),
                ],
                'json' => [
                    'image' => $file_url
                ],
            ];

            $ocr_endpoint = config('dfx.ocr_protocol') . '://' . config('dfx.ocr_domain');
            $request_url = $ocr_endpoint . '/api/v1/ocr';

            $ocr = new GuzzleHttpClient();
            $ocr_request = $ocr->request('POST', $request_url, $ocr_params);
            $ocr_response = json_decode($ocr_request->getBody()->getContents(), true);

            if (!array_key_exists('text', $ocr_response)) {
                return response([
                    'declaration' => 'ocr_content_not_found',
                    'status' => 'error',
                    'payload' => [
                        'message' => $ocr_response->getMessage(),
                    ],
                        ], 404);
            }

            $arr = array();
            $vin_str = '';
            preg_match_all('/\b(?![0-9]+\b)(?![a-z]+\b)[0-9a-z]+\b/i', $ocr_response['text'], $arr);

            if (!is_null($arr)) {                
                foreach ($arr[0] as $key => $value) {
                    if (strlen($value) == 17) {                        
                        $vin_str  = $value;
                    }
                }
            }

            if (empty($vin_str)) {
                return response([
                    'declaration' => 'vin_not_found',
                    'status' => 'error',
                    'payload' => [
                        'message' => 'VIN not found please try again.',
                    ],
                        ], 404);
            }
                        
            return response([
                'declaration' => 'vin_found',
                'status' => 'success',
                'payload' => [
                    'vin' => $vin_str,
                ],
                    ], 200);
            
        } catch (ConnectException $e) {
            $return = [
                'declaration' => 'gateway_timeout',
                'status' => 'error',
                'payload' => [
                    'message' => $e->getMessage(),
                ],
            ];
            return response($return, 504);
        } catch (RequestException $e) {
            return response($e->getResponse()->getBody()->getContents(), $e->getResponse()->getStatusCode())->header('Content-Type', $e->getResponse()->getHeader('Content-Type'));
        }
    }

}
