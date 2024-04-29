<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddSomeColumnToSellerFoliosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('seller_folios', function (Blueprint $table) {
            $table->double('money_in', 10, 2)->default(0.00);
            $table->double('money_out', 10, 2)->default(0.00);
            $table->double('balance', 10, 2)->default(0.00);
            $table->double('uncleared', 10, 2)->default(0.00);
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

        });
    }
}
