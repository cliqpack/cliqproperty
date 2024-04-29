<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddStatementDateToReconcilliationMonthsDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('reconcilliation_months_details', function (Blueprint $table) {
            $table->date('bank_statement_balance_date')->nullable();
            $table->bigInteger('bank_statement_balance')->default(0);
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
