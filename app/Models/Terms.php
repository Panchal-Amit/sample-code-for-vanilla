<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $term
 * @property int $created_by
 * @property int $updated_by
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon $deleted_at
 * @property-read TermTranslation[] $termTranslations
 */
class Terms extends Model
{
    /**
     * @var array
     */
    protected $fillable = ['id', 'term', 'created_by', 'updated_by', 'deleted_at'];

    /**
     * @var array
     */
    protected $dates = ['created_at', 'updated_at', 'deleted_at'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function termTranslations()
    {
        return $this->hasMany('App\Models\TermTranslation', 'term_id');
    }
}
