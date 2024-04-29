<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTenantFoliosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tenant_folios', function (Blueprint $table) {
            $table->id();
            $table->biginteger('tenant_contact_id')->unsigned();
            $table->foreign('tenant_contact_id')->references('id')->on('tenant_contacts')->onDelete('cascade');
            $table->biginteger('property_id')->unsigned();
            $table->foreign('property_id')->references('id')->on('properties')->onDelete('cascade');
            $table->integer('rent');
            $table->string('rent_type');
            $table->boolean('rent_includes_tax')->nullable();
            $table->integer('bond_required')->nullable();
            $table->integer('bond_held')->nullable();
            $table->date('move_in');
            $table->date('move_out')->nullable();
            $table->date('agreement_start');
            $table->date('agreement_end')->nullable();
            $table->boolean('periodic_tenancy')->nullable();
            $table->date('paid_to');
            $table->integer('part_paid')->nullable();
            $table->integer('invoice_days_in_advance')->nullable();
            $table->integer('rent_review_frequency')->nullable();
            $table->date('next_rent_review')->nullable();
            $table->boolean('exclude_form_arrears')->nullable();
            $table->string('bank_reterence')->nullable();
            $table->string('receipt_warning')->nullable();
            $table->boolean('tenant_access')->nullable();

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
        Schema::dropIfExists('tenant_folios');
    }
}
