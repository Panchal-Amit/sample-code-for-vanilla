<?php

namespace App\Models\User;

use Illuminate\Database\Eloquent\Model;

class UserAddress extends Model
{
    protected $table = 'user_addresses';
    /**
     * @var array
     */
    protected $fillable = ['id','user_id','address_1','address_2','city','state','postal_code','country','latitude','longitude','province','county'];
    
    /**
     * @var array
     */
    protected $dates = ['created_at', 'updated_at'];
    
    public function user(){
        return $this->belongsTo('App\Models\User\User','user_id','id');
    }

    
    

}
