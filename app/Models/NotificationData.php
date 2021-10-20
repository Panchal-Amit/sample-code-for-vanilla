<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $notification_id
 * @property int $vehicle_id
 * @property int $user_id
 * @property mixed $status
 * @property string $data
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read Notifications $notifications
 * @property-read Users $users
 * @property-read Vehicles $vehicles
 */
class NotificationData extends Model {

    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 'notification_data';

    /**
     * @var array
     */
    protected $fillable = ['id', 'notification_id', 'vehicle_id', 'user_id', 'status', 'data','image','token','priority'];

    /**
     * @var array
     */
    protected $dates = ['created_at', 'updated_at'];

    /**
     * Notification data belongs to notifications
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     * @author  Amit Panchal <amit@unoindia.co>
     * @version 0.0.1
     */
    public function notifications() {
        return $this->belongsTo('App\Models\Notifications', 'notification_id');
    }

    /**
     * Notification will be read by user
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     * @author  Amit Panchal <amit@unoindia.co>
     * @version 0.0.1
     */
    public function users() {
        return $this->belongsTo('App\Models\Users', 'user_id');
    }

    /**
     * Notification will be for specific vehicle
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     * @author  Amit Panchal <amit@unoindia.co>
     * @version 0.0.1
     */
    public function vehicles() {
        return $this->belongsTo('App\Models\Vehicles', 'vehicle_id');
    }

}
