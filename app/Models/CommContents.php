<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $name
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon $deleted_at
 * @property-read UserContentChannels[] $userContentChannels
 */
class CommContents extends Model
{
    
    protected $table = 'comm_contents';
    
    /**
     * @var array
     */
    protected $fillable = ['id', 'name', 'deleted_at'];

    /**
     * @var array
     */
    protected $dates = ['created_at', 'updated_at', 'deleted_at'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    
     /**
     * One to many relationship with user.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     * @author  Amit Panchal<amit@unoindia.co>
     * @version 0.0.1
     */
    public function userContentChannels()
    {        
        return $this->hasMany('App\Models\user\UserContentChannels', 'content_id');
    }
}
