<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSettingListingProvidersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('setting_listing_providers', function (Blueprint $table) {
            
            $table->bigIncrements('id');
            $table->string('name')->nullable();
            $table->string('agent_id')->nullable(); 
            $table->boolean('is_available')->default(false);
            $table->boolean('is_enable')->default(false);
            $table->boolean('has_listing_provider_import_in_progress')->default(false);
            $table->unsignedBigInteger('company_id')->nullable(); 
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->string('external_provider_type')->nullable();
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
        Schema::dropIfExists('setting_listing_providers');
    }
}
