<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $dealership_id
 * @property int $dfx_id
 * @property string $name
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read Dealerships $dealerships
 */
class Manufacturers extends Model {

    /**
     * @var array
     */
    protected $fillable = ['id', 'dealership_id', 'dfx_id', 'name'];

    /**
     * @var array
     */
    protected $dates = ['created_at', 'updated_at'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function dealerships() {

        return $this->belongsTo('App\Models\Dealerships', 'dealership_id');
    }

    /**
     * Relationship with vehicle.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     * @author  Amit Panchal <amit@unoindia.co>
     * @version 0.0.1
     */
    public function vehicles() {
        return $this->hasMany('App\Models\Vehicles', 'manufacturer_id');
    }

    /**
     * Relationship with question.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     * @author  Amit Panchal <amit@unoindia.co>
     * @version 0.0.1
     */
    public function questions() {
        return $this->hasMany('App\Models\Questions', 'manufacturer_id');
    }

    /**
     * Relationship with preDelivery.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     * @author  Amit Panchal <amit@unoindia.co>
     * @version 0.0.1
     */
    public function preDeliveries() {
        return $this->hasMany('App\Models\PreDelivery', 'manufacturer_id','dfx_id');
    }

}
