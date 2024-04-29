<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePropertyCheckoutKeysTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('property_checkout_keys', function (Blueprint $table) {
            $table->id();
            $table->biginteger('contact_id')->unsigned();
            $table->foreign('contact_id')->references('id')->on('contacts')->onDelete('cascade');
            $table->biginteger('property_id')->unsigned();
            $table->foreign('property_id')->references('id')->on('properties');
            $table->date('return_due');
            $table->time('return_time');
            $table->string('note');
            $table->string('check_type');


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
        Schema::dropIfExists('property_checkout_keys');
    }
}
