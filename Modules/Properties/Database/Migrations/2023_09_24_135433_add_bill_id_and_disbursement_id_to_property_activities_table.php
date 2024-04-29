<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddBillIdAndDisbursementIdToPropertyActivitiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('property_activities', function (Blueprint $table) {
            $table->biginteger('bill_id')->unsigned()->nullable();
            $table->foreign('bill_id')->references('id')->on('bills');
            $table->biginteger('disbursement_id')->unsigned()->nullable();
            $table->foreign('disbursement_id')->references('id')->on('disbursements');
            $table->biginteger('receipt_id')->unsigned()->nullable();
            $table->foreign('receipt_id')->references('id')->on('receipts');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('property_activities', function (Blueprint $table) {
        });
    }
}
