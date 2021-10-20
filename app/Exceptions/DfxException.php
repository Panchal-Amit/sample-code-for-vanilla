<?php

 /**
  * DfxException.php
  */

namespace App\Exceptions;

use App\Helpers\ErrorCodes;

 /**
  * DfxException class
  *
  * @author  Amandeep Singh <amandeep@unoapp.com>
  * @version 0.0.6
  */
class DfxException extends \Exception
{
    /**
     * Response declaration string
     *
     * @var     string
     * @author  Amandeep Singh <amandeep@unoapp.com>
     */
    private $declaration;

    /**
     * Error Codes.
     *
     * @var     integer
     * @author  Amandeep Singh <amandeep@unoapp.com>
     */
    private $dfx_error_code;

    /**
     * Response request status code
     *
     * @var     string
     * @author  Amandeep Singh <amandeep@unoapp.com>
     */
    private $request_code;

    /**
     * Custom log_message of request.
     *
     * @var     string
     * @author  Amandeep Singh <amandeep@unoapp.com>
     */
    private $log_message;

    /**
     * Constructor
     *
     * @param   string  $log_message
     * @param   string  $declaration
     * @return  void
     * @author  Amandeep Singh <amandeep@unoapp.com>
     */
    public function __construct($dfx_error_code, $declaration, $log_message, $request_code)
    {
        $this->declaration = $declaration;
        $this->dfx_error_code = $dfx_error_code;
        $this->request_code = $request_code;
        $this->log_message = $log_message;
        
        parent::__construct($log_message);
    }

    /**
     * Get declaration
     *
     * @return  string
     * @author  Amandeep Singh <amandeep@unoapp.com>
     */
    public function getDeclaration()
    {
        return $this->declaration;
    }

    /**
     * Get request code
     *
     * @return  string
     * @author  Amandeep Singh <amandeep@unoapp.com>
     */
    public function getRequestCode()
    {
        return $this->request_code;
    }

    /**
     * Get error message
     *
     * @return  string
     * @author  Amandeep Singh <amandeep@unoapp.com>
     */
    public function getErrorMessage()
    {   
        $return = (isset(ErrorCodes::ERROR_MESSAGES[$this->dfx_error_code]) ? ErrorCodes::ERROR_MESSAGES[$this->dfx_error_code] : $this->getMessage());
        return $return;
    }
    /**
     * To handle dfx exception     
     *
     * @return  void
     * @author  Amit Panchal <amit@unoindia.co>
     * @version 0.0.1
     */
    public static function dfxExceptionHandler($exception) {
        $status_code = $exception->getResponse()->getStatusCode();

        switch ($status_code) {
            case 400 :
                $log_message = ErrorCodes::DFX_BADREQUEST . ' : ' . $exception->getResponse()->getBody()->getContents();
                throw new DfxException(ErrorCodes::DFX_BADREQUEST, 'invalid_request', $log_message, $status_code);
                break;
            case 401:
                $log_message = ErrorCodes::DFX_UNAUTHORIZED . ' : ' . $exception->getResponse()->getBody()->getContents();
                throw new DfxException(ErrorCodes::DFX_UNAUTHORIZED, 'invalid_request', $log_message, $status_code);
                break;
            case 500 :
                $log_message = ErrorCodes::DFX_SERVERERROR . ' : ' . $exception->getResponse()->getBody()->getContents();
                throw new DfxException(ErrorCodes::DFX_SERVERERROR, 'invalid_request', $log_message, $status_code);
                break;
            case 403 :
                $log_message = ErrorCodes::DFX_UNAUTHORIZED_RESOURCE . ' : ' . $exception->getResponse()->getBody()->getContents();
                throw new DfxException(ErrorCodes::DFX_UNAUTHORIZED_RESOURCE, 'invalid_request', $log_message, $status_code);
                break;
        }
    }

}
