<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSellerPaymentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('seller_payments', function (Blueprint $table) {
            $table->id();
            $table->biginteger('seller_contact_id')->unsigned();
            $table->foreign('seller_contact_id')->references('id')->on('seller_contacts')->onDelete('cascade');
            $table->string('method')->nullable();
            $table->string('payee')->nullable();
            $table->string('bsb')->nullable();
            $table->string('account')->nullable();
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
        Schema::dropIfExists('seller_payments');
    }
}
