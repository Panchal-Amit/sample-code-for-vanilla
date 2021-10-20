<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $env_id
 * @property string $name
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read Environment $environment
 */
class MainManufacturer extends Model {

    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 'main_manufacturer';

    /**
     * @var array
     */
    protected $fillable = ['id', 'env_id', 'name'];

    /**
     * @var array
     */
    protected $dates = ['created_at', 'updated_at'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function environment() {
        return $this->belongsTo('App\Models\Environment', 'env_id');
    }

}
