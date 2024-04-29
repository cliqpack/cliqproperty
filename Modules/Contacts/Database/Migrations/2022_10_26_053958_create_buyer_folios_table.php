<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBuyerFoliosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('buyer_folios', function (Blueprint $table) {
            $table->id();
            $table->date('agreement_start')->nullable();
            $table->date('agreement_end')->nullable();
            $table->integer('asking_price')->nullable();
            $table->integer('purchase_price')->nullable();
            $table->date('contract_exchange')->nullable();
            $table->date('deposit_due')->nullable();
            $table->date('settlement_due')->nullable();
            $table->integer('commission')->nullable();
            $table->biginteger('buyer_contact_id')->unsigned();
            $table->foreign('buyer_contact_id')->references('id')->on('buyer_contacts')->onDelete('cascade');

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
        Schema::dropIfExists('buyer_folios');
    }
}
