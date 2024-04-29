<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOwnerFolioTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('owner_folio_transactions', function (Blueprint $table) {
            $table->id();
            $table->biginteger('folio_id')->unsigned()->nullable();
            $table->foreign('folio_id')->references('id')->on('owner_folios');
            $table->biginteger('owner_contact_id')->unsigned()->nullable();
            $table->foreign('owner_contact_id')->references('id')->on('owner_contacts');
            $table->biginteger('property_id')->unsigned()->nullable();
            $table->foreign('property_id')->references('id')->on('properties');
            $table->string('transaction_type')->nullable();
            $table->date('transaction_date')->nullable();
            $table->string('details')->nullable();
            $table->double('amount')->default(0.00);
            $table->string('amount_type')->nullable();
            $table->biginteger('transaction_folio_id')->nullable();
            $table->string('transaction_folio_type')->nullable();
            $table->string('reversed_reason')->nullable();
            $table->boolean('reversed')->default(false);
            $table->biginteger('receipt_details_id')->unsigned()->nullable();
            $table->foreign('receipt_details_id')->references('id')->on('receipt_details');
            $table->biginteger('company_id')->unsigned()->nullable();
            $table->foreign('company_id')->references('id')->on('companies');
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
        Schema::dropIfExists('owner_folio_transactions');
    }
}
