<?php
use Monolog\Logger;

require_once __DIR__.'/../vendor/autoload.php';

if (trim(env('APP_ENV')) != 'testing') {
    /* To calculate execution time of each request. */
    DEFINE('REQUEST_START_TIME', microtime(true));
}

try {
    (new Dotenv\Dotenv(__DIR__.'/../'))->load();
} catch (Dotenv\Exception\InvalidPathException $e) {
    //
}

/*
|--------------------------------------------------------------------------
| Create The Application
|--------------------------------------------------------------------------
|
| Here we will load the environment and create the application instance
| that serves as the central piece of this framework. We'll use this
| application as an "IoC" container and router for this framework.
|
*/

$app = new Laravel\Lumen\Application(
    realpath(__DIR__.'/../')
);

 $app->withFacades();

 $app->withEloquent();

/*
|--------------------------------------------------------------------------
| Register Container Bindings
|--------------------------------------------------------------------------
|
| Now we will register a few bindings in the service container. We will
| register the exception handler and the console kernel. You may add
| your own bindings here if you like or you can make another file.
|
*/

$app->singleton(
    Illuminate\Contracts\Debug\ExceptionHandler::class,
    App\Exceptions\Handler::class
);

$app->singleton(
    Illuminate\Contracts\Console\Kernel::class,
    App\Console\Kernel::class
);

/*
|--------------------------------------------------------------------------
| Register Middleware
|--------------------------------------------------------------------------
|
| Next, we will register the middleware with the application. These can
| be global middleware that run before and after each request into a
| route or middleware that'll be assigned to some specific routes.
|
*/

$app->middleware([
    App\Http\Middleware\CorsMiddleware::class,
    App\Http\Middleware\Logging::class,
    // App\Http\Middleware\ApplicationMiddleware::class,
]);


$app->routeMiddleware([
    'Auth' => App\Http\Middleware\Authenticate::class,
    'DfxAuth' => App\Http\Middleware\DfxToken::class,
    'DmpiAuth' => App\Http\Middleware\DmpiToken::class,
    // 'userAuthMiddleware' => App\Http\Middleware\UserMiddleware::class // I think, we're not using this.
]);

/*
|--------------------------------------------------------------------------
| Register Service Providers
|--------------------------------------------------------------------------
|
| Here we will register all of the application's service providers which
| are used to bind services into the container. Service providers are
| totally optional, so you are not required to uncomment this line.
|
*/

$app->register(App\Providers\AppServiceProvider::class);
$app->register(App\Providers\AuthServiceProvider::class);
$app->register(App\Providers\HelperServiceProvider::class);
$app->register(App\Providers\EventServiceProvider::class);
$app->register(Illuminate\Filesystem\FilesystemServiceProvider::class);
$app->register(Illuminate\Mail\MailServiceProvider::class);

/*
|--------------------------------------------------------------------------
| Load The Application Routes
|--------------------------------------------------------------------------
|
| Next we will include the routes file so that they can all be added to
| the application. This will provide all of the URLs the application
| can respond to, as well as the controllers that may handle them.
|
*/

$app->router->group([
    'namespace' => 'App\Http\Controllers',
], function ($router) {
    require __DIR__.'/../routes/web.php';
    require __DIR__.'/../routes/user.php';
    require __DIR__.'/../routes/sales.php';
    require __DIR__.'/../routes/dmpi.php';
    require __DIR__.'/../routes/term.php';
});

/*
|--------------------------------------------------------------------------
| Register Config files
|--------------------------------------------------------------------------
|
| Here we will register all of the application's config files which
| are used to set up defaults for the application's various services.
|
*/

$app->configure('dfx');
$app->configure('dmpi');
$app->configure('mail');
$app->configure('filesystems');
$app->configure('services');
$app->configure('survey');

$app->configureMonologUsing(function ($monolog) {
    $log_level = Logger::INFO;
    $debug_log = config('dfx.debug_log');
    $stdout_log = config('dfx.stdout_log');

    if ($debug_log) {
        $log_level = Logger::DEBUG;
    }
    
    // log to STDOUT if STDOUT_LOG set
    if ($stdout_log) {
        $monolog->pushHandler((new Monolog\Handler\StreamHandler('php://stdout', $log_level))
        ->setFormatter(new Monolog\Formatter\LineFormatter(NULL, NULL, TRUE, TRUE)));
    } else { // else log to file
        $monolog->pushHandler((new Monolog\Handler\StreamHandler(storage_path('logs/lumen-dfxgateway-' . \Illuminate\Support\Carbon::now()->format("Y-m-d") . '.log'),
            $log_level))
            ->setFormatter(new Monolog\Formatter\LineFormatter(NULL, NULL, TRUE, TRUE)));
    }

    return $monolog;
});


return $app;
