<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateWithdrawalsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('withdrawals', function (Blueprint $table) {
            $table->id();
            $table->biginteger('property_id')->unsigned()->nullable();
            $table->foreign('property_id')->references('id')->on('properties');
            $table->biginteger('receipt_id')->unsigned()->nullable();
            $table->foreign('receipt_id')->references('id')->on('receipts');
            $table->biginteger('disbursement_id')->unsigned()->nullable();
            $table->foreign('disbursement_id')->references('id')->on('disbursements');
            $table->date('create_date')->nullable();
            $table->biginteger('contact_payment_id')->unsigned()->nullable();
            $table->string('contact_type')->nullable();
            $table->double('amount', 10, 2)->nullable();
            $table->string('customer_reference')->nullable();
            $table->string('statement')->nullable();
            $table->string('payment_type')->nullable();
            $table->date('complete_date')->nullable();
            $table->biginteger('cheque_number')->unsigned()->nullable();
            $table->integer('total_withdrawals')->nullable();
            $table->boolean('status')->default(false);
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
        Schema::dropIfExists('withdrawals');
    }
}
