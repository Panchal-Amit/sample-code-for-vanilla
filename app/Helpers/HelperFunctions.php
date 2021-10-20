<?php
use Illuminate\Support\Facades\Storage;

/**
 * Created by PhpStorm.
 * User: jenish
 * Date: 29-04-2016
 * Time: PM 04:32.
 */
if (!function_exists('config_path')) {

    /**
     * Return the path to config files
     * @param null $path
     * @return string
     */
    function config_path($path = null) {
        return app()->getConfigurationPath(rtrim($path, ".php"));
    }

}
if (!function_exists('public_path')) {

    /**
     * Return the path to public dir
     * @param null $path
     * @return string
     */
    function public_path($path = null) {
        return rtrim(app()->basePath('public/' . $path), '/');
    }

}
if (!function_exists('storage_path')) {

    /**
     * Return the path to storage dir
     * @param null $path
     * @return string
     */
    function storage_path($path = null) {
        return app()->storagePath($path);
    }

}
if (!function_exists('database_path')) {

    /**
     * Return the path to database dir
     * @param null $path
     * @return string
     */
    function database_path($path = null) {
        return app()->databasePath($path);
    }

}
if (!function_exists('resource_path')) {

    /**
     * Return the path to resource dir
     * @param null $path
     * @return string
     */
    function resource_path($path = null) {
        return app()->resourcePath($path);
    }

}

if (!function_exists('asset')) {

    /**
     * Generate an asset path for the application.
     *
     * @param  string $path
     * @param  bool $secure
     * @return string
     */
    function asset($path, $secure = null) {
        return app('url')->asset($path, $secure);
    }

}
if (!function_exists('elixir')) {

    /**
     * Get the path to a versioned Elixir file.
     *
     * @param  string $file
     * @return string
     */
    function elixir($file) {
        static $manifest = null;
        if (is_null($manifest)) {
            $manifest = json_decode(file_get_contents(public_path() . '/build/rev-manifest.json'), true);
        }
        if (isset($manifest[$file])) {
            return '/build/' . $manifest[$file];
        }
        throw new InvalidArgumentException("File {$file} not defined in asset manifest.");
    }

}
if (!function_exists('response')) {

    /**
     * Return a new response from the application.
     *
     * @param  string $content
     * @param  int $status
     * @param  array $headers
     * @return \Symfony\Component\HttpFoundation\Response|\Illuminate\Contracts\Routing\ResponseFactory
     */
    function response($content = '', $status = 200, array $headers = array()) {
        $factory = app('Illuminate\Contracts\Routing\ResponseFactory');
        if (func_num_args() === 0) {
            return $factory;
        }
        return $factory->make($content, $status, $headers);
    }

}
if (!function_exists('secure_asset')) {

    /**
     * Generate an asset path for the application.
     *
     * @param  string $path
     * @return string
     */
    function secure_asset($path) {
        return asset($path, true);
    }

}
if (!function_exists('secure_url')) {

    /**
     * Generate a HTTPS url for the application.
     *
     * @param  string $path
     * @param  mixed $parameters
     * @return string
     */
    function secure_url($path, $parameters = array()) {
        return url($path, $parameters, true);
    }

}
if (!function_exists('session')) {

    /**
     * Get / set the specified session value.
     *
     * If an array is passed as the key, we will assume you want to set an array of values.
     *
     * @param  array|string $key
     * @param  mixed $default
     * @return mixed
     */
    function session($key = null, $default = null) {
        if (is_null($key))
            return app('session');
        if (is_array($key))
            return app('session')->put($key);
        return app('session')->get($key, $default);
    }

}
if (!function_exists('cookie')) {

    /**
     * Create a new cookie instance.
     *
     * @param  string $name
     * @param  string $value
     * @param  int $minutes
     * @param  string $path
     * @param  string $domain
     * @param  bool $secure
     * @param  bool $httpOnly
     * @return \Symfony\Component\HttpFoundation\Cookie
     */
    function cookie($name = null, $value = null, $minutes = 0, $path = null, $domain = null, $secure = false, $httpOnly = true) {
        $cookie = app('Illuminate\Contracts\Cookie\Factory');
        if (is_null($name)) {
            return $cookie;
        }
        return $cookie->make($name, $value, $minutes, $path, $domain, $secure, $httpOnly);
    }

}


