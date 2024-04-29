<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateReceiptsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('receipts', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('property_id')->unsigned()->nullable();
            $table->foreign('property_id')->references('id')->on('properties');
            $table->biginteger('contact_id')->unsigned()->nullable();
            $table->foreign('contact_id')->references('id')->on('contacts');
            $table->string('amount')->nullable();
            $table->string('amount_type')->nullable();
            $table->date('receipt_date')->nullable();
            $table->string('ref')->nullable();
            $table->string('type')->nullable();
            $table->string('summary')->nullable();
            $table->string('payment_method')->nullable();
            $table->string('from')->nullable();
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->string('paid_by')->nullable();
            $table->date('cleared_date')->nullable();
            $table->string('cheque_drawer')->nullable();
            $table->string('cheque_bank')->nullable();
            $table->string('cheque_branch')->nullable();
            $table->string('cheque_amount')->nullable();
            $table->biginteger('folio_id')->nullable();
            $table->string('folio_type')->nullable();
            $table->integer('rent_amount')->nullable();
            $table->integer('deposit_amount')->nullable();
            $table->biginteger('from_folio_id')->nullable();
            $table->string('from_folio_type')->nullable();
            $table->biginteger('to_folio_id')->nullable();
            $table->string('to_folio_type')->nullable();
            $table->biginteger('company_id')->unsigned()->nullable();
            $table->string('status')->nullable();
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
        Schema::dropIfExists('receipts');
    }
}
