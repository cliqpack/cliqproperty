<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddOwnerFolioColumnToPropertiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->biginteger('owner_folio_id')->unsigned()->nullable();
            $table->foreign('owner_folio_id')->references('id')->on('owner_folios')->onDelete('cascade');
            $table->biginteger('owner_contact_id')->unsigned()->nullable();
            $table->foreign('owner_contact_id')->references('id')->on('owner_contacts')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('properties', function (Blueprint $table) {
        });
    }
}
