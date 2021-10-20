<?php

use Illuminate\Database\Seeder;
use App\Models\Countries;

class CountriesTableSeeder extends Seeder {

    /**
     * Run the database seeds to add data to countries table.
     *
     * @return void
     * @author  Amit Panchal <amit@unoindia.co>
     * @version 0.0.1
     */
    public function run() {
        
        $country1 = Countries::firstOrcreate([
                    'name' => 'Canada',
                    'flag' => 'canada.png'
        ]);

        $country1->locales()->firstOrcreate([
            'language_code' => 'en-CA',
            'language_title' => 'English',
                ], [
            'default' => '1',
            'order' => 2
        ]);

        $country1->locales()->firstOrcreate([
            'language_code' => 'fr-CA',
            'language_title' => 'French',
                ], [
            'default' => '0',
            'order' => 3
        ]);

        $country2 = Countries::firstOrcreate([
                    'name' => 'Us',
                    'flag' => 'us.png'
        ]);

        $country2->locales()->firstOrcreate([
            'language_code' => 'en-US',
                ], [
            'language_title' => 'English',
            'country_id' => '2',
            'default' => '1',
            'order' => 1
        ]);
    }

}
