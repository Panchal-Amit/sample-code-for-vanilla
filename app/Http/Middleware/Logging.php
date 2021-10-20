<?php

namespace App\Http\Middleware;

use Closure;
use GuzzleHttp\Client as GuzzleHttpClient;
use Exception;

class Logging
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {             
        if (trim(env('APP_ENV')) == 'testing') {
            return $next($request);
        }

        $this->client = new GuzzleHttpClient([
            'base_uri' => 'http://logs.unoapp.io',
        ]);

        $record['request'] = [
            'method'     => $request->getRealMethod(),
            'url'        => $request->fullUrl(),
            'headers'    => $request->header(),
            'params'     => $request->all(),
            'client_ip'  => $request->ip(),
        ];

        $response   = $next($request);

        $record['elapsed_time'] = round(1000*(microtime(true) - REQUEST_START_TIME),3)."ms";
        $record['response'] = $response->original;

        $params = [
            'json' => $record,
            'headers' => [
                'app-id' => '7B456A22EDAA7548D2CE3843E736D',
                'app-secret' => '627256DCBA2A8EDF16F75187A25B8',
            ],
        ];

        try {
            $this->client->request('PUT', "/log", $params);
        } catch (Exception $e) {
            return $response;
        }
        

        return $response;
    }
}
