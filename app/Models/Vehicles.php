<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $vin
 * @property string $make
 * @property string $model
 * @property \Carbon\Carbon $year
 * @property string $transmissions
 * @property string $cylinder
 * @property string $drive
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read UserVehicles[] $userVehicles
 */
class Vehicles extends Model {

    protected $table = 'vehicles';

    /**
     * @var array
     */
    protected $fillable = ['id', 'vin', 'make', 'model', 'year', 'transmissions', 'cylinder', 'drive', 'dealership_id', 'dfx_id', 'color', 'manufacturer_id', 'image', 'odometer'];

    /**
     * @var array
     */
    protected $dates = ['created_at', 'updated_at'];

    /**
     * Relationship with user
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function userVehicles() {
        return $this->hasMany('App\Models\UserVehicles', 'vehicle_id');
    }

    /**
     * Relationship with dealer
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     * @author  Amit Panchal <amit@unoindia.co>
     * @version 0.0.1
     */
    public function dealerVehicles() {
        return $this->hasMany('App\Models\DealerVehicle', 'vehicle_id');
    }

}
