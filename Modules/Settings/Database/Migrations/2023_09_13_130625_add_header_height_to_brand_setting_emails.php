<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddHeaderHeightToBrandSettingEmails extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('brand_setting_emails', function (Blueprint $table) {
            $table->integer('header_img_height')->nullable();
            $table->integer('footer_img_height')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('brand_setting_emails', function (Blueprint $table) {
        });
    }
}
