<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $locales_id
 * @property int $term_id
 * @property int $created_by
 * @property int $updated_by
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon $deleted_at
 * @property-read Locales $locales
 * @property-read Terms $terms
 */
class TermTranslation extends Model {

    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 'term_translation';

    /**
     * @var array
     */
    protected $fillable = ['id', 'locales_id', 'term_id', 'translation', 'created_by', 'updated_by', 'deleted_at'];

    /**
     * @var array
     */
    protected $dates = ['created_at', 'updated_at', 'deleted_at'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function locales() {
        return $this->belongsTo('App\Models\Locales');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function terms() {
        return $this->belongsTo('App\Models\Terms', 'term_id');
    }

}
