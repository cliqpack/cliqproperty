<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateWorkingHoursTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('working_hours', function (Blueprint $table) {
            $table->id();
            $table->string('day')->nullable();
            $table->boolean('work')->nullable();
            $table->string('start_time')->nullable();
            $table->string('end_time')->nullable();
            $table->biginteger('company_settings_id')->unsigned()->nullable();
            $table->foreign('company_settings_id')->references('id')->on('company_settings')->onDelete('cascade');
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
        Schema::dropIfExists('working_hours');
    }
}
