<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $name
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read MainManufacturer[] $mainManufacturers
 */
class Environment extends Model
{
    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 'environment';

    /**
     * @var array
     */
    protected $fillable = ['id', 'name'];

    /**
     * @var array
     */
    protected $dates = ['created_at', 'updated_at'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function mainManufacturers()
    {
        return $this->hasMany('App\Models\MainManufacturer', 'env_id');
    }
}
