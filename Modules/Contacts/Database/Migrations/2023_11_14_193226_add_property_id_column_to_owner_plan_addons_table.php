<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddPropertyIdColumnToOwnerPlanAddonsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('owner_plan_addons', function (Blueprint $table) {
            $table->biginteger('property_id')->unsigned()->nullable();
            $table->foreign('property_id')->references('id')->on('properties');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('owner_plan_addons', function (Blueprint $table) {
            $table->dropColumn('property_id');
        });
    }
}
