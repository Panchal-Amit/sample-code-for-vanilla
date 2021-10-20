<?php

namespace App\Http\Controllers;

use GuzzleHttp\Client as GuzzleHttpClient;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SurveyController extends Controller {

    /**
     * Instance of GuzzleHttpClient
     *
     * @var client
     */
    private $client;

    public function __construct() {
        $this->client = new GuzzleHttpClient([
            'base_uri' => config('survey.survey_protocol') . '://' . config('survey.survey_domain'),
        ]);
        parent::__construct();
    }

    /**
     * Making guest login for survey-engine     
     * 
     * @param   Request $request     
     * @return  \Illuminate\Http\Response
     * @author  Amit Panchal <amit@unoindia.co>
     * @author  Jenish Panchal <jenishp@unoindia.co>
     * @version 0.0.1
     */
    public function guestLogin(Request $request) {

        $this->validate($request, [
            'location_id'=>'required|integer',
            'email' => 'required|email'            
        ]);

        try {
            $request_data = $request->all();            
            $request_data['form_access_code'] = config('survey.form_access_code');
            $request_data['location_id'] = $request['location_id'];
            $params = [
                'headers' => [
                    'app-id' => config('survey.app_id'),
                    'app-secret' => config('survey.app_secret')
                ],
                'json' => $request_data,
            ];
            $survey_request = $this->client->request('PUT', "/survey/guest_login", $params);
            $response = json_decode($survey_request->getBody()->getContents());

            return response()->json([
                        'status' => 'success',
                        'declaration' => 'guest_logged_in',
                        'payload' => $response->payload,
                            ], 200);
        } catch (ConnectException $e) {

            $return = [
                'declaration' => 'gateway_timeout',
                'status' => 'error',
                'payload' => ['message' => $e->getMessage()],
            ];
            return response($return, 504);
        } catch (RequestException $e) {
            return response()->json([
                        'status' => 'error',
                        'declaration' => 'invalid_request',
                        'payload' => json_decode($e->getResponse()->getBody()->getContents()),
                            ], 404);
        }
    }

    /**
     * Get survey list from survey-engine
     * @NOTE Still working on this
     * @param   Request $request     
     * @return  \Illuminate\Http\Response
     * @author  Jenish Panchal <jenishp@unoindia.co>
     * @version 0.0.1
     */
    public function surveyList(Request $request) {

        $this->validate($request, [
            'unique_code' => 'required|string',
            'participant_token' => 'required|integer'
        ]);

        try {
            $params = [
                'headers' => [
                    'app-id' => config('survey.app_id'),
                    'app-secret' => config('survey.app_secret')
                ],
                'json' => $request->all(),
            ];

            $survey_request = $this->client->request('POST', "/participants/list_survey", $params);
            $response = json_decode($survey_request->getBody()->getContents());

            return response()->json([
                        'status' => 'success',
                        'declaration' => $response->declaration,
                        'payload' => $response->payload,
                            ], 200);
        } catch (ConnectException $e) {

            $return = [
                'declaration' => 'gateway_timeout',
                'status' => 'error',
                'payload' => ['message' => $e->getMessage()],
            ];
            return response($return, 504);
        } catch (RequestException $e) {
            return response()->json([
                        'status' => 'error',
                        'declaration' => 'invalid_request',
                        'payload' => json_decode($e->getResponse()->getBody()->getContents()),
                            ], 404);
        }
    }

    /**
     * Get Section list from survey-engine
     * @NOTE Still working on this
     * @param   Request $request     
     * @return  \Illuminate\Http\Response
     * @author  Jenish Panchal <jenishp@unoindia.co>
     * @version 0.0.1
     */
    public function sectionList(Request $request) {

        $this->validate($request, [
            'participant_token' => 'required|integer',
            'form_id' => 'required|integer',
            'with_all_details' => 'sometimes|boolean'
        ]);

        try {
            $params = [
                'headers' => [
                    'app-id' => config('survey.app_id'),
                    'app-secret' => config('survey.app_secret'),
                    'access-code' => config('survey.form_access_code'),
                    'participant-token' => $request->get('participant_token')
                ],
                'json' => $request->all(),
            ];

            $survey_request = $this->client->request('POST', "/section/access_code", $params);
            $response = json_decode($survey_request->getBody()->getContents(), true);

            if (count($response['payload']['sections']) > 0) {
                $replace_data = $request->get('data');
                foreach ($response['payload']['sections'] as $sectionKey => $sectionVal) {
                    foreach ($sectionVal['question'] as $key => $val) {
                        preg_match_all('/{{(.*?)}}/', $val['question_text'], $matches);
                        if (count($matches) > 0) {
                            $replace = array("{{MAKE}}" => ucfirst(strtolower($replace_data['MAKE'])), "{{YEAR}}" => $replace_data['YEAR'], "{{MODEL}}" => $replace_data['MODEL']);
                            $response['payload']['sections'][$sectionKey]['question'][$key]['question_text'] = strtr($val['question_text'], $replace);
                        }
                    }
                }
            }
            return response()->json([
                        'status' => 'success',
                        'payload' => $response['payload'],
                            ], 200);
        } catch (ConnectException $e) {

            $return = [
                'declaration' => 'gateway_timeout',
                'status' => 'error',
                'payload' => ['message' => $e->getMessage()],
            ];
            return response($return, 504);
        } catch (RequestException $e) {
            return response()->json([
                        'status' => 'error',
                        'declaration' => 'invalid_request',
                        'payload' => json_decode($e->getResponse()->getBody()->getContents()),
                            ], 404);
        }
    }

    /**
     * Question List from survey-engine     
     * @param   Request $request     
     * @return  \Illuminate\Http\Response
     * @author  Jenish Panchal <jenishp@unoindia.co>
     * @version 0.0.1
     */
    public function questionList(Request $request) {

        $this->validate($request, [
            'participant_token' => 'required|integer',
            'section_id' => 'sometimes|required|integer',
            'question_id' => 'sometimes|required|integer',
            'survey_id' => 'sometimes|required|integer',
        ]);

        try {
            $params = [
                'headers' => [
                    'app-id' => config('survey.app_id'),
                    'app-secret' => config('survey.app_secret'),
                    'access-code' => config('survey.form_access_code'),
                    'participant-token' => $request->get('participant_token')
                ],
                'json' => $request->all(),
            ];

            $survey_request = $this->client->request('POST', "/question/access_code", $params);
            $response = json_decode($survey_request->getBody()->getContents());

            return response()->json([
                        'status' => 'success',
                        'payload' => $response->payload,
                            ], 200);
        } catch (ConnectException $e) {

            $return = [
                'declaration' => 'gateway_timeout',
                'status' => 'error',
                'payload' => ['message' => $e->getMessage()],
            ];
            return response($return, 504);
        } catch (RequestException $e) {
            return response()->json([
                        'status' => 'error',
                        'declaration' => 'invalid_request',
                        'payload' => json_decode($e->getResponse()->getBody()->getContents()),
                            ], 404);
        }
    }

    /**
     * Question Optoin List from survey-engine     
     * @param   Request $request     
     * @return  \Illuminate\Http\Response
     * @author  Amit Panchal <amit@unoindia.co>
     * @version 0.0.1
     */
    public function QuestionOptionList(Request $request) {

        $this->validate($request, [
            'question_id' => 'required|integer',
            'participant_token' => 'required|integer'
        ]);
        $request_data['question_id'] = $request->get('question_id');

        try {
            $params = [
                'headers' => [
                    'app-id' => config('survey.app_id'),
                    'app-secret' => config('survey.app_secret'),
                    'access-code' => config('survey.form_access_code'),
                    'participant-token' => $request->get('participant_token')
                ],
                'json' => $request_data,
            ];

            $survey_request = $this->client->request('POST', "/question_option/access_code", $params);
            $response = json_decode($survey_request->getBody()->getContents());

            return response()->json([
                        'status' => 'success',
                        'declaration' => 'question_option_loaded',
                        'payload' => $response->payload,
                            ], 200);
        } catch (ConnectException $e) {

            $return = [
                'declaration' => 'gateway_timeout',
                'status' => 'error',
                'payload' => ['message' => $e->getMessage()],
            ];
            return response($return, 504);
        } catch (RequestException $e) {
            return response()->json([
                        'status' => 'error',
                        'declaration' => 'invalid_request',
                        'payload' => json_decode($e->getResponse()->getBody()->getContents()),
                            ], 404);
        }
    }

    /**
     * Question List from survey-engine     
     * @param   Request $request     
     * @return  \Illuminate\Http\Response
     * @author  Jenish Panchal <jenishp@unoindia.co>
     * @author  Amit Panchal <amit@unoindia.co>
     * @version 0.0.1
     */
    public function sectionResponse(Request $request) {

        $this->validate($request, [
            'participant_token' => 'required|integer',
            'unique_code' => 'required|alpha_num',
            'survey_id' => 'required|integer',
            'questions.*.id' => 'required|integer',
            'questions.*.section_id' => 'required|integer',
            'questions.*.option_id' => 'required|integer',
            'questions.*.override_update' => 'sometimes|required|boolean',
        ]);

        try {
            $request_data = $request->all();
            $questions = $request->get('questions');

            //To upload signature image
            //To reduce call to make individual call to upload image as per discussion with mobile team
            if (count($request_data['questions']) > 0) {
                foreach ($request_data['questions'] as $key => $val) {
                    if ($val['type'] == 'sign' && !empty($val['sign'])) {
                        $upload_response = $this->uploadFile($val['sign']);
                        if ($upload_response['status'] == 'success') {
                            $request_data['questions'][$key]['option_value'] = $upload_response['payload']['file']['url'];
                            unset($request_data['questions'][$key]['sign']);
                        }
                    }
                }
            }
            $params = [
                'headers' => [
                    'app-id' => config('survey.app_id'),
                    'app-secret' => config('survey.app_secret'),
                    'access-code' => config('survey.form_access_code'),
                    'participant-token' => $request->get('participant_token')
                ],
                'json' => $request_data,
            ];

            $survey_request = $this->client->request('POST', "/participant/section/response", $params);
            $response = json_decode($survey_request->getBody()->getContents());

            return response()->json([
                        'status' => 'success',
                        'declaration' => 'response_added',
                        'payload' => [],
                            ], 200);
        } catch (ConnectException $e) {

            $return = [
                'declaration' => 'gateway_timeout',
                'status' => 'error',
                'payload' => ['message' => $e->getMessage()],
            ];
            return response($return, 504);
        } catch (RequestException $e) {
            return response()->json([
                        'status' => 'error',
                        'declaration' => 'invalid_request',
                        'payload' => json_decode($e->getResponse()->getBody()->getContents()),
                            ], 404);
        }
    }

    /**
     * To get survey detail from survey-engine     
     * @param   Request $request     
     * @return  \Illuminate\Http\Response
     * @author  Amit Panchal <amit@unoindia.co>
     * @version 0.0.1
     */
    public function SurveyDetail(Request $request) {

        $this->validate($request, [
            'unique_code' => 'required|alpha_num',
            'survey_id' => 'required|integer',
            'participant_token' => 'required|integer'
        ]);
        $request_data['unique_code'] = $request->get('unique_code');
        $request_data['survey_id'] = $request->get('survey_id');
        try {
            $params = [
                'headers' => [
                    'app-id' => config('survey.app_id'),
                    'app-secret' => config('survey.app_secret'),
                    'access-code' => config('survey.form_access_code'),
                    'participant-token' => $request->get('participant_token')
                ],
                'json' => $request_data,
            ];

            $survey_request = $this->client->request('POST', "/survey/get_details", $params);
            $response = json_decode($survey_request->getBody()->getContents());

            return response()->json([
                        'status' => 'success',
                        'declaration' => 'survey_detail_loaded',
                        'payload' => $response->payload,
                            ], 200);
        } catch (ConnectException $e) {

            $return = [
                'declaration' => 'gateway_timeout',
                'status' => 'error',
                'payload' => ['message' => $e->getMessage()],
            ];
            return response($return, 504);
        } catch (RequestException $e) {
            return response()->json([
                        'status' => 'error',
                        'declaration' => 'invalid_request',
                        'payload' => json_decode($e->getResponse()->getBody()->getContents()),
                            ], 404);
        }
    }

    /**
     * To get survey response from survey-engine     
     * @param   Request $request     
     * @return  \Illuminate\Http\Response
     * @author  Amit Panchal <amit@unoindia.co>
     * @version 0.0.1
     */
    public function SurveyResponse(Request $request) {

        $this->validate($request, [
            'survey_id' => 'required|integer',
            'participant_token' => 'required|integer'
        ]);
        $survey_id = $request->get('survey_id');
        try {
            $params = [
                'headers' => [
                    'app-id' => config('survey.app_id'),
                    'app-secret' => config('survey.app_secret'),
                    'access-code' => config('survey.form_access_code'),
                    'participant-token' => $request->get('participant_token')
                ]
            ];

            $survey_request = $this->client->request('GET', "/survey/$survey_id/response", $params);
            $response = json_decode($survey_request->getBody()->getContents());

            return response()->json([
                        'status' => 'success',
                        'declaration' => 'survey_response_loaded',
                        'payload' => $response->payload,
                            ], 200);
        } catch (ConnectException $e) {

            $return = [
                'declaration' => 'gateway_timeout',
                'status' => 'error',
                'payload' => ['message' => $e->getMessage()],
            ];
            return response($return, 504);
        } catch (RequestException $e) {
            return response()->json([
                        'status' => 'error',
                        'declaration' => 'invalid_request',
                        'payload' => json_decode($e->getResponse()->getBody()->getContents()),
                            ], 404);
        }
    }

    /**
     * Submit survey to survey-engine     
     * @param   Request $request     
     * @return  \Illuminate\Http\Response
     * @author  Amit Panchal <amit@unoindia.co>
     * @version 0.0.1
     */
    public function SubmitSurvey(Request $request) {

        $this->validate($request, [
            'survey_id' => 'required|integer',
            'participant_token' => 'required|integer',
            'unique_code' => 'required|alpha_num',
        ]);
        $request_data['unique_code'] = $request->get('unique_code');
        $request_data['survey_id'] = $request->get('survey_id');
        try {
            $params = [
                'headers' => [
                    'app-id' => config('survey.app_id'),
                    'app-secret' => config('survey.app_secret'),
                    'access-code' => config('survey.form_access_code'),
                    'participant-token' => $request->get('participant_token')
                ],
                'json' => $request_data,
            ];

            $survey_request = $this->client->request('POST', "/participant/submit", $params);
            $response = json_decode($survey_request->getBody()->getContents());

            return response()->json([
                        'status' => 'success',
                        'declaration' => 'survey_submitted',
                        'payload' => $response->payload,
                            ], 200);
        } catch (ConnectException $e) {

            $return = [
                'declaration' => 'gateway_timeout',
                'status' => 'error',
                'payload' => ['message' => $e->getMessage()],
            ];
            return response($return, 504);
        } catch (RequestException $e) {
            return response()->json([
                        'status' => 'error',
                        'declaration' => 'invalid_request',
                        'payload' => json_decode($e->getResponse()->getBody()->getContents()),
                            ], 404);
        }
    }

    /**
     * Saving images to resources.
     * TODO: Move things out to .env file
     */
    public function saveFile(Request $request) {
        $this->validate($request, [
            'file' => 'required',
        ]);

        $file = $request->file('file');

        $business_id = 1; //UNOapp
        $folder_id = 20; //survey-images

        $url = config('survey.resources_endpoint') . "/resources/business_file/$business_id/$folder_id";
        $request_type = "POST";
        /* Making request. */
        try {
            $client = new GuzzleHttpClient();
            $params = [
                'multipart' => [
                    [
                        'contents' => fopen($file->path(), 'r'),
                        'name' => 'file',
                    ]
                ],
            ];
            $response = $client->request($request_type, $url, $params);
            return response($response->getBody()->getContents(), $response->getStatusCode())
                            ->header('Content-Type', $response->getHeader('Content-Type'));
        } catch (ConnectException $e) {
            $return = [
                'declaration' => 'gateway_timeout',
                'status' => 'error',
                'payload' => [
                    'message' => $e->getMessage(),
                ],
            ];
            return response($return, 504);
        } catch (RequestException $e) {
            return response($e->getResponse()->getBody()->getContents(), $e->getResponse()->getStatusCode())
                            ->header('Content-Type', $e->getResponse()->getHeader('Content-Type'));
        }
    }

    /**
     * Associate participant to forms.     
     * @param   Request $request     
     * @return  \Illuminate\Http\Response
     * @author  Jenish Panchal <jenishp@unoindia.co>
     * @version 0.0.1
     */
    public function associateParticipantToforms(Request $request) {

        $this->validate($request, [
            'participant_token' => 'required|integer',
            'unique_code' => 'required|alpha_num',
            'reference_id' => 'required_without:unique_code|string',
            'data' => 'nullable|array',
        ]);

        $request_data = $request->all();
        $request_data['form_access_code'] = config('survey.form_access_code');

        try {
            $params = [
                'headers' => [
                    'app-id' => config('survey.app_id'),
                    'app-secret' => config('survey.app_secret'),
                    'access-code' => config('survey.form_access_code'),
                    'participant-token' => $request->get('participant_token')
                ],
                'json' => $request_data,
            ];

            $survey_request = $this->client->request('PUT', "/participant/survey/redo", $params);
            $response = json_decode($survey_request->getBody()->getContents());

            return response()->json([
                        'status' => 'success',
                        'declaration' => 'survey_added',
                        "payload" => $response->payload
                            ], 200);
        } catch (ConnectException $e) {

            $return = [
                'declaration' => 'gateway_timeout',
                'status' => 'error',
                'payload' => ['message' => $e->getMessage()],
            ];
            return response($return, 504);
        } catch (RequestException $e) {
            return response()->json([
                        'status' => 'error',
                        'declaration' => 'invalid_request',
                        'payload' => json_decode($e->getResponse()->getBody()->getContents()),
                            ], 404);
        }
    }

    /**
     * Generate PDF from Survey
     * @NOTE : S3 need to add
     * @param   Request $request     
     * @return  \Illuminate\Http\Response
     * @author  Amit Panchal <amit@unoindia.co>
     * @version 0.0.1
     */
    public function generatePdf(Request $request) {

        $this->validate($request, [
            'participant_token' => 'required|integer',
            'survey_id' => 'required|integer',
            'section_id' => 'required|integer',
            'dealer_logo' => 'required',
            'dealer_title' => 'required',
            'customer_vehicle_info' => 'required',
        ]);
        $survey_id = $request->get('survey_id');
        $section_id = $request->get('section_id');
        
        try {
            $params = [
                'headers' => [
                    'app-id' => config('survey.app_id'),
                    'app-secret' => config('survey.app_secret'),
                    'access-code' => config('survey.form_access_code'),
                    'participant-token' => $request->get('participant_token')
                ],
                'json' => [
                    "section_id" => $section_id,
                    "survey_id" => $survey_id,
                ]
            ];

            $survey_request = $this->client->request('POST', "/section/with_all", $params);
            $response = json_decode($survey_request->getBody()->getContents(), true);

            if ($response['status'] == 'error') {
                return response()->json([
                            'status' => 'error',
                            'declaration' => 'survey_not_found',
                            'payload' => ['message' => 'Sorry, survey not found. Please try again.'],
                                ], 404);
            }

            $request_data = $request->all();
            $request_data['sections'] = $response['payload']['sections'];
            $html = generateHtml($request_data);

            $file_name = $survey_id;
            $url = saveHtmlFile($html, $file_name);

            return response()->json([
                        'status' => 'success',
                        'declaration' => 'pdf_generated',
                        'payload' => $url,
                            ], 200);
        } catch (ConnectException $e) {

            $return = [
                'declaration' => 'gateway_timeout',
                'status' => 'error',
                'payload' => ['message' => $e->getMessage()],
            ];
            return response($return, 504);
        } catch (RequestException $e) {
            return response()->json([
                        'status' => 'error',
                        'declaration' => 'invalid_request',
                        'payload' => json_decode($e->getResponse()->getBody()->getContents()),
                            ], 404);
        }
    }

    /**
     * To upload file
     * @param   Request $request     
     * @return  \Illuminate\Http\Response
     * @author  Amit Panchal <amit@unoindia.co>
     * @version 0.0.1
     */
    public function uploadFile($file) {

        $business_id = 1; //UNOapp
        $folder_id = 20; //survey-images

        $url = config('survey.resources_endpoint') . "/resources/business_file/$business_id/$folder_id";
        $request_type = "POST";
        /* Making request. */
        try {
            $client = new GuzzleHttpClient();
            $params = [
                'multipart' => [
                    [
                        'contents' => fopen($file->path(), 'r'),
                        'name' => 'file',
                    ]
                ],
            ];
            $survey_request = $client->request($request_type, $url, $params);
            return json_decode($survey_request->getBody()->getContents(), true);
        } catch (ConnectException $e) {
            $return = [
                'declaration' => 'gateway_timeout',
                'status' => 'error',
                'payload' => [
                    'message' => $e->getMessage(),
                ],
            ];
            return response($return, 504);
        } catch (RequestException $e) {
            return response($e->getResponse()->getBody()->getContents(), $e->getResponse()->getStatusCode())->header('Content-Type', $e->getResponse()->getHeader('Content-Type'));
        }
    }

    /**
     * To get form credential
     * @param   Request $request     
     * @return  \Illuminate\Http\Response
     * @author  Amit Panchal <amit@unoindia.co>
     * @version 0.0.1
     */
    public function getFormCredential(Request $request) {

        try {
            $params = [
                'headers' => [
                    'app-id' => config('survey.app_id'),
                    'app-secret' => config('survey.app_secret')
                ],
                'json' => ["access_code" => config('survey.form_access_code')],
            ];
            $survey_request = $this->client->request('POST', "/form/access_code", $params);
            $response = json_decode($survey_request->getBody()->getContents());

            return response()->json([
                        'status' => 'success',
                        'declaration' => 'form_credential',
                        'payload' => $response->payload,
                            ], 200);
        } catch (ConnectException $e) {

            $return = [
                'declaration' => 'gateway_timeout',
                'status' => 'error',
                'payload' => ['message' => $e->getMessage()],
            ];
            return response($return, 504);
        } catch (RequestException $e) {
            return response()->json([
                        'status' => 'error',
                        'declaration' => 'invalid_request',
                        'payload' => json_decode($e->getResponse()->getBody()->getContents()),
                            ], $e->getResponse()->getStatusCode());
        }
    }

    /**
     * Get Section detail from survey-engine     
     * @param   Request $request     
     * @return  \Illuminate\Http\Response
     * @author  Amit Panchal <amit@unoindia.co>
     * @version 0.0.1
     */
    public function sectionDetail(Request $request) {

        $this->validate($request, [
            'section_id' => 'required|integer',
            'with_all_details' => 'sometimes|boolean',
            'survey_id' => 'required|integer',
            'form_access_code'=>'required',
            'participant_token' => 'required|integer',
            'form_id' => 'required|integer',
            'data.MAKE' => 'nullable',
            'data.YEAR' => 'nullable',
            'data.MODEL' => 'nullable'
        ]);

        try {
            $params = [
                'headers' => [
                    'app-id' => config('survey.app_id'),
                    'app-secret' => config('survey.app_secret'),
                    'access-code' => $request->get('form_access_code'),
                    'participant-token' => $request->get('participant_token')
                ],
                'json' => $request->all(),
            ];

            $survey_request = $this->client->request('POST', "section/with_all", $params);
            $response = json_decode($survey_request->getBody()->getContents(), true);

            if (count($response['payload']['sections']) > 0) {
                $replace_data = $request->get('data');
                foreach ($response['payload']['sections'] as $sectionKey => $sectionVal) {
                    foreach ($sectionVal['children'] as $keyChild => $valChild) {
                        foreach ($valChild['question'] as $key => $val) {

                            preg_match_all('/{{(.*?)}}/', $val['question_text'], $matches);
                            if (count($matches) > 0) {
                                $replace = array("{{MAKE}}" => ucfirst(strtolower($replace_data['MAKE'])), "{{YEAR}}" => $replace_data['YEAR'], "{{MODEL}}" => $replace_data['MODEL']);
                                $response['payload']['sections'][$sectionKey]['children'][$keyChild]['question'][$key]['question_text'] = strtr($val['question_text'], $replace);
                            }
                        }
                    }
                }
            }
            return response()->json([
                        'status' => 'success',
                        'declaration' => 'get_section_detail',
                        'payload' => $response['payload'],
                            ], 200);
        } catch (ConnectException $e) {

            $return = [
                'declaration' => 'gateway_timeout',
                'status' => 'error',
                'payload' => ['message' => $e->getMessage()],
            ];
            return response($return, 504);
        } catch (RequestException $e) {
            return response()->json([
                        'status' => 'error',
                        'declaration' => 'invalid_request',
                        'payload' => json_decode($e->getResponse()->getBody()->getContents()),
                            ], $e->getResponse()->getStatusCode());
        }
    }

}
