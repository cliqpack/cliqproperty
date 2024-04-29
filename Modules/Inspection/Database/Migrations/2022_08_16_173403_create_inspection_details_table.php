<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateInspectionDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('inspection_details', function (Blueprint $table) {
            $table->id();
            $table->biginteger('inspection_id')->unsigned();
            $table->foreign('inspection_id')->references('id')->on('inspections');
            $table->biginteger('property_id')->unsigned();
            $table->foreign('property_id')->references('id')->on('properties');
            $table->biginteger('room_id')->unsigned();
            $table->foreign('room_id')->references('id')->on('property_rooms');
            $table->string('room_attributes')->nullable();
            $table->boolean('clean')->nullable();
            $table->boolean('undamaged')->nullable();
            $table->boolean('working')->nullable();
            $table->string('comment')->nullable();
            $table->string('routine_description')->nullable();


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
        Schema::dropIfExists('inspection_details');
    }
}
