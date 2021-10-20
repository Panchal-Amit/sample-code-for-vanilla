<?php

namespace App\Models\User;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $channel_id
 * @property int $content_id
 * @property int $user_id
 * @property boolean $status
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read CommChannels $commChannels
 * @property-read CommContents $commContents
 * @property-read Users $users
 */
class UserContentChannels extends Model
{
    
    protected $table = 'user_content_channels';
    /**
     * @var array
     */
    protected $fillable = ['id', 'channel_id', 'content_id', 'user_id', 'status'];

    /**
     * @var array
     */
    protected $dates = ['created_at', 'updated_at'];
    
    
    /** Relation with channel.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     * @author Amit Panchal <amit@unoindia.co>
     * @version 0.0.1
     */
    public function commChannels()
    {
        return $this->belongsTo('App\Models\CommChannels', 'channel_id');
    }
    
    
    /** Relation with content.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     * @author Amit Panchal <amit@unoindia.co>
     * @version 0.0.1
     */
    public function commContents()
    {
        return $this->belongsTo('App\Models\CommContents', 'content_id');
    }
    
    /**
     * Relation with user.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     * @author Amit Panchal <amit@unoindia.co>
     * @version 0.0.1
     */
    public function users()
    {
        return $this->belongsTo('App\Models\User\Users', 'user_id');
    }
}
