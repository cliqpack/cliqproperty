<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSellerFoliosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('seller_folios', function (Blueprint $table) {
            $table->id();
            $table->date('agreement_start')->nullable();
            $table->date('agreement_end')->nullable();
            $table->integer('asking_price')->nullable();
            $table->integer('commission')->nullable();
            $table->biginteger('seller_contact_id')->unsigned();
            $table->foreign('seller_contact_id')->references('id')->on('seller_contacts')->onDelete('cascade');

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
        Schema::dropIfExists('seller_folios');
    }
}
