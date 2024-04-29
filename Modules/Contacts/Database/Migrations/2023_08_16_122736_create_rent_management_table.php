<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRentManagementTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('rent_management', function (Blueprint $table) {
            $table->id();
            $table->date('from_date');
            $table->date('to_date');
            $table->biginteger('tenant_id')->unsigned()->nullable();
            $table->foreign('tenant_id')->references('id')->on('tenant_contacts')->onDelete('cascade');
            $table->biginteger('property_id')->unsigned()->nullable();
            $table->foreign('property_id')->references('id')->on('properties')->onDelete('cascade');
            $table->double('rent', 10, 2)->default(0.00);
            $table->double('adjustment', 10, 2)->default(0.00);
            $table->double('deduction', 10, 2)->default(0.00);
            $table->double('due', 10, 2)->default(0.00);
            $table->biginteger('audit')->unsigned()->nullable();
            $table->foreign('audit')->references('id')->on('receipts');
            $table->double('credit', 10, 2)->default(0.00);
            $table->double('received', 10, 2)->default(0.00);

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
        Schema::dropIfExists('rent_management');
    }
}
