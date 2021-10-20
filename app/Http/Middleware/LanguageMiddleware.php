<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\App;

class LanguageMiddleware {

    public function handle($request, Closure $next) {
        $language = new \stdClass();
        if (strlen($request->get('locale')) > 1) {
            $language->locale = $request->get('locale');
        } else {
            $language->locale = 'en';
        }
        App::singleton('languageSelector', function () use ($language) {
            return $language;
        });
        return $next($request);
    }

}
