<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCompanySettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('company_settings', function (Blueprint $table) {
            $table->id();

            $table->string('portfolio_supplier')->nullable();
            $table->string('portfolio_name')->nullable();
            $table->biginteger('country_id')->unsigned()->nullable();
            $table->foreign('country_id')->references('id')->on('countries')->onDelete('cascade');
            $table->biginteger('region_id')->unsigned()->nullable();
            $table->foreign('region_id')->references('id')->on('regions')->onDelete('cascade');
            // $table->string('region')->nullable();
            $table->string('licence_number')->nullable();
            $table->boolean('include_property_key_number')->default(0)->nullable();
            $table->boolean('update_inspection_date')->default(0)->nullable();
            $table->boolean('client_access')->default(0)->nullable();

            $table->string('client_access_url')->nullable();
            $table->string('portfolio_id')->nullable();
            $table->string('working_hours')->nullable();
            $table->string('invoice_payment_instructions')->nullable();
            $table->string('inspection_report_disclaimer')->nullable();

            $table->boolean('rental_position_on_receipts')->default(0)->nullable();
            $table->boolean('show_effective_paid_to_dates')->default(0)->nullable();
            $table->boolean('include_paid_bills')->default(0)->nullable();
            $table->boolean('bill_approval')->default(0)->nullable();
            $table->boolean('join_the_test_program')->default(0)->nullable();
            $table->biginteger('company_id')->unsigned();
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
        Schema::dropIfExists('company_settings');
    }
}
