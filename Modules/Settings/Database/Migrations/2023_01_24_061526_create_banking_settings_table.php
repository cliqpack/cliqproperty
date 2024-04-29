<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBankingSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('banking_settings', function (Blueprint $table) {
            $table->id();
            $table->string('account_name')->nullable();

            $table->bigInteger('bsb')->nullable();
            $table->bigInteger('account_number')->nullable();
            $table->bigInteger('unique_identifying_number')->nullable();

            $table->bigInteger('bank_id')->unsigned()->nullable();
            $table->foreign('bank_id')->references('id')->on('banks')->onDelete('cascade');

            $table->boolean('eft_payments_enable')->default(0)->nullable();
            $table->string('statement_description_as_property_reference')->nullable();
            $table->string('default_statement_description')->nullable();
            $table->string('de_user_id')->nullable();

            // $table->string('file_format')->nullable();
            $table->bigInteger('file_format_id')->unsigned()->nullable();
            $table->foreign('file_format_id')->references('id')->on('file_formats')->onDelete('cascade');

            $table->boolean('tenant_direct_debitenable_enable')->default(0)->nullable();

            // $table->string('change_to_days_to_clear')->nullable();

            $table->boolean('bpay_enable')->default(0)->nullable();
            $table->bigInteger('customer_id')->nullable();
            $table->string('customer_name')->nullable();
            $table->string('bpay_for')->nullable();
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
        Schema::dropIfExists('banking_settings');
    }
}
