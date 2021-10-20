<?php

use Illuminate\Database\Seeder;
use App\Models\Locales;

class LocalesTableSeeder extends Seeder {

    /**
     * Run the database seeds to add data to locale table.
     * 
     * @return  void
     * @author  Amit Panchal <amit@unoindia.co>
     * @author  Jenish Panchal <jenishp@unoindia.co>
     * @version 0.0.1
     */
    public function run() {

        Locales::firstOrcreate(array('language_code' => 'en-CA', 'language_title' => 'English', 'country_id' => '1', 'default' => '1', 'order' => 2));
        Locales::firstOrcreate(array('language_code' => 'fr-CA', 'language_title' => 'French', 'country_id' => '1', 'default' => '0', 'order' => 3));
        Locales::firstOrcreate(array('language_code' => 'en-US', 'language_title' => 'English', 'country_id' => '2', 'default' => '1', 'order' => 1));
    }

}
