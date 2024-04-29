<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddDepositIdColumnToCurrentAllInOneBankDepositListsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('current_all_in_one_bank_deposit_lists', function (Blueprint $table) {
            $table->biginteger('b_id')->unsigned()->nullable();
            $table->foreign('b_id')->references('id')->on('bank_deposit_lists');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('current_all_in_one_bank_deposit_lists', function (Blueprint $table) {

        });
    }
}
