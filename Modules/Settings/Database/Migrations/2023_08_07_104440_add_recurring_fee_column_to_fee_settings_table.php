<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddRecurringFeeColumnToFeeSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('fee_settings', function (Blueprint $table) {
            $table->string('frequnecy_type')->nullable();
            $table->string('weekly')->nullable();
            $table->integer('monthly')->nullable();
            $table->string('yearly')->nullable();
            $table->string('time')->nullable();
            $table->boolean('status')->default(1)->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('fee_settings', function (Blueprint $table) {
        });
    }
}
