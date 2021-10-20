<?php

use Illuminate\Database\Seeder;
use App\Models\Terms;

class TermsSeeder extends Seeder {

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run() {
        Terms::firstOrcreate(array('term' => 'Login'));
        Terms::firstOrcreate(array('term' => 'User name'));
        Terms::firstOrcreate(array('term' => 'Password'));
        Terms::firstOrcreate(array('term' => 'Enter'));
        
        
        
        Terms::firstOrcreate(array('term' => 'Forgot your password?'));
        Terms::firstOrcreate(array('term' => 'Forgot your password?'));
        Terms::firstOrcreate(array('term' => 'Username is required'));
        Terms::firstOrcreate(array('term' => 'Password is required'));
        Terms::firstOrcreate(array('term' => 'Password Reset'));
        Terms::firstOrcreate(array('term' => 'Please enter your username and your password will be emailed to you.'));
        Terms::firstOrcreate(array('term' => 'Enter your username'));
        Terms::firstOrcreate(array('term' => 'Logout'));
        Terms::firstOrcreate(array('term' => 'Vehicle'));
        Terms::firstOrcreate(array('term' => 'Customer'));
        Terms::firstOrcreate(array('term' => 'Video'));
        Terms::firstOrcreate(array('term' => 'Schedule'));
        Terms::firstOrcreate(array('term' => 'Pre-Delivery'));
        Terms::firstOrcreate(array('term' => 'PREVIOUS'));
        Terms::firstOrcreate(array('term' => 'NEXT'));
        Terms::firstOrcreate(array('term' => 'PREVIEW'));
    }

}
