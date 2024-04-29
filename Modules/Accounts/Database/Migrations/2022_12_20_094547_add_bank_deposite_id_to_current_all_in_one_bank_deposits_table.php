<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddBankDepositeIdToCurrentAllInOneBankDepositsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('current_all_in_one_bank_deposits', function (Blueprint $table) {
            $table->biginteger('bank_deposite_list_id')->unsigned()->nullable();
            $table->foreign('bank_deposite_list_id')->references('id')->on('bank_deposit_lists')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('current_all_in_one_bank_deposits', function (Blueprint $table) {
        });
    }
}
