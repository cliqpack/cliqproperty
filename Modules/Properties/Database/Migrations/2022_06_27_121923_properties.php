<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Properties extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        //
        Schema::create('properties', function (Blueprint $table) {
            $table->id('id');
            $table->string('reference');
            $table->biginteger('manager_id')->unsigned();
            $table->foreign('manager_id')->references('id')->on('users');
            $table->string('location')->nullable();

            $table->biginteger('property_type')->unsigned()->nullable();
            $table->foreign('property_type')->references('id')->on('property_types');

            $table->string('primary_type')->nullable();
            $table->text('description')->nullable();
            $table->string('bathroom')->nullable();
            $table->string('bedroom')->nullable();
            $table->string('car_space')->nullable();
            $table->string('floor_area')->nullable();
            $table->string('floor_size')->nullable();
            $table->string('land_area')->nullable();
            $table->string('land_size')->nullable();
            $table->integer('key_number')->nullable();
            $table->integer('strata_manager_id')->nullable();
            $table->string('routine_inspections_frequency')->nullable();
            $table->string('routine_inspections_frequency_type')->nullable();
            $table->integer('first_routine')->nullable();
            $table->string('first_routine_frequency_type')->nullable();
            $table->date('routine_inspection_due_date')->nullable();
            $table->string('note')->nullable();
            $table->string('property_image')->nullable();
            $table->string('owner')->nullable();
            $table->string('tenant')->nullable();
            $table->biginteger('company_id')->unsigned();
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');

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
        Schema::dropIfExists('properties');
    }
}
