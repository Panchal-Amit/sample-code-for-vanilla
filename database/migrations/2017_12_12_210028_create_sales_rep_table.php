<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSalesRepTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sales_rep', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('dfx_id');
            $table->integer('dealership_id')->unsigned();
            $table->foreign('dealership_id')->references('id')->on('dealerships');
            $table->string('username');
            $table->string('first_name');
            $table->string('last_name');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('sales_rep');
    }
}
