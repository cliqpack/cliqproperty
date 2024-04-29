<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOwnerFeesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('owner_fees', function (Blueprint $table) {
            $table->id();
            $table->string('fee_template')->nullable();
            $table->string('income_account')->nullable();
            $table->string('fee_trigger')->nullable();
            $table->string('notes')->nullable();
            $table->string('amount')->nullable();
            $table->biginteger('owner_contact_id')->unsigned();
            $table->foreign('owner_contact_id')->references('id')->on('owner_contacts')->onDelete('cascade');

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
        Schema::dropIfExists('owner_fees');
    }
}
