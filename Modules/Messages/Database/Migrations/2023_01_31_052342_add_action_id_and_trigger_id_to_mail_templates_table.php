<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddActionIdAndTriggerIdToMailTemplatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('mail_templates', function (Blueprint $table) {
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
        Schema::table('mail_templates', function (Blueprint $table) {
        });
    }
}
