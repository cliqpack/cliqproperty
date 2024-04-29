<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddAdjustmentIdToRentManagementTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('rent_management', function (Blueprint $table) {
            $table->biginteger('rent_adjustment_id')->unsigned()->nullable();
            $table->foreign('rent_adjustment_id')->references('id')->on('rent_details');
            $table->biginteger('rent_discount_id')->unsigned()->nullable();
            $table->foreign('rent_discount_id')->references('id')->on('rent_discounts');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('rent_management', function (Blueprint $table) {

        });
    }
}
