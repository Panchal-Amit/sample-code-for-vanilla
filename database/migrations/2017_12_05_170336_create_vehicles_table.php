<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateVehiclesTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('vehicles', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('dfx_id');            
            $table->string('vin',50);
            $table->string('model');
            $table->year('year');
            $table->string('transmissions');
            $table->string('cylinder');
            $table->string('drive');
            $table->string('color')->nullable();
            $table->string('image');            	
            $table->integer('odometer');
            $table->timestamps();            
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('vehicles');
    }

}
