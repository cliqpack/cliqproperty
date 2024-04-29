<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOptionalPropertiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('optional_properties', function (Blueprint $table) {
            $table->id();
            $table->biginteger('property_id')->unsigned();
            $table->foreign('property_id')->references('id')->on('properties');
            $table->string('garages')->nullable();
            $table->string('carports')->nullable();
            $table->string('open_car_space')->nullable();

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
        Schema::dropIfExists('optional_properties');
    }
}
