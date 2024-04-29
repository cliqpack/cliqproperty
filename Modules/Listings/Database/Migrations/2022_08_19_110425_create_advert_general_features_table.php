<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use phpDocumentor\Reflection\Types\Nullable;

class CreateAdvertGeneralFeaturesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('advert_general_features', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('listing_id')->unsigned();
            $table->foreign('listing_id')->references('id')->on('listings')->onDelete('cascade');
            $table->string('new_or_established')->nullable();
            $table->string('ensuites')->nullable();
            $table->string('toilets')->nullable();
            $table->string('furnished')->nullable();
            $table->string('pets_allowed')->nullable();
            $table->string('smokers_permitted')->nullable();
            $table->string('balcony_or_deck')->nullable();
            $table->string('deck')->nullable();
            $table->string('fully_fenced')->nullable();
            $table->string('garden_or_courtyard')->nullable();
            $table->string('internal_laundry')->nullable();
            $table->string('outdoor_entertaining_area')->nullable();
            $table->string('outside_spa')->nullable();
            $table->string('secure_parking')->nullable();
            $table->string('shed')->nullable();
            $table->string('swimming_pool')->nullable();
            $table->string('tennis_court')->nullable();
            $table->string('alarm_system')->nullable();
            $table->string('broadband')->nullable();
            $table->string('Built_in_wardrobes')->nullable();
            $table->string('dishwasher')->nullable();
            $table->string('floorboards')->nullable();
            $table->string('gas_heating')->nullable();
            $table->string('gym')->nullable();
            $table->string('hot_water_service')->nullable();
            $table->string('inside_spa')->nullable();
            $table->string('intercom')->nullable();
            $table->string('pay_tv_access')->nullable();
            $table->string('rumpus_room')->nullable();
            $table->string('study')->nullable();
            $table->string('air_conditioning')->nullable();
            $table->string('solar_hot_water')->nullable();
            $table->string('solar_panels')->nullable();
            $table->string('water_tank')->nullable();

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
        Schema::dropIfExists('advert_general_features');
    }
}
