<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class MainManufacturer extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        if (!Schema::hasTable('main_manufacturer')) {
            Schema::create('main_manufacturer', function (Blueprint $table) {
                $table->increments('id');
                $table->string('name');
                $table->integer('env_id')->unsigned();
                $table->foreign('env_id')->references('id')->on('environment');
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('main_manufacturer');
    }

}
