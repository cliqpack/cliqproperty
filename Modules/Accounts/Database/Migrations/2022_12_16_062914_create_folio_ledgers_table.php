<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFolioLedgersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('folio_ledgers', function (Blueprint $table) {
            $table->id();
            $table->biginteger('company_id')->unsigned();
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->date('date')->nullable();
            $table->string('folio_id')->nullable();
            $table->string('folio_type')->nullable();
            $table->string('opening_balance')->nullable();
            $table->string('closing_balance')->nullable();
            $table->boolean('updated')->default('0');

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
        Schema::dropIfExists('folio_ledgers');
    }
}
