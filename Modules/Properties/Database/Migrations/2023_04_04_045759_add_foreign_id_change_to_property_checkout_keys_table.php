<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddForeignIdChangeToPropertyCheckoutKeysTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('property_checkout_keys', function (Blueprint $table) {
            $table->unsignedBigInteger('contact_id')->nullable()->change();
            $table->unsignedBigInteger('team_member_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('property_checkout_keys', function (Blueprint $table) {
            $table->unsignedBigInteger('contact_id')->nullable(false)->change();
            $table->unsignedBigInteger('team_member_id')->nullable(false)->change();
        });
    }
}
