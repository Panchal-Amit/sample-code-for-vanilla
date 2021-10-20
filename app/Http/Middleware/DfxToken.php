<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Contracts\Auth\Factory as Auth;
use Illuminate\Support\Facades\App;
use GuzzleHttp\Client as GuzzleHttpClient;

//use Illuminate\Support\Facades\DB;

class DfxToken {

    /**
     * Instance of GuzzleHttpClient
     *
     * @var client
     */
    private $client;

    /**
     * Create a new middleware instance.     
     * @param  \Illuminate\Contracts\Auth\Factory  $auth
     * @return void
     */
    public function __construct(Auth $auth) {
        $this->client = new GuzzleHttpClient([
            'base_uri' => config('dfx.df_protocol') . '://' . config('dfx.df_domain'),
        ]);
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  $guard
     * @return mixed
     */
    public function handle($request, Closure $next, $guard = null) {

        $dfxToken = $this->refreshToken();
        config(['dfx.dfx_token' => $dfxToken]);
        
        return $next($request);
    }

    /**
     * To refresh token
     * @Note : This will be move in another middleware
     * @return  void
     * @author  Amit Panchal <amit@unoindia.co>
     * @version 0.0.1
     */
    public function refreshToken() {
        $params = [
            'form_params' => [
                'grant_type'    => 'client_credentials',
                'client_id'     => config('dfx.client_id'),
                'client_secret' => config('dfx.client_secret'),
                'scope' => config('dfx.scope')
            ],
        ];
        try {            
            $response = $this->client->request('POST', "oauth/token", $params);
            $decoded_response = json_decode($response->getBody()->getContents());
            return $decoded_response;
        } catch (ConnectException $e) {
            $return = [
                'declaration' => 'gateway_timeout',
                'status'      => 'error',
                'payload'     => ['message' => $e->getMessage()],
            ];
            return response($return, 504);
        } catch (RequestException $e) {
            return response()->json([
                        'status'      => 'error',
                        'declaration' => 'invalid_request',
                        'payload'     => json_decode($e->getResponse()->getBody()->getContents()),
                            ], 404);
        }
    }

}
