<?php

	namespace App\Http\Middleware;

	use App\Models\User\AuthToken;
    use App\Models\User\User;
    use Closure;
	use Illuminate\Support\Facades\App;
    use Log;
    use RestResponseFactory;

	class UserMiddleware
	{
		/**
		 * Handle an incoming request and check auth token.
		 *
		 * @param  \Illuminate\Http\Request $request
		 * @param  \Closure                 $next
		 *
		 * @return mixed
		 */
		public function handle($request , Closure $next)
		{
            Log::info("UserMiddleware");

            $auth_id = $request->header('auth-token');
			if (empty($auth_id)) {
				$responseObj = RestResponseFactory::unauthorized((object) array() , "Auth token is required.");
				
				return $responseObj->toJSON();
			}

            /** @var TYPE_NAME $authObj */
            $authObj =AuthToken::where('token','=',$auth_id)->first();

			Log::info("searching for  auth token");

			if (!$authObj) {
                Log::info("Auth token not found");
                $responseObj = RestResponseFactory::unauthorized((object) array() , "Invalid/Expired session token.");
                return $responseObj->toJSON();

			}
			if ($authObj->isExpired()) {
                Log::info("Token expired !");
                $responseObj = RestResponseFactory::unauthorized((object) array() , "Invalid/Expired session token.");
                return $responseObj->toJSON();
			}


			$authUser = User::where('id',$authObj->user_id)->first();

			App::singleton('AuthenticatedUser' ,
				function () use ($authUser) {
					return $authUser;
				});

            Log::info("User Auth middleware passed");


            return $next($request);
		}
	}
