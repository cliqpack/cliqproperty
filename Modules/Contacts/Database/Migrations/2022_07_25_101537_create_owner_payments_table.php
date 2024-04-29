<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOwnerPaymentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('owner_payments', function (Blueprint $table) {
            $table->id();
            $table->biginteger('owner_contact_id')->unsigned();
            $table->foreign('owner_contact_id')->references('id')->on('owner_contacts')->onDelete('cascade');
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
        Schema::dropIfExists('owner_payments');
    }
}
