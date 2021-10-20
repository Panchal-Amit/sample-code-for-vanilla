<?php

namespace App\Helpers;

class ErrorCodes {

    const GATEWAY_TIMEOUT = 3000;
    const DFX_BADREQUEST = 4000;
    const DFX_UNAUTHORIZED = 4001;
    const DFX_NOTFOUND = 4004;
    const DFX_SERVERERROR = 5000;
    const DFX_UNAUTHORIZED_RESOURCE = 4003;
    const UNOAPP_CUSTOM_LOG = 0000;
    
    const ERROR_MESSAGES = [
        3000 => '3000 : Oops! Timeout.',
        4000 => '4000 : Oops! Something went wrong',
        4001 => '4001 : Your session has expired. Please login again.',
        4004 => '4004 : Invalid VIN. Please try another VIN.',
        5000 => '5000 : Oops! Something went wrong.',
        4003 => '4003 : Unauthorized',
    ];

}
