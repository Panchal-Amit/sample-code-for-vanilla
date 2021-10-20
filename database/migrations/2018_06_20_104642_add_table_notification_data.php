<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddTableNotificationData extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('notification_data', function (Blueprint $table) {
            $table->increments('id');
            $table->enum('status', ['unread', 'read', 'dismiss'])->default('unread');
            $table->integer('notification_id')->unsigned();
            $table->foreign('notification_id')->references('id')->on('notifications');
            $table->integer('vehicle_id')->unsigned();
            $table->foreign('vehicle_id')->references('id')->on('vehicles');
            $table->integer('user_id')->unsigned()->nullable();
            $table->foreign('user_id')->references('id')->on('users');
            $table->text('data');
            $table->string('image', 1024);
            $table->string('token', 1024);
            $table->integer('priority')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('notification_data');
    }

}
