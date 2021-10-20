<?php

return [

    /*
      |--------------------------------------------------------------------------
      | Survey .env defaults
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

    'survey_protocol' => env('SURVEY_PROTOCOL', 'http'),
    'survey_domain' => env('SURVEY_DOMAIN', 'survey-engine-dfx.dev.api.unoapp.io'),
    'app_id' => env('APP_ID', 'FE84OyWFLfF6D913Blmhdft5yu6ETcd1'),
    'app_secret' => env('APP_SECRET', 'wpyq0C5byUOzwzZFCOxtee5W99HPf1G65urjHEdherImVZnvsn'),
    'form_access_code' => env('FORM_ACCESS_CODE', '5c00d2a54f4e4'),
    'survey_location' => env('SURVEY_LOCATION', '5'),
    'resources_endpoint' => env('RESOURCES_ENDPOINT', 'https://resources.dev.api.unoapp.io')
];
