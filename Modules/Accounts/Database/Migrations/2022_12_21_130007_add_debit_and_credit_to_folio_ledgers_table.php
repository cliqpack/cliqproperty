<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddDebitAndCreditToFolioLedgersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('folio_ledgers', function (Blueprint $table) {
            $table->bigInteger('debit')->default(0);
            $table->bigInteger('credit')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('folio_ledgers', function (Blueprint $table) {
        });
    }
}
