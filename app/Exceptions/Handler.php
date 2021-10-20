<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Laravel\Lumen\Exceptions\Handler as ExceptionHandler;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\Exceptions\DfxException;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that should not be reported.
     *
     * @var array
     */
    protected $dontReport = [
        HttpException::class,
        ModelNotFoundException::class,
        ValidationException::class,
    ];

    /**
     * Report or log an exception.
     *
     * This is a great spot to send exceptions to Sentry, Bugsnag, etc.
     *
     * @param  \Exception  $e
     * @return void
     */
    public function report(Exception $e)
    {
        parent::report($e);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Exception  $e
     * @return \Illuminate\Http\Response
     */
    public function render($request, Exception $e)
    {
        // HttpExceptions
        if ($e instanceof HttpException) {
            // Invalid routes
            return response()->json([
                'status' => 'error',
                'declaration' => 'route_not_found',
                //'payload' => [],
            ], 404);
        }        
        
        // Validation Exception
        if ($e instanceof ValidationException) {            
            
            $errorMsg = [];
            $errorArr = $e->errors();
            //@NOTES : if we use directly this than always phone number validation digits:10 always making issue as it is not returning in expected format it return in two dimensional array which cause issue.
            if (is_array($errorArr)) {
                foreach ($errorArr as $key => $errorArr) {
                    $errorMsg = $errorArr;
                }
            } else {
                $errorMsg = $errorArr;
            }            
            return response()->json([
                'status'      => 'error',
                'declaration' => 'invalid_input',
                'payload'     => [
                                    'message'=>$errorMsg[0]
                                 ],
            ], 422);
        }

        // DFX Exception
        if ($e instanceof DfxException) {
            return response()->json([
                'status' => 'error',
                'declaration' => $e->getDeclaration(),
                'payload' => [
                    'message'=> $e->getErrorMessage(),
                    'log_message' => $e->getMessage(),
                 ],
            ], $e->getRequestCode());
        }
        
        return parent::render($request, $e);
    }
}
