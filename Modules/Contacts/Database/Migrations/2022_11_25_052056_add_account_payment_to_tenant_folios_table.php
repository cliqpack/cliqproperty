<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddAccountPaymentToTenantFoliosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('tenant_folios', function (Blueprint $table) {
            $table->integer('total_payout')->nullable();
            $table->integer('advance_pay')->nullable();
            $table->integer('deposit')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('tenant_folios', function (Blueprint $table) {
        });
    }
}
