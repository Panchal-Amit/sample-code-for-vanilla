<?php

namespace App\Models\User;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $dealership_id
 * @property int $user_id
 * @property boolean $preferred
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon $deleted_at
 * @property-read Dealerships $dealerships
 * @property-read Users $users
 */
class UserDealerShips extends Model
{
    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 'user_dealerships';

    /**
     * @var array
     */
    protected $fillable = ['id', 'dealership_id', 'user_id', 'preferred', 'deleted_at','user_dfx_id'];

    /**
     * @var array
     */
    protected $dates = ['created_at', 'updated_at', 'deleted_at'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function dealerships()
    {
        return $this->belongsTo('App\Models\DealerShips', 'dealership_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function users()
    {
        return $this->belongsTo('App\Models\User\User', 'user_id');
    }
}
