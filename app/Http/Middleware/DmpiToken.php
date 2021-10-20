<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Contracts\Auth\Factory as Auth;
use Illuminate\Support\Facades\App;
use GuzzleHttp\Client as GuzzleHttpClient;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ConnectException;
use App\Exceptions\DfxException;
use App\Helpers\ErrorCodes;

class DmpiToken {

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
            'base_uri' => config('dmpi.dmpi_protocol') . '://' . config('dmpi.dmpi_domain'),
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
        $dmpiToken = $this->refreshAccessToken($request);
        config(['dmpi.dmpi_token' => $dmpiToken]);
        return $next($request);
    }

    /**
     * To Refresh DMPI Access Token
     * 
     * @return  void
     * @author  Amit Panchal <amit@unoindia.co>
     * @version 0.0.1
     */
    public function refreshAccessToken($request) {
        
        //Need to apply validation on client_token
        $clientToken = $request->input('client_token');
        $params = [
            'form_params' => [
                'grant_type' => 'client_credentials',
                'client_id' => $clientToken,
                'client_secret' => config('dmpi.dmpi_client_secret'),
            ],
        ];
        try {
            $response = $this->client->request('POST', "/oauth/token", $params);
            $decoded_response = json_decode($response->getBody()->getContents());
            return $decoded_response;
        } catch (ConnectException $e) {
            $log_message = ErrorCodes::GATEWAY_TIMEOUT . ' : ' . $e->getMessage();
            throw new DfxException(ErrorCodes::GATEWAY_TIMEOUT, 'gateway_timeout', $log_message, 504);
        } catch (RequestException $e) {
            DfxException::dfxExceptionHandler($e);
        }
    }

    /**
     * To Refresh DMPI Access Token
     * 
     * @return  void
     * @author  Amit Panchal <amit@unoindia.co>
     * @version 0.0.1
     */
    public function refreshClientToken() {
        // Need to generate Client Token  here
    }

}
