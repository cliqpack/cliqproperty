<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddSupplierIdToPropertyDocsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('property_docs', function (Blueprint $table) {
            $table->biginteger('supplier_contact_id')->unsigned()->nullable();
            $table->foreign('supplier_contact_id')->references('id')->on('supplier_contacts')->onDelete('cascade');
            $table->biginteger('buyer_contact_id')->unsigned()->nullable();
            $table->foreign('buyer_contact_id')->references('id')->on('buyer_contacts')->onDelete('cascade');
            $table->biginteger('seller_contact_id')->unsigned()->nullable();
            $table->foreign('seller_contact_id')->references('id')->on('seller_contacts')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('property_docs', function (Blueprint $table) {
        });
    }
}