if (!function_exists('randomString')) {

    function randomString($length, $uc = FALSE, $n = FALSE, $sc = FALSE) {
        $rstr = '';
        $source = 'abcdefghijklmnopqrstuvwxyz';
        if ($uc)
            $source .= 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        if ($n)
            $source .= '1234567890';
        if ($sc)
            $source .= '|@#~$%()=^*+[]{}-_';
        if ($length > 0) {
            $rstr = "";
            $length1 = $length - 1;
            $input = array('a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z');
            $rand = array_rand($input, 1);
            $source = str_split($source, 1);
            $rstr1 = '';
            for ($i = 1; $i <= $length1; $i++) {
                $num = mt_rand(1, count($source));
                $rstr1 .= $source[$num - 1];
                $rstr = "{$rand}{$rstr1}";
            }
        }
        return $rstr;
    }

}

if (!function_exists('generateTicketID')) {

    function generateTicketID() {
        return substr(str_replace(' ', '', microtime(FALSE)), 2);
    }

}

if (!function_exists('arrangeArrayPair')) {

    function arrangeArrayPair($mainArray, $keyLabel, $valueLabel) {
        $newArray = array_combine(
                array_map(
                        function ($value) use ($keyLabel) {
                    return $value[$keyLabel];
                }, $mainArray
                ), array_map(
                        function ($value) use ($valueLabel) {
                    return $value[$valueLabel];
                }, $mainArray
                )
        );
        return $newArray;
    }

}

if (!function_exists('array_keyBy')) {

    function array_keyBy($array, $keyBy) {
        if (!empty($array) && count($array) > 0) {
            $tempArray = [];
            foreach ($array as $key => $value) {
                if (!is_array($value)) {
                    $value = (array) $value;
                }
                if (isset($value[$keyBy]) && $value[$keyBy] != '' && !is_array($value[$keyBy])) {

                    $tempArray[$value[$keyBy]] = $value;
                }
            }
            $array = $tempArray;
        }
        return $array;
    }

}

if (!function_exists('arrayMultiKeyByGenrate')) {

    function arrayMultiKeyByGenrate($inputArray, $keyBy, $innerKey) {
        $returnArray = [];
        if (!empty($inputArray) && count($inputArray) > 0) {
            $tempArray = [];
            foreach ($inputArray as $key => $value) {
                if (!is_array($value)) {
                    $value = (array) $value;
                }
                if (is_array($value) && count($value) > 0) {
                    if (isset($value[$keyBy]) && $value[$keyBy] != '' && !is_array($value[$keyBy])) {
                        if (!array_key_exists($value[$keyBy], $tempArray)) {
                            $tempArray[$value[$keyBy]][$innerKey] = [];
                        }

                        $tempArray[$value[$keyBy]][$innerKey][] = $value;
                    }
                }
            }
            $returnArray = $tempArray;
        }
        return $returnArray;
    }

}

if (!function_exists('arrayMergeByKey')) {

    function arrayMergeByKey($array1, $array2, $mergeValueOf) {
        if (!empty($array1) && count($array1) > 0) {
            foreach ($array1 as $key => $value) {
                if (!is_array($value)) {
                    $value = (array) $value;
                }
                if (isset($array2[$key]) && isset($array2[$key][$mergeValueOf])) {
                    if (!isset($array1[$key][$mergeValueOf])) {
                        $array1[$key][$mergeValueOf] = '';
                    }
                    $array1[$key][$mergeValueOf] = $array2[$key][$mergeValueOf];
                }
            }
        }
        return $array1;
    }

}

