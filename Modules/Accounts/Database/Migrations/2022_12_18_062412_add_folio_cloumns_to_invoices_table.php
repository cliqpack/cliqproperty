<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddFolioCloumnsToInvoicesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->biginteger('supplier_folio_id')->unsigned()->nullable();
            $table->foreign('supplier_folio_id')->references('id')->on('supplier_details');
            $table->biginteger('owner_folio_id')->unsigned()->nullable();
            $table->foreign('owner_folio_id')->references('id')->on('owner_folios');
            $table->biginteger('tenant_folio_id')->unsigned()->nullable();
            $table->foreign('tenant_folio_id')->references('id')->on('tenant_folios');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('invoices', function (Blueprint $table) {
        });
    }
}
