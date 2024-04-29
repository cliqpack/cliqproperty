<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateReconcilliationMonthsDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('reconcilliation_months_details', function (Blueprint $table) {
            $table->id();
            $table->biginteger('r_month_id')->unsigned();
            $table->foreign('r_month_id')->references('id')->on('reconcilliation_months');
            $table->double('unreconciled_deposits')->nullable();
            $table->double('unreconciled_withdrawals')->nullable();
            $table->double('adjustment')->nullable();
            $table->double('cash_not_banked')->nullable();
            $table->double('withdrawals_not_processed')->nullable();
            $table->double('new_receipts')->nullable();
            $table->double('new_withdrawals')->nullable();
            $table->boolean('status')->default(0);
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
        Schema::dropIfExists('reconcilliation_months_details');
    }
}
