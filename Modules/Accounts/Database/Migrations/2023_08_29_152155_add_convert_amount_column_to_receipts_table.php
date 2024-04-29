<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Doctrine\DBAL\Types\FloatType;
use Doctrine\DBAL\Types\Type;

class AddConvertAmountColumnToReceiptsTable extends Migration
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
        Schema::table('receipts', function (Blueprint $table) {
            $table->double('amount')->default(0.00)->change();
            $table->double('cheque_amount')->default(0.00)->change();
            $table->double('rent_amount')->default(0.00)->change();
            $table->double('deposit_amount')->default(0.00)->change();
            $table->string('new_type')->nullable();
            $table->biginteger('owner_folio_id')->unsigned()->nullable();
            $table->foreign('owner_folio_id')->references('id')->on('owner_folios');
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
        Schema::table('receipts', function (Blueprint $table) {

        });
    }
}
