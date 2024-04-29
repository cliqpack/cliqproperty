<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateChartOfAccountSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('chart_of_account_settings', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->integer('number')->nullable();
            $table->string('type')->nullable();
            $table->string('description')->nullable();
            $table->boolean('tax')->default(0);
            $table->string('category')->nullable();
            $table->boolean('system_account')->default(0);
            $table->string('hidden')->nullable();
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
        Schema::dropIfExists('chart_of_account_settings');
    }
}
