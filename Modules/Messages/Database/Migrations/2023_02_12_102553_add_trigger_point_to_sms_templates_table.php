<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddTriggerPointToSmsTemplatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('sms_templates', function (Blueprint $table) {
            $table->string('to')->nullable();
            $table->string('message_action_name')->nullable();
            $table->string('message_trigger_to')->nullable();
            $table->string('messsage_trigger_point')->nullable();

            $table->biginteger('action_name_id')->unsigned()->nullable();
            $table->foreign('action_name_id')->references('id')->on('message_action_names')->onDelete('cascade');
            $table->biginteger('trigger_to_id')->unsigned()->nullable();
            $table->foreign('trigger_to_id')->references('id')->on('message_action_trigger_tos')->onDelete('cascade');
            $table->biginteger('trigger_point_id')->unsigned()->nullable();
            $table->foreign('trigger_point_id')->references('id')->on('message_action_trigger_points')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('sms_templates', function (Blueprint $table) {
        });
    }
}
