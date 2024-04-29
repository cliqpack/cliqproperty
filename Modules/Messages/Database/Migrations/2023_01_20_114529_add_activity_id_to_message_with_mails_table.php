<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddActivityIdToMessageWithMailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('message_with_mails', function (Blueprint $table) {
            $table->bigInteger('property_activity_id')->unsigned()->nullable();
            $table->foreign('property_activity_id')->references('id')->on('property_activities');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('message_with_mails', function (Blueprint $table) {
        });
    }
}
