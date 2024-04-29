<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class PropertyAddresses extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        Schema::create('property_addresses', function (Blueprint $table) {
            $table->id('id');
            $table->biginteger('property_id')->unsigned();
            $table->foreign('property_id')->references('id')->on('properties');
            $table->string('building_name')->nullable();
            $table->string('unit')->nullable();
            $table->string('number')->nullable();
            $table->string('street')->nullable();
            $table->string('suburb')->nullable();
            $table->string('postcode')->nullable();
            $table->string('state')->nullable();
            $table->string('country')->nullable();
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
        Schema::dropIfExists('property_addresses');
    }
}
