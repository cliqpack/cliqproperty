<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateInvoicesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->date('invoice_billing_date')->nullable();
            $table->biginteger('supplier_contact_id')->unsigned()->nullable();
            $table->foreign('supplier_contact_id')->references('id')->on('supplier_contacts');
            $table->bigInteger('chart_of_account_id')->unsigned()->nullable();
            $table->foreign('chart_of_account_id')->references('id')->on('accounts');
            $table->string('details')->nullable();
            $table->bigInteger('property_id')->unsigned()->nullable();
            $table->foreign('property_id')->references('id')->on('properties');
            $table->integer('amount')->nullable();
            $table->string('file')->nullable();
            $table->boolean('include_tax')->default(false);
            $table->biginteger('tenant_contact_id')->unsigned()->nullable();
            $table->foreign('tenant_contact_id')->references('id')->on('tenant_contacts');
            $table->biginteger('company_id')->unsigned();
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->string('status')->default('Unpaid');
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
        Schema::dropIfExists('invoices');
    }
}
