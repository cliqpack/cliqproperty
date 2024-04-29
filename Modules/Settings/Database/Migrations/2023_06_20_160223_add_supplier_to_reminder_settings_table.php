<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddSupplierToReminderSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('reminder_settings', function (Blueprint $table) {
            $table->biginteger('supplier_contact_id')->unsigned()->nullable();
            $table->foreign('supplier_contact_id')->references('id')->on('supplier_contacts')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('reminder_settings', function (Blueprint $table) {
        });
    }
}
