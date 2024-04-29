<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddContactDetailsIdToContactPostalAddress extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('contact_postal_addresses', function (Blueprint $table) {
            $table->biginteger('contact_details_id')->unsigned()->nullable();
            $table->foreign('contact_details_id')->references('id')->on('contact_details')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('contact_postal_addresses', function (Blueprint $table) {

        });
    }
}
