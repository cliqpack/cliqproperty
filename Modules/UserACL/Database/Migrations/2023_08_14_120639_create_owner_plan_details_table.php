<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOwnerPlanDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('owner_plan_details', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('owner_plan_id')->unsigned();
            $table->foreign('owner_plan_id')->references('id')->on('owner_plans');
            $table->bigInteger('bill_id')->unsigned()->nullable();
            $table->foreign('bill_id')->references('id')->on('bills');
            $table->bigInteger('company_id')->unsigned();
            $table->foreign('company_id')->references('id')->on('companies');
            $table->date('trigger_date')->nullable();
            $table->string('trigger_time')->nullable();
            $table->string('weekly')->nullable();
            $table->integer('monthly')->nullable();
            $table->string('yearly')->nullable();
            $table->date('fortnightly')->nullable();
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
        Schema::dropIfExists('owner_plan_details');
    }
}
