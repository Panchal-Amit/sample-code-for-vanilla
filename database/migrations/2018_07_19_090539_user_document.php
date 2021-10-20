<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UserDocument extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('user_document', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id')->unsigned()->unsigned();
            $table->foreign('user_id')->references('id')->on('users');
            $table->string('title');
            $table->enum('type', ['text', 'pdf', 'video']);
            $table->string('description');
            $table->string('url');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('user_document');
    }

}
