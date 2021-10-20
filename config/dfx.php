<?php

return [

    /*
      |--------------------------------------------------------------------------
      | dfx .env defaults
      |--------------------------------------------------------------------------
      |
      | Here we set up default values for .env so localhost doesn't break
      | just because someone didn't update their own .env file.
      |
      |
      | IMPORTANT :
      |
      | Your .env values will override these default config values, so please
      | make changes there instead so you don't accidentally commit your
      | localhost setup to the repository.
      |
     */

    /* OLD information
    'df_protocol' => env('DF_PROTOCOL', 'https'),
    'df_domain' => env('DF_DOMAIN', 'test2iceapi.dealer-fx.com'),
    'client_id' => env('CLIENT_ID', 'unoAppUser'),
    'client_secret' => env('CLIENT_SECRET', 'unoApp'),
    */
    
    'df_protocol' => env('DF_PROTOCOL', 'https'),
    'df_domain' => env('DF_DOMAIN', 'testcsrviceapi.dealer-fx.com'),
    'client_id' => env('CLIENT_ID', 'ICE_Api_Test'),
    'client_secret' => env('CLIENT_SECRET', 'test1234'),
    'environment' => env('ENVIRONMENT', 'Prod2'),
    'stdout_log' => env('STDOUT_LOG',TRUE),
    'debug_log' => env('DEBUG_LOG',TRUE),
    'scope' => env('SCOPE', 'TEST_IceApiService'),
    'dfx_token' => [],
    'ocr_domain' => env('OCR_DOMAIN', 'uno-vision.dev.api.unoapp.io'),
    'ocr_protocol' => env('OCR_PROTOCOL', 'http'),
    'ocr_app_id' => env('OCR_APP_ID', '9b1a51167d2c538e3815aa33075089298aa2bc30'),
    'ocr_app_secret' => env('OCR_APP_SECRET', 'b5ff7cb09596f31f883d945a6636ac663a846226'),
];
