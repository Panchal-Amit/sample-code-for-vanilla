<?php

use Illuminate\Database\Seeder;
use App\Models\QuestionsOptions;

class QuestionOptionTableSeeder extends Seeder {

    /**
     * Run the database seeds to add data to question options table.
     *
     * @return  void
     * @author  Amit Panchal <amit@unoindia.co>
     * @version 0.0.1
     */
    public function run() {

        QuestionsOptions::create(array('question_id' => '5', 'option_text' => 'Operation of Remote Door Lock/Unlock'));
        QuestionsOptions::create(array('question_id' => '5', 'option_text' => 'Demo Trunk and Fuel Door Openers'));
        QuestionsOptions::create(array('question_id' => '5', 'option_text' => 'Demo Trunk and Fuel Door Openers'));
        QuestionsOptions::create(array('question_id' => '5', 'option_text' => 'Demo Child Locks(if equipped)'));
        QuestionsOptions::create(array('question_id' => '5', 'option_text' => 'Demo Hood and Parking Brake Releases'));
        QuestionsOptions::create(array('question_id' => '5', 'option_text' => 'Demo Seat Adjustments(Memory if equipped)'));
        QuestionsOptions::create(array('question_id' => '5', 'option_text' => 'Demo Heated Seat Controls(if equipped)'));
        QuestionsOptions::create(array('question_id' => '5', 'option_text' => 'Demo Power Window/Lock Controls'));
        QuestionsOptions::create(array('question_id' => '5', 'option_text' => 'Demo Slide/Rear View Mirrors'));
        QuestionsOptions::create(array('question_id' => '5', 'option_text' => 'Demo Headlight/Wiper Controls'));
        QuestionsOptions::create(array('question_id' => '5', 'option_text' => 'Demo Cruise Control'));
        QuestionsOptions::create(array('question_id' => '5', 'option_text' => 'Demo Steering Wheel Adjustments'));
        QuestionsOptions::create(array('question_id' => '5', 'option_text' => 'Demo Blue-Tooth(if equipped)'));
        QuestionsOptions::create(array('question_id' => '5', 'option_text' => 'Demo Nav System (if equipped)'));
        QuestionsOptions::create(array('question_id' => '5', 'option_text' => 'Demo Radio Controls'));
        QuestionsOptions::create(array('question_id' => '5', 'option_text' => 'Demo Heat/A-C Controls'));
    }

}
