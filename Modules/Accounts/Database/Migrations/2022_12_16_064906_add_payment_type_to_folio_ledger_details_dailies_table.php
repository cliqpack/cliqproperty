<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddPaymentTypeToFolioLedgerDetailsDailiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('folio_ledger_details_dailies', function (Blueprint $table) {
            $table->string('payment_type')->nullable();
            $table->biginteger('folio_ledgers_id')->unsigned()->nullable();
            $table->foreign('folio_ledgers_id')->references('id')->on('folio_ledgers');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('folio_ledger_details_dailies', function (Blueprint $table) {
        });
    }
}
