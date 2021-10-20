<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $question_id
 * @property int $question_option_id
 * @property int $user_id
 * @property string $response
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read Questions $questions
 * @property-read QuestionOptions $questionOptions
 * @property-read Users $users
 */
class QuestionsResponse extends Model {

    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 'question_response';

    /**
     * @var array
     */
    protected $fillable = ['id', 'question_id', 'question_option_id', 'user_id', 'response'];

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
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function questionOptions() {
        return $this->belongsTo('App\Models\QuestionOptions', 'question_option_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function users() {
        return $this->belongsTo('App\Models\User\Users', 'user_id');
    }

}
