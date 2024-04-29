<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDisbursementsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('disbursements', function (Blueprint $table) {
            $table->id();
            $table->biginteger('receipt_id')->unsigned()->nullable();
            $table->foreign('receipt_id')->references('id')->on('receipts');
            $table->string('reference');
            $table->biginteger('property_id')->unsigned()->nullable();
            $table->foreign('property_id')->references('id')->on('properties')->onDelete('cascade');
            $table->biginteger('folio_id')->nullable();
            $table->string('folio_type')->nullable();
            $table->date('last')->nullable();
            $table->date('due')->nullable();
            $table->string('pay_by')->nullable();
            $table->string('withhold')->nullable();
            $table->integer('bills_due')->nullable();
            $table->integer('fees_raised')->nullable();
            $table->integer('payout')->nullable();
            $table->integer('rent')->nullable();
            $table->integer('bills')->nullable();
            $table->string('preview')->nullable();
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();

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
        Schema::dropIfExists('disbursements');
    }
}
