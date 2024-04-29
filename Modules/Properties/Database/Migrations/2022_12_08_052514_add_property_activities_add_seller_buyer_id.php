<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddPropertyActivitiesAddSellerBuyerId extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('property_activities', function (Blueprint $table) {
            $table->biginteger('seller_contact_id')->unsigned()->nullable();
            $table->foreign('seller_contact_id')->references('id')->on('seller_contacts');
            $table->biginteger('buyer_contact_id')->unsigned()->nullable();
            $table->foreign('buyer_contact_id')->references('id')->on('buyer_contacts');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('', function (Blueprint $table) {

        });
    }
}
