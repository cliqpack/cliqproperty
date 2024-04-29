<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBuyerPaymentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('buyer_payments', function (Blueprint $table) {
            $table->id();
            $table->biginteger('buyer_contact_id')->unsigned();
            $table->foreign('buyer_contact_id')->references('id')->on('buyer_contacts')->onDelete('cascade');
            $table->string('payment_method')->nullable();
            $table->string('payee')->nullable();
            $table->string('bsb')->nullable();
            $table->string('account_no')->nullable();
            $table->string('split')->nullable();
            $table->string('split_type')->nullable();

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
        Schema::dropIfExists('buyer_payments');
    }
}
