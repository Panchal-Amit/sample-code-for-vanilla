<?php

use Illuminate\Database\Seeder;
use App\Models\Notifications;

class NotificationsTableSeeder extends Seeder {

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run() {

        Notifications::firstOrcreate(array('action_type' => 'reminder'));
        Notifications::firstOrcreate(array('action_type' => 'call'));
        Notifications::firstOrcreate(array('action_type' => 'reschedule-appointment'));
        Notifications::firstOrcreate(array('action_type' => 'rate-us'));
        Notifications::firstOrcreate(array('action_type' => 'feedback'));
        Notifications::firstOrcreate(array('action_type' => 'dmpi'));
        Notifications::firstOrcreate(array('action_type' => 'order'));
        Notifications::firstOrcreate(array('action_type' => 'recall'));
        Notifications::firstOrcreate(array('action_type' => 'update'));
        Notifications::firstOrcreate(array('action_type' => 'vehicle-health'));
        Notifications::firstOrcreate(array('action_type' => 'wallet'));
        Notifications::firstOrcreate(array('action_type' => 'video'));
    }

}
