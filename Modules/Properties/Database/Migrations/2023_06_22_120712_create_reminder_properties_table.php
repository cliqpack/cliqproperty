<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateReminderPropertiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('reminder_properties', function (Blueprint $table) {
            $table->id();
            $table->biginteger('property_id')->unsigned()->nullable();
            $table->foreign('property_id')->references('id')->on('properties');
            $table->biginteger('reminder_setting_id')->unsigned()->nullable();
            $table->foreign('reminder_setting_id')->references('id')->on('reminder_settings');
            $table->string('name')->nullable();
            $table->string('contact')->nullable();
            $table->string('frequency')->nullable();

            $table->date('due')->nullable();
            $table->date('certificate_expiry')->nullable();
            $table->boolean('system_template')->default(0)->nullable();
            $table->string('frequency_type')->nullable();
            $table->string('notes')->nullable();
            $table->string('attachment')->nullable();
            $table->string('status')->nullable();
            $table->biginteger('supplier_contact_id')->unsigned()->nullable();
            $table->foreign('supplier_contact_id')->references('id')->on('supplier_contacts')->onDelete('cascade');
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
        Schema::dropIfExists('reminder_properties');
    }
}
