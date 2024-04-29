<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMenuPlanDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('menu_plan_details', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('menu_plan_id')->unsigned();
            $table->foreign('menu_plan_id')->references('id')->on('menu_plans')->onDelete('cascade');
            $table->bigInteger('menu_id')->unsigned()->nullable();
            $table->foreign('menu_id')->references('id')->on('menus')->onDelete('cascade');
            $table->bigInteger('addon_id')->unsigned();
            $table->foreign('addon_id')->references('id')->on('addons')->onDelete('cascade');

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
        Schema::dropIfExists('menu_plan_details');
    }
}
