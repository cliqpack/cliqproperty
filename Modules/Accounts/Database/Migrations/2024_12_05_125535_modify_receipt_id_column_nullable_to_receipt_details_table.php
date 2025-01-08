<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ModifyReceiptIdColumnNullableToReceiptDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('receipt_details', function (Blueprint $table) {
            $table->dropForeign(['receipt_id']);
            $table->biginteger('receipt_id')->unsigned()->nullable()->change();
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
        Schema::table('receipt_details', function (Blueprint $table) {
            $table->dropForeign(['receipt_id']);
            $table->biginteger('receipt_id')->unsigned()->nullable(false)->change();
            $table->foreign('receipt_id')->references('id')->on('receipts');
        });
    }
}
