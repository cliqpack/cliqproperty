<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFolioLedgerDetailsDailiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('folio_ledger_details_dailies', function (Blueprint $table) {
            $table->id();
            $table->biginteger('company_id')->unsigned();
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->string('ledger_type')->nullable();
            $table->string('ledger_id')->nullable();
            $table->string('details')->nullable();
            $table->string('folio_id')->nullable();
            $table->string('folio_type')->nullable();
            $table->bigInteger('amount')->nullable();
            $table->string('type')->nullable();
            $table->date('date')->nullable();
            $table->biginteger('receipt_id')->unsigned()->nullable();
            $table->foreign('receipt_id')->references('id')->on('receipts');
            $table->biginteger('receipt_details_id')->unsigned()->nullable();
            $table->foreign('receipt_details_id')->references('id')->on('receipt_details')->onDelete('cascade');
            $table->biginteger('bill_id')->unsigned()->nullable();
            $table->foreign('bill_id')->references('id')->on('bills')->onDelete('cascade');
            $table->biginteger('disbursement_id')->unsigned()->nullable();
            $table->foreign('disbursement_id')->references('id')->on('disbursements')->onDelete('cascade');
            $table->biginteger('invoice_id')->unsigned()->nullable();
            $table->foreign('invoice_id')->references('id')->on('invoices')->onDelete('cascade');

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
        Schema::dropIfExists('folio_ledger_details_dailies');
    }
}