if (!function_exists('arrayRemoveKeyValue')) {

    function arrayRemoveKeyValue($array, $needToRemove) {
        if (is_array($array) && count($array) > 0) {
            $explodeKeys = explode('.', $needToRemove);

            if (isset($explodeKeys[0]) && $explodeKeys[0] == '*') {
                unset($explodeKeys[0]);
                foreach ($array as $key => $value) {
                    $explodeKeys = array_values($explodeKeys);
                    if (!empty($explodeKeys) && count($explodeKeys) > 0) {
                        if (is_array($value)) {
                            $array[$key] = arrayRemoveKeyValue($value, implode('.', $explodeKeys));
                        }
                    }
                }
            } else if (isset($explodeKeys[0]) && $explodeKeys[0] != '') {
                $currentKey = $explodeKeys[0];
                unset($explodeKeys[0]);
                $explodeKeys = array_values($explodeKeys);
                if ($currentKey == 'order_items') {
                    Log::info("Current key " . $currentKey . " need to exclude " . implode('.', $explodeKeys));
                }
                if (!empty($explodeKeys) && count($explodeKeys) > 0) {
                    if (isset($array[$currentKey])) {
                        if (is_array($array[$currentKey])) {
                            $array[$currentKey] = arrayRemoveKeyValue($array[$currentKey], implode('.', $explodeKeys));
                        }
                    }
                } else {
                    unset($array[$currentKey]);
                }
            }
        }
        return $array;
    }

}

if (!function_exists('obj2Arr')) {

    function obj2Arr($obj) {
        return json_decode(json_encode($obj), true);
    }

}

if (!function_exists('multiArrayKeyIndexSubArray')) {

    function multiArrayKeyIndexSubArray($array, $keyLabel, $valueLabel) {
        $tempArray = [];
        if (!empty($array) && count($array) > 0) {
            foreach ($array as $key => $subArray) {
                $tempArray[$key] = arrayKeyIndexSubArray($subArray, $keyLabel, $valueLabel);
            }
        }
        return $tempArray;
    }

}

if (!function_exists('arrayKeyIndexSubArray')) {

    function arrayKeyIndexSubArray($array, $keyLabel, $valueLabel) {
        $tempArray = [];
        if (!empty($array) && count($array) > 0) {
            foreach ($array as $subArray) {
                if (isset($subArray[$keyLabel])) {
                    if (!array_key_exists($subArray[$keyLabel], $tempArray)) {
                        $tempArray[$subArray[$keyLabel]] = [];
                    }
                }
                if (isset($subArray[$valueLabel])) {
                    $tempArray[$subArray[$keyLabel]][] = $subArray[$valueLabel];
                }
            }
        }
        return $tempArray;
    }

}

if (!function_exists('arrayGroupByInnerKey')) {

    function arrayGroupByInnerKey($array, $indexKey, $removeIndexKeyPair = false) {
        $tempArray = [];
        if (is_object($array)) {
            $array = obj2Arr($array);
        }
        if (count($array) > 0) {
            foreach ($array as $key => $value) {

                if (array_key_exists($indexKey, $value)) {
                    if (!isset($tempArray[$value[$indexKey]])) {
                        $tempArray[$value[$indexKey]] = [];
                    }
                    $assignArray = $value;
                    if ($removeIndexKeyPair) {
                        unset($assignArray[$indexKey]);
                    }
                    $tempArray[$value[$indexKey]][] = $assignArray;
                }
            }
        }
        return $tempArray;
    }

}

