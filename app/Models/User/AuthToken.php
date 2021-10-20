<?php
/**
 * Created by PhpStorm.
 * User: jenish
 * Date: 02-05-2016
 * Time: PM 12:28
 */

namespace App\Models\User;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use DB;
use SpoonityService;

/**
 * Modal class for user's auth tokens
 *
 * @property string user_token
 * @property int    z_user_id_fk
 * @property string user_token_attribute
 * @property int    z_app_id_fk
 * @property mixed  token_expired_on
 * @property mixed  added_on
 * @property mixed  modified_on
 */
class AuthToken extends Model
{
    protected $table   = 'user_auth_tokens';
    protected $guarded   = [];





    /**
     * Check if user's auth token is expired or not.
     *
     * @return bool
     */
    public function isExpired()
    {
        //$sql = "DELETE FROM auth_tokens WHERE expired_at < '" . Carbon::now()->addMonths(-1)->toDateString() . "'";
        //DB::delete(DB::raw($sql));
        $now        = time();
        $expired_at = strtotime($this->expiry);
        if ($now > $expired_at) {
            return TRUE;
        }

        return FALSE;
    }

    /**
     *Extend auth token's expiry.
     */
    public function extendExpiry()
    {
        $this->expiry = Carbon::now()->addDay(30);
        $this->save();
    }



    /**
     * Map with child user class.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo('App\Models\User\User','user_id');
    }

}