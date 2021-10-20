<?php

namespace App\Models\User;

use Illuminate\Database\Eloquent\Model;

class UserBilling extends Model
{

    public function user(){
        return $this->belongsTo('App\Models\User\User','user_id','id');
    }


}
