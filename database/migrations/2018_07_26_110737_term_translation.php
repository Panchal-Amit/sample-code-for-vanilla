<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class TermTranslation extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {

        Schema::create('term_translation', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('locales_id')->unsigned();
            $table->foreign('locales_id')->references('id')->on('locales');
            $table->integer('term_id')->unsigned();
            $table->foreign('term_id')->references('id')->on('terms');            	
            $table->string('translation');
            $table->integer('created_by');
            $table->integer('updated_by');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('term_translation');
    }

}
