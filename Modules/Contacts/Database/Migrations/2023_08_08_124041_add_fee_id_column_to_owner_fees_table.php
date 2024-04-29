<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddFeeIdColumnToOwnerFeesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('owner_fees', function (Blueprint $table) {
            $table->biginteger('fee_id')->unsigned()->nullable();
            $table->foreign('fee_id')->references('id')->on('fee_settings')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('owner_fees', function (Blueprint $table) {
        });
    }
}
