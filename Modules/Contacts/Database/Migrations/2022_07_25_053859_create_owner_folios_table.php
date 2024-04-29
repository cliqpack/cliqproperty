<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOwnerFoliosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('owner_folios', function (Blueprint $table) {
            $table->id();
            $table->integer('total_money')->nullable();
            $table->integer('balance')->nullable();
            $table->string('regular_intervals')->nullable();
            $table->date('next_disburse_date')->nullable();
            $table->integer('withhold_amount')->nullable();
            $table->string('withold_reason')->nullable();
            $table->date('agreement_start')->nullable();
            $table->string('gained_reason')->nullable();
            $table->text('comment')->nullable();
            $table->date('agreement_end')->nullable();
            $table->boolean('owner_access')->default(false)->nullable();

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
        Schema::dropIfExists('owner_folios');
    }
}
