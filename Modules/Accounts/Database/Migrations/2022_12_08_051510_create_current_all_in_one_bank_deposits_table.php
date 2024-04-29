<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCurrentAllInOneBankDepositsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('current_all_in_one_bank_deposits', function (Blueprint $table) {
            $table->id();
            $table->date('deposit_date')->nullable();
            $table->string('cash')->nullable();
            $table->string('cheque')->nullable();
            $table->string('card')->nullable();
            $table->string('total')->nullable();
            $table->biginteger('company_id')->unsigned()->nullable();
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
        Schema::dropIfExists('current_all_in_one_bank_deposits');
    }
}
