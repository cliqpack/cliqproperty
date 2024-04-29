<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRecurringInvoicesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('recurring_invoices', function (Blueprint $table) {
            $table->id();
            $table->string('details')->nullable();
            $table->integer('amount')->nullable();
            $table->boolean('include_tax')->default(false);
            $table->bigInteger('chart_of_account_id')->unsigned()->nullable();
            $table->foreign('chart_of_account_id')->references('id')->on('accounts');
            $table->bigInteger('property_id')->unsigned()->nullable();
            $table->foreign('property_id')->references('id')->on('properties');
            $table->biginteger('supplier_contact_id')->unsigned()->nullable();
            $table->foreign('supplier_contact_id')->references('id')->on('supplier_contacts');
            $table->biginteger('supplier_folio_id')->unsigned()->nullable();
            $table->foreign('supplier_folio_id')->references('id')->on('supplier_details');
            $table->biginteger('owner_folio_id')->unsigned()->nullable();
            $table->foreign('owner_folio_id')->references('id')->on('owner_folios');
            $table->biginteger('tenant_contact_id')->unsigned()->nullable();
            $table->foreign('tenant_contact_id')->references('id')->on('tenant_contacts');
            $table->biginteger('tenant_folio_id')->unsigned()->nullable();
            $table->foreign('tenant_folio_id')->references('id')->on('tenant_folios');
            $table->biginteger('company_id')->unsigned();
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
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
        Schema::dropIfExists('recurring_invoices');
    }
}
