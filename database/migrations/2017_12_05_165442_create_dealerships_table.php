<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDealershipsTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('dealerships', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('dfx_id');
            $table->string('name');
            $table->string('email');
            $table->string('oem_id');
            $table->string('oem_name');
            $table->string('oem_logo');
            $table->string('oem_video_url');
            $table->string('phone_number');
            $table->string('sms_number');
            $table->string('roadside_assist');
            $table->string('address_1');
            $table->string('address_2');
            $table->string('city');
            $table->string('postal_code');
            $table->string('country');
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);
            $table->boolean('status');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('dealerships');
    }

}
