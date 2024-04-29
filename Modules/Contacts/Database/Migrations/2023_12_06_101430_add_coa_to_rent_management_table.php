<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddCoaToRentManagementTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('rent_management', function (Blueprint $table) {
            $table->biginteger('account_id')->unsigned()->nullable();
            $table->foreign('account_id')->references('id')->on('accounts');
            $table->string('type')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('rent_management', function (Blueprint $table) {
            $table->dropColumn('account_id');
        });
    }
}
