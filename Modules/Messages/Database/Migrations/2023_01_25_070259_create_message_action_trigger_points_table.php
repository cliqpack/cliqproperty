<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMessageActionTriggerPointsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('message_action_trigger_points', function (Blueprint $table) {
            $table->id();
            $table->biginteger('action_id')->unsigned()->nullable();
            $table->foreign('action_id')->references('id')->on('message_action_names')->onDelete('cascade');
            $table->string('trigger_point')->nullable();
            $table->biginteger('company_id')->unsigned()->nullable();
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');


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
        Schema::dropIfExists('message_action_trigger_points');
    }
}
