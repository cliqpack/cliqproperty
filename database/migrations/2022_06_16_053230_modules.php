<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Modules extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        Schema::create('modules', function (Blueprint $table) {
            $table->increments('id');
            $table->biginteger('menu_id')->unsigned();
            $table->foreign('menu_id')->references('id')->on('menus');
            $table->string('name');
            $table->string('created_by');
            $table->boolean('soft_delete');
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
        //
    }
}
