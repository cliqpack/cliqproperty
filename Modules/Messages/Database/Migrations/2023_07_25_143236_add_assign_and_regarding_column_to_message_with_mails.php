<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddAssignAndRegardingColumnToMessageWithMails extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('message_with_mails', function (Blueprint $table) {
            $table->string('details_status')->nullable();
            $table->biginteger('assign_id')->unsigned()->nullable();
            $table->foreign('assign_id')->references('id')->on('users')->onDelete('cascade');
            // $table->biginteger('property_id')->unsigned()->nullable();
            // $table->foreign('property_id')->references('id')->on('properties')->onDelete('cascade');
            $table->biginteger('contact_id')->unsigned()->nullable();
            $table->foreign('contact_id')->references('id')->on('contacts')->onDelete('cascade');
            $table->biginteger('job_id')->unsigned()->nullable();
            $table->foreign('job_id')->references('id')->on('maintenances')->onDelete('cascade');
            $table->biginteger('inspection_id')->unsigned()->nullable();
            $table->foreign('inspection_id')->references('id')->on('inspections')->onDelete('cascade');
            $table->biginteger('task_id')->unsigned()->nullable();
            $table->foreign('task_id')->references('id')->on('tasks')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('', function (Blueprint $table) {

        });
    }
}
