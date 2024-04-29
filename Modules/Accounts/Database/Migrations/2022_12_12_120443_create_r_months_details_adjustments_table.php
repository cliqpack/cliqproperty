<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRMonthsDetailsAdjustmentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('r_months_details_adjustments', function (Blueprint $table) {
            $table->id();
            $table->biginteger('r_month_details_id')->unsigned();
            $table->foreign('r_month_details_id')->references('id')->on('reconcilliation_months_details');
            $table->date('adjustment_date')->nullable();
            $table->string('reason')->nullable();
            $table->boolean('removed')->default(0);
            $table->string('removed_reason')->nullable();
            $table->integer('amount')->nullable();
            $table->biginteger('company_id')->unsigned()->nullable();
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
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
        Schema::dropIfExists('r_months_details_adjustments');
    }
}
