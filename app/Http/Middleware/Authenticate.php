<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Contracts\Auth\Factory as Auth;
use Illuminate\Support\Facades\App;
use App\Models\User\User;
use App\Models\User\AuthToken;

//use Illuminate\Support\Facades\DB;

class Authenticate {

    /**
     * The authentication guard factory instance.
     *
     * @var \Illuminate\Contracts\Auth\Factory
     */
    protected $auth;

    /**
     * Create a new middleware instance.     
     * @param  \Illuminate\Contracts\Auth\Factory  $auth
     * @return void
     */
    public function __construct(Auth $auth) {
        $this->auth = $auth;
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
        $auth_token = $request->header('auth-token');

        if (is_null($auth_token)) {
            return response()->json([
                        'status' => 'error',
                        'declaration' => 'auth_token_missing',
                            //'payload' => [],
                            ], 400);
        }
        $authObj = AuthToken::where('token', '=', $auth_token)->first();
        if (is_null($authObj)) {
            return response()->json([
                        'status' => 'error',
                        'declaration' => 'auth_token_invalid',
                        'payload' => ["message" => "Invalid session token"]
                            ], 401);
        }
        if ($authObj->isExpired()) {
            return response()->json([
                        'status' => 'error',
                        'declaration' => 'auth_token_expired',
                        'payload' => ["message" => "Expired session token"]
                            ], 401);
        }
        $authUser = User::where('id', $authObj->user_id)->first();

        App::singleton('AuthenticatedUser', function () use ($authUser) {
            return $authUser;
        });
        return $next($request);
    }

}
