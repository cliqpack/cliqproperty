<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Doctrine\DBAL\Types\FloatType;
use Doctrine\DBAL\Types\Type;

class AddNetBankBalanceColumnToReconcilliationMonthsDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Type::hasType('double')) {
            Type::addType('double', FloatType::class);
        }
        Schema::table('reconcilliation_months_details', function (Blueprint $table) {
            $table->double('unreconciled_deposits')->default(0.00)->change();
            $table->double('unreconciled_withdrawals')->default(0.00)->change();
            $table->double('adjustment')->default(0.00)->change();
            $table->double('cash_not_banked')->default(0.00)->change();
            $table->double('withdrawals_not_processed')->default(0.00)->change();
            $table->double('new_receipts')->default(0.00)->change();
            $table->double('new_withdrawals')->default(0.00)->change();
            $table->double('net_bank_balance')->default(0.00);
            $table->double('journal_balance')->default(0.00);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('reconcilliation_months_details', function (Blueprint $table) {

        });
    }
}
