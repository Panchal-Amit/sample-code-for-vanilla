<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $dealership_id
 * @property int $dfx_id
 * @property string $username
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read Dealerships $dealerships
 */
class SalesRep extends Model {

    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 'sales_rep';

    /**
     * @var array
     */
    protected $fillable = ['id', 'dealership_id', 'dfx_id', 'username', 'first_name', 'last_name'];

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

}
