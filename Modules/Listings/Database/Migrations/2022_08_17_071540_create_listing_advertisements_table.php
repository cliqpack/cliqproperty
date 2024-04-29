<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateListingAdvertisementsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('listing_advertisements', function (Blueprint $table) {
            $table->id();
            $table->biginteger('listing_agent_primary')->unsigned()->nullable();
            $table->foreign('listing_agent_primary')->references('id')->on('users');
            $table->biginteger('listing_agent_secondary')->unsigned()->nullable();
            $table->foreign('listing_agent_secondary')->references('id')->on('users')->nullable();
            $table->date('date_available')->nullable();
            $table->integer('rent')->nullable();
            $table->boolean('display_rent')->nullable();
            $table->integer('bond')->nullable();
            $table->biginteger('listing_id')->unsigned();
            $table->foreign('listing_id')->references('id')->on('listings')->onDelete('cascade');

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
        Schema::dropIfExists('listing_advertisements');
    }
}
