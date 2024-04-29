<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddPropertyToOwnerFeesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('owner_fees', function (Blueprint $table) {
            $table->biginteger('owner_folio_id')->unsigned()->nullable();
            $table->foreign('owner_folio_id')->references('id')->on('owner_folios');
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
        Schema::table('owner_fees', function (Blueprint $table) {

        });
    }
}
