<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBrandProperty extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('brand_property', function (Blueprint $table) {
            $table->increments('id');
            $table->string('oem_id');
            $table->integer('ecomm_brand_id');
            $table->string('oem_name');
            $table->string('color');
            $table->string('text_color');
            $table->string('button_previous_backgroud_color');
            $table->string('button_previous_text_color');
            $table->string('font_name_regular');
            $table->string('font_name_light');
            $table->string('font_name_bold');
            $table->string('motto');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('brand_property');
    }

}
