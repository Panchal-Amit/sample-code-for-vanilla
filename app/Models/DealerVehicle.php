<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $dealer_id
 * @property int $manufacturer_id
 * @property int $vehicle_id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read Dealerships $dealerships
 * @property-read Manufacturers $manufacturers
 * @property-read Vehicles $vehicles
 */
class DealerVehicle extends Model {

    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 'dealer_vehicle';

    /**
     * @var array
     */
    protected $fillable = ['id', 'dealer_id', 'manufacturer_id', 'vehicle_id'];

    /**
     * @var array
     */
    protected $dates = ['created_at', 'updated_at'];

    /**
     * Relationship with dealer.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     * @author  Amit Panchal <amit@unoindia.co>
     * @version 0.0.1
     */
    public function dealerships() {
        return $this->belongsTo('App\Models\DealerShips', 'dealer_id');
    }

    /**
     * Relationship with manufacturers.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     * @author  Amit Panchal <amit@unoindia.co>
     * @version 0.0.1
     */
    public function manufacturers() {
        return $this->belongsTo('App\Models\Manufacturers', 'manufacturer_id');
    }

    /**
     * Relationship with vehicle.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     * @author  Amit Panchal <amit@unoindia.co>
     * @version 0.0.1
     */
    public function vehicles() {
        return $this->belongsTo('App\Models\Vehicles', 'vehicle_id');
    }

}
