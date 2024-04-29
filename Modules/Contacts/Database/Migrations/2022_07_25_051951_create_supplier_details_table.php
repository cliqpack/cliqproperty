<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSupplierDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('supplier_details', function (Blueprint $table) {
            $table->id();
            $table->biginteger('supplier_contact_id')->unsigned();
            $table->foreign('supplier_contact_id')->references('id')->on('supplier_contacts')->onDelete('cascade');
            $table->string('abn')->nullable();
            $table->string('website')->nullable();
            $table->string('account')->nullable();
            $table->string('priority')->nullable();
            $table->boolean('auto_approve_bills')->default(false)->nullable();

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
        Schema::dropIfExists('supplier_details');
    }
}
