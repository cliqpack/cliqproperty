<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddTenantFolioBondToTenantFoliosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('tenant_folios', function (Blueprint $table) {
            $table->string('pro_rata_to')->nullable();
            $table->boolean('rent_invoice')->nullable();
            $table->integer('bond_already_paid')->nullable();
            $table->integer('bond_receipted')->nullable();
            $table->integer('bond_arreas')->nullable();
            $table->string('bond_reference')->nullable();
            $table->date('break_lease')->nullable();
            $table->date('termination')->nullable();
            $table->text('notes')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('tenant_folios', function (Blueprint $table) {
        });
    }
}
