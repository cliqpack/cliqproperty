<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMessagePortfolioEmailSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('message_portfolio_email_settings', function (Blueprint $table) {
            $table->id();
            $table->string('portfolio_email')->nullable();
            $table->bigInteger('message_setting_id')->unsigned()->nullable();
            $table->foreign('message_setting_id')->references('id')->on('message_settings')->onDelete('cascade');
            $table->bigInteger('company_id')->unsigned()->nullable();
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
        Schema::dropIfExists('message_portfolio_email_settings');
    }
}
