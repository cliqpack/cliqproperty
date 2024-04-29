<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddTeamMemberIdToPropertyCheckoutKeysTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('property_checkout_keys', function (Blueprint $table) {
            $table->string('status')->nullable();
            $table->biginteger('team_member_id')->unsigned()->nullable();
            $table->foreign('team_member_id')->references('id')->on('users');
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
        });
    }
}
