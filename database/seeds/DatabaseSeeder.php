<?php

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder {

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run() {
        
        
        /***************************************         
         * Need to remove all commented seeds after confirmation.
         * These two seeds are moved on survey-engine-dfx so now we comment it.
         * ***************************************/
        
        //$this->call('QuestionTableSeeder');
        //$this->call('QuestionOptionTableSeeder');                
        //$this->call('LocalesTableSeeder');//Moved to CountriesTableSeeder         
        //$this->call('MainManufacturerTableSeeder'); //Moved to EnvironmentTableSeeder
        
        $this->call('BrandPropertyTableSeeder');
        $this->call('NotificationsTableSeeder');
        $this->call('CountriesTableSeeder'); 
        $this->call('EnvironmentTableSeeder');
    }

}