if (!function_exists('arrayAppendByMainArrayKey')) {

    function arrayAppendByMainArrayKey($mainArray, $subArray, $mainArrayKey, $removeMainKey = false) {
        $tempArray = [];
        if (is_object($mainArray)) {
            $mainArray = obj2Arr($mainArray);
        }
        if (is_object($subArray)) {
            $subArray = obj2Arr($subArray);
        }
        if (count($mainArray) > 0) {
            foreach ($mainArray as $key => $value) {

                if (array_key_exists($mainArrayKey, $value)) {
                    if (array_key_exists($value[$mainArrayKey], $subArray)) {
                        $newArray = array_merge($value, $subArray[$value[$mainArrayKey]]);
                        if ($removeMainKey) {
                            unset($newArray[$mainArrayKey]);
                        }
                        $tempArray[$key] = $newArray;
                    } else {
                        $tempArray[$key] = $value;
                    }
                }
            }
        }
        return $tempArray;
    }

}


if (!function_exists('getClientIp')) {

    function getClientIp() {
        if (getenv('HTTP_CLIENT_IP'))
            $ipaddress = getenv('HTTP_CLIENT_IP');
        else if (getenv('HTTP_X_FORWARDED_FOR'))
            $ipaddress = getenv('HTTP_X_FORWARDED_FOR');
        else if (getenv('HTTP_X_FORWARDED'))
            $ipaddress = getenv('HTTP_X_FORWARDED');
        else if (getenv('HTTP_FORWARDED_FOR'))
            $ipaddress = getenv('HTTP_FORWARDED_FOR');
        else if (getenv('HTTP_FORWARDED'))
            $ipaddress = getenv('HTTP_FORWARDED');
        else if (getenv('REMOTE_ADDR'))
            $ipaddress = getenv('REMOTE_ADDR');
        else
            $ipaddress = 'UNKNOWN';

        return $ipaddress;
    }

}

/**
 * Generate x digit random number
 * @param  integer  $digits 
 *
 * @return number
 * @author  Amit panchal <amit@unoindia.co>
 * @version 0.0.1
 */
if (!function_exists('numericRand')) {

    function numericRand($digits) {
        return rand(pow(10, $digits - 1), pow(10, $digits) - 1);
    }

}

/**
 * Saving html file to s3.
 */
if (!function_exists('saveHtmlFile')) {
    function saveHtmlFile($html, $file_name) {
        $file_name = $file_name.'.html';
        $path = config('filesystems.disks.s3.bucket_folder_name').$file_name;
        Storage::disk(config('filesystems.cloud'))->put($path, $html); 
        $html_url = Storage::disk(config('filesystems.cloud'))->url($path);
        $url = $html_url;

        $client = new GuzzleHttp\Client([
            'base_uri' => 'http://pdf-generator.dev.api.unoapp.io',
        ]);

        try{
            $params = [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'client-id' => 'UUGwGQSzsocydaVSyJAXTr7kX7uyukbP',
                    'client-secret' => 'tIGZlsd8lh7sg9Ls5f8mZ5HwrMO791TV',
                ],
                'json' => [
                    'htmlUrl' => $html_url,
                    'requested_path' => config('filesystems.disks.s3.bucket_folder_name'),
                ],
            ];
    
            $response = $client->request('POST', "/api/v1/pdfgenerator", $params);
            $decoded_response = json_decode($response->getBody()->getContents());
            $url = $decoded_response->url;
            
        } catch (GuzzleHttp\Exception\ConnectException $e) {
            $log_message = App\Helpers\ErrorCodes::GATEWAY_TIMEOUT . ' : ' . $e->getMessage();
            throw new App\Exceptions\DfxException(ErrorCodes::GATEWAY_TIMEOUT, 'gateway_timeout', $log_message, 504);
        } catch (GuzzleHttp\Exception\RequestException $e) {
            App\Exceptions\DfxException::dfxExceptionHandler($e);
        }

        return $url;
    }


}
/**
 * Generate pdf from html
 *
 * @return $destinationPath
 * @author  Amit panchal <amit@unoindia.co>
 * @version 0.0.1
 */
