<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateInspectionRoutineOverviewsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('inspection_routine_overviews', function (Blueprint $table) {
            $table->id();
            $table->biginteger('inspection_id')->unsigned();
            $table->foreign('inspection_id')->references('id')->on('inspections');
            $table->bigInteger('property_id')->unsigned();
            $table->foreign('property_id')->references('id')->on('properties')->onDelete('cascade');
            $table->boolean('share_with_owner')->nullable();
            $table->boolean('share_with_tenant')->nullable();
            $table->string('rent_review')->nullable();
            $table->string('water_meter_reading')->nullable();
            $table->string('general_notes')->nullable();
            $table->string('follow_up_actions')->nullable();

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
        Schema::dropIfExists('inspection_routine_overviews');
    }
}
