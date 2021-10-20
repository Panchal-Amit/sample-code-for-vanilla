<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property mixed $action_type
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read NotificationData[] $notificationDatas
 */
class Notifications extends Model
{
    /**
     * @var array
     */
    protected $fillable = ['id', 'action_type'];

    /**
     * @var array
     */
    protected $dates = ['created_at', 'updated_at'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function notificationDatas()
    {
        return $this->hasMany('App\Models\NotificationData', 'notification_id');
    }
}
