<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddArchiveColumnToSupplierDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('supplier_details', function (Blueprint $table) {
            $table->boolean('status')->default(true);
            $table->boolean('archive')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('supplier_details', function (Blueprint $table) {
            $table->dropColumn('status');
            $table->dropColumn('archive');
        });
    }
}
