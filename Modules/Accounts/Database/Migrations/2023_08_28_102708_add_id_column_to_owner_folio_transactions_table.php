<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIdColumnToOwnerFolioTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('owner_folio_transactions', function (Blueprint $table) {
            $table->string('payment_type')->nullable();
            $table->biginteger('tenant_folio_id')->unsigned()->nullable();
            $table->foreign('tenant_folio_id')->references('id')->on('tenant_folios');
            $table->biginteger('supplier_folio_id')->unsigned()->nullable();
            $table->foreign('supplier_folio_id')->references('id')->on('supplier_details');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('owner_folio_transactions', function (Blueprint $table) {
        });
    }
}
