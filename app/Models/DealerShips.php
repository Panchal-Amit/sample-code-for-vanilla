<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $dfx_id
 * @property string $name
 * @property string $email
 * @property string $phone_number
 * @property string $sms_number
 * @property string $roadside_assist
 * @property string $address_1
 * @property string $address_2
 * @property string $city
 * @property string $postal_code
 * @property string $country
 * @property float $latitude
 * @property float $longitude
 * @property boolean $status
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read Applications[] $applications
 * @property-read SalesRep[] $salesReps
 * @property-read UserDealerships[] $userDealerships
 */
class DealerShips extends Model
{
    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 'dealerships';

    /**
     * @var array
     */
    protected $fillable = ['id', 'dfx_id', 'name', 'email', 'phone_number', 'sms_number', 'roadside_assist', 'address_1', 'address_2', 'city', 'postal_code', 'country', 'latitude', 'longitude', 'status','oem_name','oem_logo','oem_id'];

    /**
     * @var array
     */
    protected $dates = ['created_at', 'updated_at'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function applications()
    {                
        return $this->hasMany('App\Models\Application', 'dealership_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function salesReps()
    {
        return $this->hasMany('App\Models\SalesRep', 'dealership_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function userDealerships()
    {
        //return $this->hasMany('App\Models\UserDealerships', 'dealership_id');
    }

   /**
     * One to many relationship with vehicles.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     * @author  Amandeep Singh <amandeep@unoapp.com>
     * @version 0.0.1
     */
    public function vehicles()
    {
        return $this->hasMany('App\Models\Vehicles', 'dealership_id');
    }
    
    /**
     * One to many relationship with manufacturers.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     * @author  Amit Panchal <amit@unoindia.co>
     * @version 0.0.1
     */
    public function manufacturers() {
        return $this->hasMany('App\Models\Manufacturers', 'dealership_id');
    }

}
