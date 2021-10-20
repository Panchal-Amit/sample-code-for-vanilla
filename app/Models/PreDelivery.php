<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $manufacturer_id
 * @property int $user_id
 * @property string $owner_sign
 * @property string $representative_sign
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read Manufacturers $manufacturers
 * @property-read Users $users
 */
class PreDelivery extends Model
{
    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 'pre_delivery';

    /**
     * @var array
     */
    protected $fillable = ['id', 'manufacturer_id', 'user_id', 'owner_sign', 'representative_sign'];

    /**
     * @var array
     */
    protected $dates = ['created_at', 'updated_at'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function manufacturers()
    {
        return $this->belongsTo('Manufacturers', 'manufacturer_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function users()
    {
        return $this->belongsTo('Users', 'user_id');
    }
}
