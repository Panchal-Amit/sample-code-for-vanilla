<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $user_id
 * @property int $code
 * @property mixed $status
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class AccessCode extends Model
{
    /**
     * @var array
     */
    protected $fillable = ['id', 'user_id', 'code', 'status'];

    /**
     * @var array
     */
    protected $dates = ['created_at', 'updated_at'];
    
    /**
     * Each access code has one user
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     * @author  Amit Panchal <amit@unoindia.co>
     * @version 0.0.1
     */
    public function User() {
        return $this->hasOne('App\Models\User\User', 'id');
    }

}
