<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $user_id
 * @property int $vehicle_id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon $deleted_at
 * @property-read Users $users
 * @property-read Vehicles $vehicles
 */
class UserVehicles extends Model
{
    use SoftDeletes;
    
    protected $table = 'user_vehicles';
    /**
     * @var array
     */
    protected $fillable = ['id', 'user_id', 'vehicle_id', 'deleted_at'];

    /**
     * @var array
     */
    protected $dates = ['created_at', 'updated_at', 'deleted_at'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function users()
    {
        return $this->belongsTo('App\Models\User\Users', 'user_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function vehicles()
    {
        return $this->belongsTo('App\Models\Vehicles', 'vehicle_id');
    }
}
