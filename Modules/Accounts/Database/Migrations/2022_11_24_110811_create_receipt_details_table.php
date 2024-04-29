<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateReceiptDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('receipt_details', function (Blueprint $table) {
            $table->id();
            $table->biginteger('receipt_id')->unsigned();
            $table->foreign('receipt_id')->references('id')->on('receipts');
            $table->string('allocation')->nullable();
            $table->string('description')->nullable();
            $table->string('payment_type')->nullable();
            $table->string('amount')->nullable();
            $table->biginteger('folio_id')->nullable();
            $table->string('folio_type')->nullable();
            $table->string('type')->nullable();
            $table->boolean('tax')->nullable();
            $table->biginteger('account_id')->unsigned()->nullable();
            $table->foreign('account_id')->references('id')->on('accounts');
            $table->biginteger('from_folio_id')->nullable();
            $table->string('from_folio_type')->nullable();
            $table->biginteger('to_folio_id')->nullable();
            $table->string('to_folio_type')->nullable();
            $table->boolean('disbursed')->default(0);
            $table->biginteger('company_id')->unsigned()->nullable();
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
        Schema::dropIfExists('receipt_details');
    }
}