if (!function_exists('generatePdf')) {

    function generatePdf($html, $destinationPath) {
        $tempFile = public_path() . "/assets/pdf/" . time() . ".html";
        $fp = fopen($tempFile, "wb");
        fwrite($fp, $html);
        fclose($fp);
        $return = null;
        $output = null;
        exec("xvfb-run wkhtmltopdf " . $tempFile . " " . $destinationPath, $output, $return);
        
        if (!$return) {
            unlink($tempFile);
        }
        return $destinationPath;
    }

}
/**
 * To send sms to any number     
 *
 * @return  void
 * @author  Amit Panchal <amit@unoindia.co>
 * @version 0.0.1
 */
if (!function_exists('sendSms')) {

    function sendSms($phone, $msg = '') {
        try {
            $client = new \Services_Twilio(env('TWILIO_ACCOUNT_SID'), env('TWILIO_ACCOUNT_TOKEN'));
            $result = $client->account->messages->create([
                "From" => env('TWILIO_PHONE_FROM'),
                "To" => $phone,
                "Body" => $msg
            ]);
            return true;
        } catch (Services_Twilio_RestException $e) {
            return response()->json([
                        'status' => 'error',
                        'declaration' => 'sms_not_sent',
                        'payload' => ['message' => $e->getMessage()],
                            ], 500);
        }
    }

}
if (!function_exists('generateHtml')) {

    function generateHtml($data) {
        $dealer_name = $data['dealer_title'];
        $dealer_logo = $data['dealer_logo'];
        $customerVehicleInfo = $data['customer_vehicle_info'];        
        $forms = $data['sections'];
        $signature_date = $data['signature_date'];
        //$signatureInfo = $data['signature'];

        $html = '<html>';
        $html .= '<head>
                        <style>
                            .main-container{
                                margin: 0 auto;
                                width: 100%;
                                max-width: 990px;
                            }
                            table {
                                    font-family: Helvetica;
                                    border-collapse: collapse;
                                    border-spacing: 0;
                                    width: 100%;
                            }

                            .clearfix {
                                clear: both;
                                display: block;
                            }

                            .center-block {
                                margin-right: auto;
                                margin-left: auto;
                                display: block;
                            }
                            .text-center {
                                text-align: center;
                            }

                            .main_heder td {
                                    padding: 0 10px;
                            }
                            .main_heder label {
                                    font-size: 26px;
                                    font-weight: 700;
                                    text-transform: uppercase;
                                vertical-align: top;
                            }
                            .form_style tr td {
                                    padding: 0 10px;
                            }
                            .form_style tr td label {
                                    font-size: 15px;
                                    text-align: left;
                                    margin-top: 20px;
                                    display: block;
                                    font-weight: 700;
                            }
                            .form_style tr td input {
                                    height: 24px;
                                    width: 100%;
                                    vertical-align:middle;
                                    display: block;
                            }
                            .service_wrapper_1 {
                                    margin-top: 20px;
                            }
                            .service_wrapper_1 tr td p {
                                    font-size: 15px;
                                    padding-top:10px;
                                    font-weight: 700;
                                    margin: 0;
                            }

                            .service_wrapper_2 tr td {
                                    font-size: 15px;
                                    padding-top: 20px;
                                    font-weight: 700;
                                    padding-right: 60px;
                            }
                            .service_wrapper_3 tr td,
                            .service_wrapper_4 tr td,
                            .service_wrapper_5 tr td,
                             .service_wrapper_6 tr td {
                                    font-size: 15px;
                                    padding-top: 20px;
                                    font-weight: 700;
                                    padding-right: 60px;
                                    vertical-align: top;
                                width: 33.3333%;
                            }
                             .service_wrapper_6 tr.sign td {
                                    font-weight: normal;
                                    padding-right: 60px;
                                    vertical-align: top;
                            }
                             .service_wrapper_6 label {
                                    margin-bottom: 10px;
                                    display: block;
                             }
                            .list {
                                    list-style: none;
                                margin: 0;
                                padding: 0;

                            }
                            .list li {
                                position: relative;
                                padding-right: 40px;
                                margin-bottom: 15px;
                                line-height: 20px;
                            }
                            .list input {
                                        margin-left: 30px;
                                        vertical-align: middle;
                                        float: right;
                                        position: absolute;
                                        right: 0;
                                        top: 0;
                            }
                            .list label {
                            vertical-align: top;
                                display: inline-block;
                            }
                        </style>
                    </head>';

        $html.='<body>
                    <div class="main-container">                    
                            <table>
                                    <tr class="main_heder">
                                      <td><img src="' . $dealer_logo . '" alt="" width="50px; height="35px;/>&nbsp;&nbsp;                                      
                                      <label>' . $dealer_name . '</label></td> 
                                    </tr>
                            </table>
                            <table class="form_style">
                            <tr>
				<td>
					<label>First Name</label>
					<input type="text" value="' . $customerVehicleInfo['first_name'] . '"/>
				</td>
				<td>
					<label>Last Name</label>
					<input type="text" value="' . $customerVehicleInfo['last_name'] . '"/>
				</td>
				<td>
					<label>Email Address</label>
					<input type="text" value="' . $customerVehicleInfo['email'] . '"/>
				</td>
				<td>
					<label>Phone Number</label>
					<input type="text" value="' . $customerVehicleInfo['phone_number'] . '"/>
				</td>
                            </tr>
                            <tr>
				<td>
					<label>Model</label>
					<input type="text" value="' . $customerVehicleInfo['model'] . '"/>
				</td>
				<td>
					<label>Color</label>
					<input type="text" value="' . $customerVehicleInfo['color'] . '"/>
				</td>
				<td>
					<label>Stock Number</label>
					<input type="text" value="' . $customerVehicleInfo['stock_number'] . '"/>
				</td>
				<td>
					<label>VIN</label>
					<input type="text" value="' . $customerVehicleInfo['vin'] . '" />
				</td>
                            </tr>
                            <tr>
                                <td>
                                        <label>Delivery Date</label>
                                        <input type="text" value="' . $customerVehicleInfo['delivery_date'] . '"/>
                                </td>
                                <td colspan="2">
                                        <label>Sales Person</label>
                                        <input type="text" value="' . $customerVehicleInfo['sales_person'] . '" />
                                </td>
                            </tr>
                            <tr>
                                <td colspan="3">
                                        <label>Notes</label>
                                        <input type="text" value="' . $customerVehicleInfo['notes'] . '"/>
                                </td>
                            </tr>
                        </table>';

        if (count($forms) > 0) {
            $i = 1;
            $j = 1;            
            foreach ($forms as $sectionKey => $from) {
                
                foreach ($from['children'] as $sectionKey => $sectionVal) {
                    
                    $no_of_record_per_line = $sectionVal['attributes']['questions_in_row'];                    
                    if ($sectionVal['question'][0]['type'] == 'sign') {
                        $html .= '<table class="service_wrapper_6">';
                        //$sectionVal['description']
                        $html .= '<tr><td >Signature <br>' . $sectionVal['title'] . '</td></tr>';
                        $i = 1;
                        foreach ($sectionVal['question'] as $Key => $val) {
                            if ($i == 1 || $i % $no_of_record_per_line != 0) {
                                $html .= '<tr class="sign">';
                            }
                            $html .= '<td>';

                            $image_url = '';
                            foreach ($val['question_options'] as $optionkey => $optionval) {
                                if (trim($optionval['participant_response']['option_value']) != '') {
                                    $image_url = trim($optionval['participant_response']['option_value']);
                                }
                            }

                            $html .='<label style="height: 50px;width: 200px;position: relative;"><img src="' . $image_url . '" style="max-width: 100%"> </lable>';

                            $html .= '<label><b> ' . $val['question_text'] . '</b></label>';
                            $html .= '</td>';
                            if ($i != 1 && $i % $no_of_record_per_line == 0) {
                                $html .= '</tr>';
                            }
                            $i++;
                        }
                        $html .='<tr><td><label>Date : ' . $signature_date . '</label></tr>';
                        $html.='</table>';
                    } else if ($no_of_record_per_line > 1) {
                        $html .= '<table class="service_wrapper_2">';
                        $html .= '<tr><td >' . $sectionVal['title'] . '</td></tr>';
                        $i = 1;
                        foreach ($sectionVal['question'] as $Key => $val) {
                            if ($i == 1 || $i % $no_of_record_per_line != 0) {
                                $html .= '<tr>';
                            }

                            $html .= '<td>';
                            $html .='<ul class="list">';
                            $html .='<li>' . trim($val['question_text']);
                            $checked = null;
                            foreach ($val['question_options'] as $optionKey => $optionVal) {
                                if (strtolower(trim($optionVal['option_text'])) == 'yes' && count($optionVal['participant_response']) > 0) {
                                    $checked = 'checked = "checked"';
                                    $html .='<input id="checkBox" type="checkbox" ' . $checked . '>&nbsp;&nbsp;&nbsp;&nbsp;';
                                }
                                if (strtolower(trim($optionVal['option_text'])) == 'no' && count($optionVal['participant_response']) > 0) {
                                    $html .='<input id="checkBox" type="checkbox" ' . $checked . '>&nbsp;&nbsp;&nbsp;&nbsp;';
                                }
                            }
                            //$html .='<input id="checkBox" type="checkbox" ' . $checked . '> ';

                            $html .='</li>';

                            $html .='</ul>';
                            $html .= '</td>';

                            if ($i != 1 && $i % $no_of_record_per_line == 0) {
                                $html .= '</tr>';
                            }
                            $i++;
                        }
                        $html.='</table>';
                    } else {
                        if ($j == 1) {
                            $html .= '<table class="service_wrapper_1">
                          <tr><td><p>Your First Service</p></td></tr>
                          <tr><td><p>To be completed by the new owner. Please check off items as they are completed</p></td></tr>
                          </table>';
                        }
                        foreach ($sectionVal['question'] as $Key => $val) {
                            $html .= '<table class="service_wrapper_2">';
                            preg_match_all('/{{(.*?)}}/', $val['question_text'], $matches);
                            if (count($matches) > 0) {
                                $replace = array("{{MAKE}}" => ucfirst(strtolower($customerVehicleInfo['make'])), "{{YEAR}}" => $customerVehicleInfo['year'], "{{MODEL}}" => $customerVehicleInfo['model']);
                                $val['question_text'] = strtr($val['question_text'], $replace);
                            }
                            $html .= '<tr><td >' . $val['question_text'] . '</td></tr>';
                            $html .= '<tr>';
                            $html .= '<td>';
                            $checked = null;

                            foreach ($val['question_options'] as $optionKey => $optionVal) {
                                $checked = "";
                                $condition_participant_response = (isset($optionVal['participant_response']) && count($optionVal['participant_response']) > 0);

                                if (strtolower(trim($optionVal['option_text'])) == 'yes' && $condition_participant_response) {
                                    $checked = 'checked = "checked"';
                                }

                                if (strtolower(trim($optionVal['option_text'])) == 'no' && $condition_participant_response) {
                                    $checked = 'checked = "checked"';
                                }
                                $html .=trim($optionVal['option_text']) . '<input id="checkBox" type="checkbox" ' . $checked . '>&nbsp;&nbsp;&nbsp;&nbsp;';
                            }
                            $html .= '</td>';
                            $html .= '</tr>';
                        }
                    }
                    $html .= '</tr>';
                    $html .= '</table>';
                    $j++;
                }
            }
        }
        $html .= '</div>
	</body></html>';
        return $html;
    }

}
