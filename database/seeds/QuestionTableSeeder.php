<?php

use Illuminate\Database\Seeder;
use App\Models\Questions;
use App\Models\Manufacturers;
use App\Models\DealerShips;

class QuestionTableSeeder extends Seeder {

    /**
     * Run the database seeds to add data to question table.
     * 
     * @return  void
     * @author  Amit Panchal <amit@unoindia.co>
     * @version 0.0.1
     */
    public function run() {
        //Currently added question for first manufacturer in manufacturer table
        $manufacturer = Manufacturers::get()->first();
        if (is_null($manufacturer)) {
            $dealership = DealerShips::firstOrCreate(['dfx_id' => '4607']);
            $manufacturer = Manufacturers::create([
                        'dfx_id' => 41,
                        'dealership_id' => $dealership->id,
                        'name' => 'NISSAN'
            ]);
        }
        $manufacturerId = $manufacturer->id;
        Questions::create(array('manufacturer_id' => $manufacturerId, 'type' => 'mcq', 'question_text' => 'Satisfied with the condition of your {{make}} {{year}} {{model}} at the time of delivery?', 'is_required' => 1));
        Questions::create(array('manufacturer_id' => $manufacturerId, 'type' => 'mcq', 'question_text' => 'Vehicle delivered with a full tank of gas or a gas voucher?', 'is_required' => 1));
        Questions::create(array('manufacturer_id' => $manufacturerId, 'type' => 'mcq', 'question_text' => 'Did you confirm you’re first service appointment?', 'is_required' => 1,'default'=>true));
        Questions::create(array('manufacturer_id' => $manufacturerId, 'type' => 'mcq', 'question_text' => 'Did you register for Entune.?', 'is_required' => 1,'default'=>true));
        Questions::create(array('manufacturer_id' => $manufacturerId, 'type' => 'mcq', 'question_text' => 'Features and Controls Review', 'is_required' => 1));
    }

}
