<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddArchiveColumnToSellerFoliosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('seller_folios', function (Blueprint $table) {
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
        Schema::table('seller_folios', function (Blueprint $table) {
            $table->dropColumn('status');
            $table->dropColumn('archive');
        });
    }
}
