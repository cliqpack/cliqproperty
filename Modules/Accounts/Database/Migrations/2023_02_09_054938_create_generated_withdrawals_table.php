<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateGeneratedWithdrawalsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('generated_withdrawals', function (Blueprint $table) {
            $table->id();
            $table->date('create_date')->nullable();
            $table->string('payment_type')->nullable();
            $table->integer('batch')->nullable();
            $table->double('amount', 10, 2)->nullable();
            $table->integer('total_withdrawals')->nullable();
            $table->string('statement')->nullable();
            $table->boolean('status')->default(false);
            $table->biginteger('company_id')->unsigned()->nullable();
            $table->foreign('company_id')->references('id')->on('companies');
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
        Schema::dropIfExists('generated_withdrawals');
    }
}
