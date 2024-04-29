<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBillsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bills', function (Blueprint $table) {
            $table->id();
            $table->date('billing_date')->nullable();
            $table->biginteger('supplier_contact_id')->unsigned()->nullable();
            $table->foreign('supplier_contact_id')->references('id')->on('supplier_contacts');
            $table->bigInteger('bill_account_id')->unsigned()->nullable();
            $table->foreign('bill_account_id')->references('id')->on('accounts');
            $table->string('invoice_ref')->nullable();
            $table->bigInteger('property_id')->unsigned()->nullable();
            $table->foreign('property_id')->references('id')->on('properties');
            $table->integer('amount')->nullable();
            $table->string('file')->nullable();
            $table->boolean('include_tax')->default(false);
            $table->bigInteger('maintenance_id')->unsigned()->nullable();
            $table->foreign('maintenance_id')->references('id')->on('maintenances');
            $table->biginteger('company_id')->unsigned();

            $table->string('uploaded')->nullable();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->string('status')->default('Unpaid');
            $table->bigInteger('owner_folio_id')->unsigned()->nullable();
            $table->foreign('owner_folio_id')->references('id')->on('owner_folios');
            $table->bigInteger('supplier_folio_id')->unsigned()->nullable();
            $table->foreign('supplier_folio_id')->references('id')->on('supplier_details');
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
        Schema::dropIfExists('bills');
    }
}
