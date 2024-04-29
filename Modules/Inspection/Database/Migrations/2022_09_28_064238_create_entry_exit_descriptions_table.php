<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateEntryExitDescriptionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('entry_exit_descriptions', function (Blueprint $table) {
            $table->id();
            $table->biginteger('property_id')->unsigned();
            $table->foreign('property_id')->references('id')->on('properties');
            $table->biginteger('inspection_id')->unsigned();
            $table->foreign('inspection_id')->references('id')->on('inspections');
            $table->biginteger('room_id')->unsigned();
            $table->foreign('room_id')->references('id')->on('property_rooms');
            $table->text('description')->nullable();
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
        Schema::dropIfExists('entry_exit_descriptions');
    }
}
