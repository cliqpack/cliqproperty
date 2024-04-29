<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddApproveColumnToReconcilliationMonthsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('reconcilliation_months', function (Blueprint $table) {
            $table->date('enddate')->nullable();
            $table->date('current_date')->nullable();
            $table->string('reconciliation_status')->default('pending');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('reconcilliation_months', function (Blueprint $table) {

        });
    }
}
