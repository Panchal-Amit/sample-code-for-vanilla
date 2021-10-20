<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ConnectException;
use Illuminate\Http\Request;
use App\Models\Countries;
use App\Models\Locales;
use App\Models\Terms;
use App\Models\TermTranslation;

class LocalizationController extends Controller {

    public function __construct() {
        
    }

    /**
     * Get localizations
     * 
     * @return  void
     * @author  Amit Panchal <amit@unoindia.co>
     * @version 0.0.1
     */
    public function getLocalization() {
        $file_path = storage_path('app/localization') . '/localization.json';
        $json = json_decode(file_get_contents($file_path), true);
        return response()->json([
                    'status' => 'success',
                    'declaration' => 'localization_loaded',
                    'payload' => ['localization' => $json],
                        ], 200);
    }

    /**
     * Create Term
     * 
     * @return  void
     * @author  Amit Panchal <amit@unoindia.co>
     * @version 0.0.1
     */
    public function create(Request $request) {
        $this->validate($request, [
            'term' => 'required',
        ]);

        try {
            $term = $request->input('term');
            $term = Terms::firstOrCreate(['term' => $term], ['term' => $term]);

            return response()->json([
                        'status' => 'success',
                        'declaration' => 'term_created',
                        'payload' => $term->id,
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
     * Create term translation
     * 
     * @return  void
     * @author  Amit Panchal <amit@unoindia.co>
     * @version 0.0.1
     */
    public function createTermTranslation(Request $request) {
        $this->validate($request, [
            'country' => 'required|string',
            'locale' => 'required|string',
            'term' => 'required|string',
            'translation' => 'required'
        ]);

        try {
            $country = $request->input('country');
            $locale = $request->input('locale');
            $term = $request->input('term');
            $translation = $request->input('translation');


            $countryObj = Countries::where('name', '=', $country)->first();
            if (is_null($countryObj)) {
                return response()->json([
                            'status' => 'error',
                            'declaration' => 'country_not_found',
                            'payload' => ['message' => 'Sorry, country not found. Please try again.'],
                                ], 404);
            }


            $localeObj = Locales::where('language_code', '=', $locale)->first();
            if (is_null($countryObj)) {
                return response()->json([
                            'status' => 'error',
                            'declaration' => 'locale_not_found',
                            'payload' => ['message' => 'Sorry, locale not found. Please try again.'],
                                ], 404);
            }


            $termObj = Terms::where('term', '=', $term)->first();
            if (is_null($countryObj)) {
                return response()->json([
                            'status' => 'error',
                            'declaration' => 'term_not_found',
                            'payload' => ['message' => 'Sorry, term not found. Please try again.'],
                                ], 404);
            }


            $termTranslationObj = TermTranslation::firstOrCreate(['locales_id' => $localeObj->id, 'term_id' => $termObj->id, 'translation' => $translation], ['locales_id' => $localeObj->id, 'term_id' => $termObj->id, 'translation' => $translation]);

            return response()->json([
                        'status' => 'success',
                        'declaration' => 'term_translation_created',
                        'payload' => $termTranslationObj->id,
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
     * Get Supported Language
     * 
     * @return  void
     * @author  Amit Panchal <amit@unoindia.co>
     * @version 0.0.1
     */
    public function getSupportedLanguage(Request $request) {
        try {

            $languages = Locales::with('countries')
                            ->select(['id', 'language_code', 'language_title', 'default', 'country_id'])
                            ->where([
                                ['id', '<', 6]
                            ])->groupBy('language_code')->orderBy('order', 'ASC')->get();

            if (is_null($languages)) {
                return response()->json([
                            'status' => 'error',
                            'declaration' => 'language_not_found',
                            'payload' => ['message' => 'Sorry, language not found. Please try again.'],
                                ], 404);
            }
            $languagesArr = $languages->toArray();
            $i = 0;
            foreach ($languagesArr as $key => $val) {
                $languagesArr[$i]['country_id'] = $val['countries']['id'];
                $languagesArr[$i]['country_name'] = $val['countries']['name'];
                $languagesArr[$i]['flag'] = str_replace('http://', 'https://', $request->root()) . '/assets/flags/' . $val['countries']['flag'];
                unset($languagesArr[$i]['countries']);
                $i++;
            }
            return response()->json([
                        'status' => 'success',
                        'declaration' => 'language_loaded',
                        'payload' => $languagesArr,
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
     * Get term based on language code id
     * 
     * @return  void
     * @author  Amit Panchal <amit@unoindia.co>
     * @version 0.0.1
     */
    public function getTermByLanguage(Request $request) {
        $this->validate($request, [
            'lan_id' => 'required',
        ]);

        try {
            $langId = $request->input('lan_id');

            $term = TermTranslation::with('terms')->where('locales_id', '=', $langId)->get();
            $payload = [];
            if (!is_null($term)) {
                $termArr = $term->toArray();
                foreach ($termArr as $key => $val) {
                    $payload[] = ['term' => $val['terms']['term'], 'translation' => $val['translation']];
                }
            }
            return response()->json([
                        'status' => 'success',
                        'declaration' => 'term_loaded',
                        'payload' => $payload,
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
     * Get all translation with language code
     * 
     * @return  void
     * @author  Amit Panchal <amit@unoindia.co>
     * @version 0.0.1
     */
    public function getAllTermByLanguage(Request $request) {

        try {

            $term = Locales::with(['termTranslations' => function($query) {
                            $query->with('terms');
                        }])->get();


            $payload = [];
            if (!is_null($term)) {
                $termArr = $term->toArray();
                foreach ($termArr as $key => $val) {
                    $languageCode = $val['language_code'];
                    $lang = [];
                    foreach ($val['term_translations'] as $Termkey => $Termval) {
                        $lang = array_merge($lang, [$Termval['terms']['term'] => $Termval['translation']]);
                    }
                    $payload[$languageCode] = (object)$lang;
                }
            }
            return response()->json([
                        'status' => 'success',
                        'declaration' => 'term_loaded',
                        'payload' => $payload,
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

}
