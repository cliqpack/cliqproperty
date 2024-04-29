<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePropertyActivityEmailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('property_activity_emails', function (Blueprint $table) {
            $table->id();
            $table->string('email_to');
            $table->string('email_from');
            $table->string('subject');
            $table->text('email_body',50000);
            $table->string('email_status');
            $table->bigInteger('property_activity_id')->unsigned()->nullable();
            $table->foreign('property_activity_id')->references('id')->on('property_activities');

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
        Schema::dropIfExists('property_activity_emails');
    }
}
