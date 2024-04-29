<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCurrentAllInOneBankDepositListsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('current_all_in_one_bank_deposit_lists', function (Blueprint $table) {
            $table->id();
            $table->biginteger('deposit_list_id')->unsigned();
            $table->foreign('deposit_list_id')->references('id')->on('current_all_in_one_bank_deposits')->onDelete('cascade');
            $table->biginteger('receipt_id')->unsigned();
            $table->foreign('receipt_id')->references('id')->on('receipts');
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
        Schema::dropIfExists('current_all_in_one_bank_deposit_lists');
    }
}
