<?php

namespace App\Http\Middleware;


use App\Models\Application;
use App\Models\ApplicationSecrets;
use App\Models\ApplicationVersion;
use Closure;
use Illuminate\Support\Facades\App;
use Log;
use RestResponseFactory;

/**
 * Class AppIdMiddleware
 *
 * @package App\Http\Middleware
 */
class ApplicationMiddleware
{
    /**
     * Handle an incoming request and check if the appid and app secret are valid.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure $next
     *
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        Log::info("Application ID Middleware");
        if ($request->path() == "/") {
            return $next($request);

        }
        $app_id = $request->header('app-id'); //Input::get('appId');
        $app_secret = $request->header('app-secret'); //Input::get('appId');
        $app_version = $request->header('app-version'); //Input::get('appId');
        $app_bundle = $request->header('app-bundle'); //Input::get('appId');
        $app_platform = $request->header('app-platform'); //Input::get('appId');

        if (empty($app_id) || empty($app_secret)) {
            $resp = RestResponseFactory::badrequest("If you donot have app-id and app-secret, please contact UnoApp", "app-id or app-secret is required.");
            return $resp->toJSON();
        }

        $application = Application::where('app_id', $app_id)
            ->where('app_secret', $app_secret)
            ->where('status', 1)->first();
        if (!count($application)) {
            Log::info("Application ID Middleware Declined");

            $resp = RestResponseFactory::badrequest("If you donot have app-id and app-secret, please contact UnoApp", "app-id or app-secret is invalid.");

            return $resp->toJSON();
        }

        App::singleton('Application',
            function () use ($application) {
                return $application;
            });

        $version = ApplicationVersion::where('application_id', $application->id)
            ->where('version', $app_version)
            ->where('bundle', $app_bundle)
            ->where('platform', $app_platform)->first();

        if(!count($version)){
            Log::info("Application version not found");

            $resp = RestResponseFactory::badrequest((object)[], "Application version not active for this platform");

            return $resp->toJSON();
        }
        if($version->status==0){
            Log::info("Application version status 0");
            $resp = RestResponseFactory::redirect($version, "Application version not active.");
            return $resp->toJSON();
        }

        return $next($request);
    }
}
