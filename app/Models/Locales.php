<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $country_id
 * @property string $language_code
 * @property boolean $default
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read Countries $countries
 * @property-read TermTranslation[] $termTranslations
 */
class Locales extends Model
{
    /**
     * @var array
     */
    protected $fillable = ['id', 'country_id', 'language_code', 'default','language_title','order'];

    /**
     * @var array
     */
    protected $dates = ['created_at', 'updated_at'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function countries()
    {
        return $this->belongsTo('App\Models\Countries', 'country_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function termTranslations()
    {
        return $this->hasMany('App\Models\TermTranslation');
    }
}
