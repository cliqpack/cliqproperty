<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFeeSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('fee_settings', function (Blueprint $table) {
            $table->id();
            $table->string('display_name')->nullable();
            $table->string('charging')->nullable();
            $table->string('fee_type')->nullable();
            $table->biginteger('fee_type_id')->unsigned()->nullable();
            $table->foreign('fee_type_id')->references('id')->on('fee_type_settings')->onDelete('cascade');
            $table->string('value')->nullable();
            $table->string('note')->nullable();
            $table->biginteger('account_id')->unsigned()->nullable();
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
            $table->string('status')->nullable();
            $table->biginteger('company_id')->unsigned();
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('fee_settings');
    }
}
