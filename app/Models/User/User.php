<?php

namespace App\Models\User;

use Illuminate\Database\Eloquent\Model;

class User extends Model {

    protected $table = 'users';

    /**
     * @var array
     */
    protected $fillable = ['id', 'first_name', 'last_name', 'email', 'phone', 'dob', 'home', 'password', 'enrolled_in_ice_app'];

    /**
     * @var array
     */
    protected $dates = ['dob', 'created_at', 'updated_at'];

    /**
     * Relation with user auth tokens.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\hasMany
     */
    public function authtoken() {
        return $this->hasMany('App\Models\User\AuthToken', 'user_id', 'id');
    }
    
    /**
     * Relation with user Addresses.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\hasMany
     */
    public function userAddresses() {
        return $this->hasMany('App\Models\User\UserAddress', 'user_id', 'id');
    }

    /**
     * Relation with user billing.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\hasMany
     */
    public function userBillings() {
        return $this->hasMany('App\Models\User\UserBilling', 'user_id', 'id');
    }

    /**
     * Relation with vehicles.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\belongsToMany
     * @author Amandeep Singh <amandeep@unoapp.com>
     * @version 0.0.1
     */
    public function vehicles() {
        return $this->belongsToMany('App\Models\Vehicles', 'user_vehicles', 'user_id', 'vehicle_id')->whereNull('deleted_at')->withTimestamps();
    }

    /**
     * Relation with user content channel.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\hasMany
     * @author Amit Panchal <amit@unoindia.co>
     * @version 0.0.1
     */
    public function UserContentChannels() {
        return $this->hasMany('App\Models\User\UserContentChannels', 'user_id', 'id');
    }

    /**
     * Each user have one access code
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     * @author  Amit Panchal <amit@unoindia.co>
     * @version 0.0.1
     */
    public function AccessCode() {
        return $this->hasOne('App\Models\AccessCode', 'user_id');
    }

    /**
     * Relation with dealer ship
     *      
     * @return \Illuminate\Database\Eloquent\Relations\hasMany
     * @author Amit Panchal <amit@unoindia.co>
     * @version 0.0.1
     */
    public function userDealerships() {
        return $this->hasMany('App\Models\User\UserDealerShips', 'user_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function preDeliveries() {
        return $this->hasMany('App\Models\PreDelivery', 'user_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function questionResponses() {
        return $this->hasMany('App\Models\QuestionsResponse', 'user_id');
    }
   /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function userDocuments() {
        return $this->hasMany('App\Models\UserDocument', 'user_id');
    }

}
