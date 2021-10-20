<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddFieldsBrandPropertyTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::table('brand_property', function (Blueprint $table) {
            $table->string('font_name_semibold')->after('font_name_bold');
            $table->string('font_name_medium')->after('font_name_semibold');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::table('brand_property', function (Blueprint $table) {
            $table->dropColumn('font_name_semibold');
            $table->dropColumn('font_name_medium');
        });
    }

}
