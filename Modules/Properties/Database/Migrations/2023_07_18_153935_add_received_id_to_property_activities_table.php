<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddReceivedIdToPropertyActivitiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('property_activities', function (Blueprint $table) {
            $table->biginteger('send_user_id')->unsigned()->nullable();
            $table->foreign('send_user_id')->references('id')->on('users');
            $table->biginteger('received_user_id')->unsigned()->nullable();
            $table->foreign('received_user_id')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('property_activities', function (Blueprint $table) {
        });
    }
}
