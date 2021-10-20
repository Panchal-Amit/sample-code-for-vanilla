<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\App;
use App\Models\Notifications;
use App\Models\NotificationData;
use App\Models\Vehicles;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class NotificationController extends Controller {

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct() {
        
    }

    /**
     * Push notification     
     * 
     * @return  void
     * @author  Amit Panchal <amit@unoindia.co>
     * @version 0.0.1
     */
    public function pushNotification(Request $request) {
        $this->validate($request, [
            'action_type' => 'required|string',
            'data' => 'required',
            'image' => 'nullable',
            'vin' => 'required|alpha_num|max:17',
            'token' => 'nullable',
            'priority' => 'required|integer'
        ]);
        $action_type = $request->input('action_type');
        $data = $request->input('data');
        $file = $request->file('image');
        $vin = $request->input('vin');
        $token = (!is_null($request->input('token'))) ? $request->input('token') : '';
        $priority = $request->input('priority');

        $notification = Notifications::where('action_type', '=', $action_type)->first();
        if (is_null($notification)) {
            return response()->json([
                        'status' => 'error',
                        'declaration' => 'notification_not_found',
                        'payload' => [
                            'message' => 'Sorry, notification not found. Please try again.'
                        ]
                            ], 404);
        }
        $vehicle = Vehicles::where('vin', '=', $vin)->first();
        if (is_null($vehicle)) {
            return response()->json([
                        'status' => 'error',
                        'declaration' => 'vehicle_not_found',
                        'payload' => [
                            'message' => 'Sorry, vehicle not found. Please try again.'
                        ]
                            ], 404);
        }
        $imageName = '';
        if (!is_null($file)) {
            // Right now assuming there will be only one image upload 
            // Temporary disable code for file system
            //$image = Storage::disk(config('filesystems.default'))->put('notifications_images', $file);
            //$imageurl = str_replace("storage", "assets", Storage::url($image));
            //$imageName = basename($image);
        }
        $notificationArr = [
            'vehicle_id' => $vehicle->id,
            'data' => $data,
            'image' => $imageName,
            'token' => $token,
            'status' => 'unread',
            'priority' => $priority
        ];
        $notificationData = $notification->notificationDatas()->Create($notificationArr);
        $playload = [];
        if ($notificationData->id > 0) {
            $playload['id'] = $notificationData->id;
            $playload['vin'] = $vin;
            $playload['vehicle_id'] = $vehicle->id;
            $playload['token'] = $token;
            $playload['image'] = $imageName;
            $playload['status'] = 'unread';
            $playload['priority'] = $priority;
            $playload['data'] = json_decode($data, true);
        }
        return response()->json([
                    "status" => "success",
                    "declaration" => "notification_added",
                    "payload" => $playload,
                        ], 200);
    }

    /**
     * Pull notification     
     * @notes Still working on this
     * @return  void
     * @author  Amit Panchal <amit@unoindia.co>
     * @version 0.0.1
     */
    public function pullNotification(Request $request) {
        $this->validate($request, [
            'vin' => 'required|alpha_num|max:17'
        ]);

        $action_type = (!is_null($request->input('action_type'))) ? $request->input('action_type') : '';
        $vin = $request->input('vin');
        $status = (!is_null($request->input('status'))) ? $request->input('status') : '';

        if (trim($action_type) != '') {
            $notification = Notifications::where('action_type', '=', $action_type)->first();
            if (is_null($notification)) {
                return response()->json([
                            'status' => 'error',
                            'declaration' => 'notification_not_found',
                            'payload' => [
                                'message' => 'Sorry, notification not found. Please try again.'
                            ]
                                ], 404);
            }
        }
        $vehicle = Vehicles::where('vin', '=', $vin)->first();
        if (is_null($vehicle)) {
            return response()->json([
                        'status' => 'error',
                        'declaration' => 'vehicle_not_found',
                        'payload' => [
                            'message' => 'Sorry, vehicle not found. Please try again.'
                        ]
                            ], 404);
        }
        $conditionArr = [];
        $conditionArr['vehicle_id'] = $vehicle->id;

        if (trim($action_type) != '') {
            $conditionArr['notification_id'] = $notification->id;
        }
        if (trim($status) != '') {
            $conditionArr['status'] = $status;
        }


        $notificationsObj = Notifications::whereHas('notificationDatas', function ($query) use($conditionArr) {
                    
                })->with(['notificationDatas' => function ($query) use($conditionArr) {
                        $query->where($conditionArr)->orderBy('created_at', 'desc');
                    }])->get();

        $payload = [];
        $userReadNotification = [];
        if (!is_null($notificationsObj)) {
            $notificationsArr = $notificationsObj->toArray();
            foreach ($notificationsArr as $key => $val) {
                if (count($val['notification_datas']) > 0) {
                    $individual = [];
                    $valData = $val['notification_datas'][0];
                    if ($valData['status'] != 'dismiss' && !is_null($valData['data']) > 0) {
                        $individual['notification_id'] = $valData['id'];
                        $individual['status'] = $valData['status'];
                        $individual['type'] = $val['action_type'];
                        $data = json_decode($valData['data'], true);
                        if (isset($data['Body']['appointment_date'])) {
                            $data['Body']['appointment_date'] = Carbon::parse($data['Body']['appointment_date'])->format('d-m-Y H:i');
                        }
                        $individual['data'] = $data;
                        $individual['created_at'] = date("d-m-Y H:i:s", strtotime($valData['created_at']));
                        $payload[$valData['id']] = $individual;
                    }
                }
            }
        }
        rsort($payload);
        return response()->json([
                    'status' => 'success',
                    'declaration' => 'notification_found',
                    'payload' => ['message' => 'Notification has been loaded', 'notifications' => $payload]
                        ], 200);
    }

    /**
     * Get user notification
     * 
     * @return  void
     * @author  Amit Panchal <amit@unoindia.co>
     * @version 0.0.1
     */
    public function getAllNotification(Request $request) {
        $this->validate($request, [
            'vin' => 'required|alpha_num|max:17'
        ]);

        $vin = $request->input('vin');

        $vehicle = Vehicles::where('vin', '=', $vin)->first();
        if (is_null($vehicle)) {
            return response()->json([
                        'status' => 'error',
                        'declaration' => 'vehicle_not_found',
                        'payload' => [
                            'message' => 'Sorry, vehicle not found. Please try again.'
                        ]
                            ], 404);
        }
        $notificationsObj = Notifications::whereHas('notificationDatas', function ($query) use($vehicle) {
                    
                })->with(['notificationDatas' => function ($query) use($vehicle) {
                        $query->where(['vehicle_id' => $vehicle->id])->orderBy('created_at', 'desc');
                    }])->get();

                $payload = [];
                $userReadNotification = [];
                if (!is_null($notificationsObj)) {
                    $notificationsArr = $notificationsObj->toArray();
                    foreach ($notificationsArr as $key => $val) {
                        if (count($val['notification_datas']) > 0) {
                            $individual = [];
                            $valData = $val['notification_datas'][0];
                            if ($valData['status'] != 'dismiss' && !is_null($valData['data']) > 0) {
                                $userReadNotification[] = $valData['id'];
                                $valData[$val['action_type'] . '_data'] = json_decode($valData['data'], true);
                                unset($valData['data']);
                                $individual['notification_id'] = $valData['id'];
                                $individual['priority'] = $valData['priority'];
                                $individual['type'] = $val['action_type'];
                                $individual[$val['action_type']] = $valData;
                                $userReadNotification[] = $valData['id'];
                                $payload[] = $individual;
                            }
                        }
                    }
                }
                if (count($userReadNotification) > 0) {
                    $user = App::make('AuthenticatedUser');
                    NotificationData::whereIn('id', $userReadNotification)->update(['user_id' => $user->id, 'status' => 'read']);
                }
                return response()->json([
                            'status' => 'success',
                            'declaration' => 'notification_found',
                            'payload' => ['message' => 'Notification has been loaded', 'notifications' => $payload]
                                ], 200);
            }

            /**
             * To dismiss notification
             * 
             * @return  void
             * @author  Amit Panchal <amit@unoindia.co>
             * @version 0.0.1
             */
            public function dismissNotification(Request $request) {
                $this->validate($request, [
                    'notification_id' => 'required|integer'
                ]);
                $id = $request->input('notification_id');

                NotificationData::where('id', '=', $id)->update(['status' => 'dismiss']);
                return response()->json([
                            "status" => "success",
                            "declaration" => "notification_dismissed",
                            "payload" => $id,
                                ], 200);
            }

            /**
             * To update notification
             * 
             * @return  void
             * @author  Amit Panchal <amit@unoindia.co>
             * @version 0.0.1
             */
            public function updatStatusNotification(Request $request) {
                $this->validate($request, [
                    'notification_id' => 'required|integer',
                    'status' => 'required'
                ]);

                $id = $request->input('notification_id');
                $status = $request->input('status');

                NotificationData::where('id', '=', $id)->update(['status' => $status]);
                return response()->json([
                            "status" => "success",
                            "declaration" => "notification_updated",
                            "payload" => $id,
                                ], 200);
            }

        }
        