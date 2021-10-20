<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $manufacturer_id
 * @property mixed $type
 * @property string $question_text
 * @property boolean $is_required
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read Manufacturers $manufacturers
 * @property-read QuestionOptions[] $questionOptions
 * @property-read QuestionResponse[] $questionResponses
 */
class Questions extends Model
{
    /**
     * @var array
     */
    protected $fillable = ['id', 'manufacturer_id', 'type', 'question_text', 'is_required'];

    /**
     * @var array
     */
    protected $dates = ['created_at', 'updated_at'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function manufacturers()
    {        
        return $this->belongsTo('App\Models\Manufacturers', 'manufacturer_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function questionOptions()
    {
        return $this->hasMany('App\Models\QuestionsOptions', 'question_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function questionResponses()
    {
        return $this->hasMany('App\Models\QuestionsResponse', 'question_id');
    }
}
