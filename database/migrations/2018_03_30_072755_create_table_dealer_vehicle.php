<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableDealerVehicle extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('dealer_vehicle', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('dealer_id')->unsigned();
            $table->integer('vehicle_id')->unsigned();
            $table->integer('manufacturer_id')->unsigned();
            $table->foreign('dealer_id')->references('id')->on('dealerships');
            $table->foreign('vehicle_id')->references('id')->on('vehicles');
            $table->foreign('manufacturer_id')->references('id')->on('manufacturers');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('dealer_vehicle');
    }

}
