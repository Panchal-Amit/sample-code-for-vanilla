<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use GuzzleHttp\Client as GuzzleHttpClient;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ConnectException;
use App\Models\DealerShips;
use App\Models\User\User;
use App\Exceptions\DfxException;
use App\Helpers\ErrorCodes;

/**
 * Service Controller
 */
class ServiceController extends Controller {

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
            'base_uri' => config('dmpi.dmpi_protocol') . '://' . config('dmpi.dmpi_domain'),
        ]);
    }

    /**
     * Get Service History
     * Makes POST request to '/document/servicehistory/inspection/{vin}'
     * This call giving error
     * @return  void
     * @author  Amit Panchal <amit@unoindia.co>
     * @version 0.0.1
     */
    public function getService(Request $request) {

        $this->validate($request, [
            'servicehistorytype' => 'required',
            'dealer_id' => 'required',
            'culture_code' => 'required',
            'vin' => 'required'
        ]);

        $dealer_id = $request->input('dealer_id');
        $serviceHistoryType = $request->input('servicehistorytype');
        $vin = $request->input('vin');

        $dealership = DealerShips::where('dfx_id', '=', $dealer_id)->first();
        if (is_null($dealership)) {
            return response()->json([
                        'status' => 'error',
                        'declaration' => 'dealership_not_found',
                        'payload' => ['message' => 'Sorry, dealer not found. Please try again.'],
                            ], 404);
        }

        $dfxToken = config('dfx.dfx_token');
        // Scope Headers
        $scope_header = json_encode([
            'DealerId' => $dealer_id,
            'CultureCode' => $request->input('culture_code'),
            'Environment' => config('dfx.environment'),
        ]);

        // TODO: Pass scope header (optional).
        $params = [
            'headers' => [
                'Authorization' => $dfxToken->token_type . ' ' . $dfxToken->access_token,
                'Scope' => $scope_header
            ]
        ];
        try {
            $this->client = new GuzzleHttpClient([
                'base_uri' => config('dfx.dfx_protocol') . '://' . config('dfx.dmpi_domain'),
            ]);
            $response = $this->client->request('GET', "/document/servicehistory/inspection/$vin", $params);
            $service = json_decode($response->getBody()->getContents());
            // inspection                        
            return response()->json([
                        'status' => 'success',
                        'declaration' => 'service_history_found',
                        'payload' => $service,
                            ], 200);
        } catch (ConnectException $e) {
            $log_message = ErrorCodes::GATEWAY_TIMEOUT . ' : ' . $e->getMessage();
            throw new DfxException(ErrorCodes::GATEWAY_TIMEOUT, 'gateway_timeout', $log_message, 504);
        } catch (RequestException $e) {
            DfxException::dfxExceptionHandler($e);
        }
    }

    /**
     * Get Write Up PDF
     * Makes POST request to '/document/writeup/{id}'
     * This call giving error
     * @return  void
     * @author  Amit Panchal <amit@unoindia.co>
     * @version 0.0.1
     */
    public function getWriteUp(Request $request) {
        $this->validate($request, [
            'customer_id' => 'required|integer',
            'dealer_id' => 'required',
            'culture_code' => 'required'
        ]);

        $customerId = $request->input('customer_id');
        $dealer_id = $request->input('dealer_id');

        $dfxToken = config('dfx.dfx_token');
        // Scope Headers
        $scope_header = json_encode([
            'DealerId' => $dealer_id,
            'CultureCode' => $request->input('culture_code'),
            'Environment' => config('dfx.environment'),
        ]);

        // TODO: Pass scope header (optional).
        $params = [
            'headers' => [
                'Authorization' => $dfxToken->token_type . ' ' . $dfxToken->access_token,
                'Scope' => $scope_header
            ]
        ];
        try {
            $user = User::where('dfx_id', '=', $customerId)->first();
            if (is_null($user)) {
                return response()->json([
                            'status' => 'error',
                            'declaration' => 'user_not_found',
                            'payload' => ['Sorry, customer not found. Please try again.'],
                                ], 404);
            }

            $dealership = DealerShips::where('dfx_id', '=', $dealer_id)->first();
            if (is_null($dealership)) {
                return response()->json([
                            'status' => 'error',
                            'declaration' => 'dealership_not_found',
                            'payload' => ['message' => 'Sorry, dealer not found. Please try again.'],
                                ], 404);
            }
            $this->client = new GuzzleHttpClient([
                'base_uri' => config('dfx.dfx_protocol') . '://' . config('dfx.dmpi_domain'),
            ]);
            $response = $this->client->request('GET', "/document/writeup/$customerId", $params);
            $url = json_decode($response->getBody()->getContents());

            return response()->json([
                        'status' => 'success',
                        'declaration' => 'enrolledInIceApp_updated',
                        'payload' => $url
                            ], 200);
        } catch (ConnectException $e) {
            $log_message = ErrorCodes::GATEWAY_TIMEOUT . ' : ' . $e->getMessage();
            throw new DfxException(ErrorCodes::GATEWAY_TIMEOUT, 'gateway_timeout', $log_message, 504);
        } catch (RequestException $e) {
            DfxException::dfxExceptionHandler($e);
        }
    }

    /**
     * Get DMPI service
     * Makes POST request to '/services/:token'
     * 
     * @return  void
     * @author  Amit Panchal <amit@unoindia.co>
     * @version 0.0.1
     */
    public function getDmpiService(Request $request) {

        $this->validate($request, [
            'client_token' => 'required'
        ]);
        $dmpiToken = config('dmpi.dmpi_token');

        $params = [
            'headers' => [
                'Authorization' => $dmpiToken->token_type . ' ' . $dmpiToken->access_token
            ]
        ];

        $clientToken = $request->input('client_token');

        try {

            $response = $this->client->request('GET', "/services/$clientToken", $params);
            $service = json_decode($response->getBody()->getContents());
            // inspection                        
            return response()->json([
                        'status' => 'success',
                        'declaration' => 'service_found',
                        'payload' => $service,
                            ], 200);
        } catch (ConnectException $e) {
            $log_message = ErrorCodes::GATEWAY_TIMEOUT . ' : ' . $e->getMessage();
            throw new DfxException(ErrorCodes::GATEWAY_TIMEOUT, 'gateway_timeout', $log_message, 504);
        } catch (RequestException $e) {
            DfxException::dfxExceptionHandler($e);
        }
    }

    /**
     * Get DMPI Inspection
     * Makes POST request to '/inspection/:token'
     * 
     * @return  void
     * @author  Amit Panchal <amit@unoindia.co>
     * @version 0.0.1
     */
    public function getInspectionLink(Request $request) {
        $this->validate($request, [
            'client_token' => 'required'
        ]);
        $dmpiToken = config('dmpi.dmpi_token');

        $params = [
            'headers' => [
                'Authorization' => $dmpiToken->token_type . ' ' . $dmpiToken->access_token
            ]
        ];

        $clientToken = $request->input('client_token');

        try {

            $response = $this->client->request('GET', "/inspection/$clientToken", $params);
            $inspection = json_decode($response->getBody()->getContents());

            // inspection                        
            return response()->json([
                        'status' => 'success',
                        'declaration' => 'inspection_found',
                        'payload' => $inspection,
                            ], 200);
        } catch (ConnectException $e) {
            $log_message = ErrorCodes::GATEWAY_TIMEOUT . ' : ' . $e->getMessage();
            throw new DfxException(ErrorCodes::GATEWAY_TIMEOUT, 'gateway_timeout', $log_message, 504);
        } catch (RequestException $e) {
            DfxException::dfxExceptionHandler($e);
        }
    }

    /**
     * Get DMPI MetaData
     * Makes POST request to '/metadata/:token'
     * 
     * @return  void
     * @author  Amit Panchal <amit@unoindia.co>
     * @version 0.0.1
     */
    public function getMetaData(Request $request) {
        $this->validate($request, [
            'client_token' => 'required',
            'lan' => 'required'
        ]);
        $dmpiToken = config('dmpi.dmpi_token');

        $params = [
            'headers' => [
                'Authorization' => $dmpiToken->token_type . ' ' . $dmpiToken->access_token
            ]
        ];

        $clientToken = $request->input('client_token');
        $lang = $request->input('lan');

        try {
            $response = $this->client->request('GET', "/metadata/$clientToken/$lang", $params);
            $metadata = json_decode($response->getBody()->getContents(), true);

            $response = $this->client->request('GET', "/services/$clientToken", $params);
            $service = json_decode($response->getBody()->getContents(), true);
            
            $response = $this->client->request('GET', "/confirmation/$clientToken", $params);
            $advisor = json_decode($response->getBody()->getContents(), true);
            
            $serviceArr = array();
            foreach ($service as $serviceKey => $serviceVal) {
                $serviceArr[$serviceVal['category']][] = $serviceVal;
            }
            $i = 0;
            foreach ($metadata['serviceCategories'] as $serviceKey => $serviceVal) {
                $metadata['serviceCategories'][$i]['items'] = $serviceArr[$serviceVal['id']];
                $i++;
            }

            $recommendation = array();
            foreach ($metadata['serviceCategories'] as $serviceKey => $serviceVal) {
                // This condition is just based on imagination which will be clearify later on
                if ($serviceVal['showOnSummary'] && $serviceVal['showOnInspection']) {
                    $serviceVal['count'] = count($serviceVal['items']);
                    unset($serviceVal['items']);
                    $metadata['serviceRecommendation'][] = $serviceVal;
                }
            }
            $metadata['advisor'] = $advisor;
            
            return response()->json([
                        'status' => 'success',
                        'declaration' => 'metadata_found',
                        'payload' => $metadata,
                            ], 200);
        } catch (ConnectException $e) {
            $log_message = ErrorCodes::GATEWAY_TIMEOUT . ' : ' . $e->getMessage();
            throw new DfxException(ErrorCodes::GATEWAY_TIMEOUT, 'gateway_timeout', $log_message, 504);
        } catch (RequestException $e) {
            DfxException::dfxExceptionHandler($e);
        }
    }

    /**
     * Get DMPI MetaData
     * Makes POST request to '/services/:token/:serviceId'
     * 
     * @return  void
     * @author  Amit Panchal <amit@unoindia.co>
     * @version 0.0.1
     */
    public function getServiceDetail(Request $request) {

        $this->validate($request, [
            'client_token' => 'required',
            'service_id' => 'required'
        ]);
        $dmpiToken = config('dmpi.dmpi_token');

        $params = [
            'headers' => [
                'Authorization' => $dmpiToken->token_type . ' ' . $dmpiToken->access_token
            ]
        ];

        $clientToken = $request->input('client_token');
        $serviceId = $request->input('service_id');

        try {
            $response = $this->client->request('GET', "/services/$clientToken/$serviceId", $params);
            $serviceDetail = json_decode($response->getBody()->getContents());

            return response()->json([
                        'status' => 'success',
                        'declaration' => 'service_found',
                        'payload' => $serviceDetail,
                            ], 200);
        } catch (ConnectException $e) {
            $log_message = ErrorCodes::GATEWAY_TIMEOUT . ' : ' . $e->getMessage();
            throw new DfxException(ErrorCodes::GATEWAY_TIMEOUT, 'gateway_timeout', $log_message, 504);
        } catch (RequestException $e) {
            DfxException::dfxExceptionHandler($e);
        }
    }

    /**
     * Approve Service
     * Makes POST request to '/services/:token'
     * 
     * @return  void
     * @author  Amit Panchal <amit@unoindia.co>
     * @version 0.0.1
     */
    public function approveService(Request $request) {
        $this->validate($request, [
            'client_token' => 'required',
            'approvedServices' => 'required|array',
            'customerSignature' => 'required'
        ]);
        $dmpiToken = config('dmpi.dmpi_token');
        $clientToken = $request->input('client_token');
        $customerSignature = $request->input('customerSignature');
        $approvedServices = $request->input('approvedServices');

        $params = [
            'headers' => [
                'Authorization' => $dmpiToken->token_type . ' ' . $dmpiToken->access_token
            ],
            'json' => ["confirmedServices" => $approvedServices, "customerSignature" => $customerSignature]
        ];
        try {
            $response = $this->client->request('POST', "/services/$clientToken", $params);
            $approveService = json_decode($response->getBody()->getContents());

            return response()->json([
                        'status' => 'success',
                        'declaration' => 'service_approved',
                        'payload' => $approveService,
                            ], 200);
        } catch (ConnectException $e) {
            throw new DfxException(ErrorCodes::GATEWAY_TIMEOUT, 'gateway_timeout', $log_message, 504);
        } catch (RequestException $e) {
            DfxException::dfxExceptionHandler($e);
        }
    }
    /**
     * Calculate Tax
     * Makes POST request to '/tax/:token'
     * 
     * @return  void
     * @author  Amit Panchal <amit@unoindia.co>
     * @version 0.0.1
     */
    public function calculateTax(Request $request) {

        $this->validate($request, [
            'client_token' => 'required',
            'selectedServices' => 'required|array'
        ]);
        $dmpiToken = config('dmpi.dmpi_token');
        $clientToken = $request->input('client_token');
        $services = $request->input('selectedServices');

        $params = [
            'headers' => [
                'Authorization' => $dmpiToken->token_type . ' ' . $dmpiToken->access_token
            ],
            'json' => ['selectedServices' => $services]
        ];
        try {
            $response = $this->client->request('POST', "/tax/$clientToken", $params);
            $tax = json_decode($response->getBody()->getContents());

            return response()->json([
                        'status' => 'success',
                        'declaration' => 'service_found',
                        'payload' => $tax,
                            ], 200);
        } catch (ConnectException $e) {
            $log_message = ErrorCodes::GATEWAY_TIMEOUT . ' : ' . $e->getMessage();
            throw new DfxException(ErrorCodes::GATEWAY_TIMEOUT, 'gateway_timeout', $log_message, 504);
        } catch (RequestException $e) {
            DfxException::dfxExceptionHandler($e);
        }
    }

}
