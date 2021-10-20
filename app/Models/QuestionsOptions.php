<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $question_id
 * @property string $option_text
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read Questions $questions
 * @property-read QuestionResponse[] $questionResponses
 */
class QuestionsOptions extends Model {

    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 'question_options';

    /**
     * @var array
     */
    protected $fillable = ['id', 'question_id', 'option_text'];

    /**
     * @var array
     */
    protected $dates = ['created_at', 'updated_at'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function questions() {
        return $this->belongsTo('App\Models\Questions', 'question_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function questionResponses() {
        return $this->hasMany('App\Models\QuestionsResponse', 'question_option_id');
    }

}
