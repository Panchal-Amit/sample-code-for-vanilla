<?php

use Illuminate\Database\Seeder;
use App\Models\Environment;

class EnvironmentTableSeeder extends Seeder {

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run() {
        
        $env1 = Environment::firstOrcreate(array('name' => 'Prod1'));       
        $env1->mainManufacturers()->firstOrcreate(array('name' => 'ALFA ROMEO'));
        $env1->mainManufacturers()->firstOrcreate(array('name' => 'PLYMOUTH'));
        $env1->mainManufacturers()->firstOrcreate(array('name' => 'CHRYSLER'));
        $env1->mainManufacturers()->firstOrcreate(array('name' => 'DODGE'));
        $env1->mainManufacturers()->firstOrcreate(array('name' => 'JEEP'));
        $env1->mainManufacturers()->firstOrcreate(array('name' => 'RAM'));
        $env1->mainManufacturers()->firstOrcreate(array('name' => 'FIAT'));
        $env1->mainManufacturers()->firstOrcreate(array('name' => 'ALPHA ROMEO'));
        
        $env2 = Environment::firstOrcreate(array('name' => 'Prod2'));
        
        $env2->mainManufacturers()->firstOrcreate(array('name' => 'MITSUBISHI'));
        $env2->mainManufacturers()->firstOrcreate(array('name' => 'AUDI'));
        $env2->mainManufacturers()->firstOrcreate(array('name' => 'VOLKSWAGEN'));
        $env2->mainManufacturers()->firstOrcreate(array('name' => 'NISSAN'));
        $env2->mainManufacturers()->firstOrcreate(array('name' => 'INFINITI'));
        $env2->mainManufacturers()->firstOrcreate(array('name' => 'LEXUS'));
        $env2->mainManufacturers()->firstOrcreate(array('name' => 'TOYOTA'));
        $env2->mainManufacturers()->firstOrcreate(array('name' => 'SCION'));
        $env2->mainManufacturers()->firstOrcreate(array('name' => 'BUICK'));
        $env2->mainManufacturers()->firstOrcreate(array('name' => 'CADILLAC'));
        $env2->mainManufacturers()->firstOrcreate(array('name' => 'CHEVROLET'));
        $env2->mainManufacturers()->firstOrcreate(array('name' => 'GMC'));
        $env2->mainManufacturers()->firstOrcreate(array('name' => 'PONTIAC'));
        $env2->mainManufacturers()->firstOrcreate(array('name' => 'SATURN'));
        $env2->mainManufacturers()->firstOrcreate(array('name' => 'FORD'));
        $env2->mainManufacturers()->firstOrcreate(array('name' => 'LINCOLN'));
        $env2->mainManufacturers()->firstOrcreate(array('name' => 'MERCURY'));
        $env2->mainManufacturers()->firstOrcreate(array('name' => 'HYUNDAI'));
        $env2->mainManufacturers()->firstOrcreate(array('name' => 'GENESIS'));
        $env2->mainManufacturers()->firstOrcreate(array('name' => 'SUBARU'));
        $env2->mainManufacturers()->firstOrcreate(array('name' => 'KIA'));
        $env2->mainManufacturers()->firstOrcreate(array('name' => 'JAGUAR'));
        $env2->mainManufacturers()->firstOrcreate(array('name' => 'LAND ROVER'));
        $env2->mainManufacturers()->firstOrcreate(array('name' => 'MAZDA'));
        $env2->mainManufacturers()->firstOrcreate(array('name' => 'HONDA'));
        $env2->mainManufacturers()->firstOrcreate(array('name' => 'ACURA'));
        $env2->mainManufacturers()->firstOrcreate(array('name' => 'BMW'));
        $env2->mainManufacturers()->firstOrcreate(array('name' => 'MINI'));
        
        $env3 = Environment::firstOrcreate(array('name' => 'Pilot'));
        $env3->mainManufacturers()->firstOrcreate(array('name' => 'ALFA ROMEO'));
        $env3->mainManufacturers()->firstOrcreate(array('name' => 'PLYMOUTH'));
        $env3->mainManufacturers()->firstOrcreate(array('name' => 'CHRYSLER'));
        $env3->mainManufacturers()->firstOrcreate(array('name' => 'DODGE'));
        $env3->mainManufacturers()->firstOrcreate(array('name' => 'JEEP'));
        $env3->mainManufacturers()->firstOrcreate(array('name' => 'RAM'));
        $env3->mainManufacturers()->firstOrcreate(array('name' => 'FIAT'));
        $env3->mainManufacturers()->firstOrcreate(array('name' => 'ALPHA ROMEO'));
                
    }

}
