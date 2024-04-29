<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddPropertyDocsToPropertyDocs extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('property_docs', function (Blueprint $table) {
            // $table->dropColumn('property_id');
            $table->biginteger('property_id')->unsigned()->nullable()->change();
            
            $table->biginteger('contact_id')->unsigned()->nullable();
            $table->foreign('contact_id')->references('id')->on('contacts');
            $table->biginteger('owner_id')->unsigned()->nullable();
            $table->foreign('owner_id')->references('id')->on('owner_contacts');
            $table->biginteger('tenant_id')->unsigned()->nullable();
            $table->foreign('tenant_id')->references('id')->on('tenant_contacts');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('property_docs', function (Blueprint $table) {
            $table->biginteger('property_id')->change();
        });
    }
}
