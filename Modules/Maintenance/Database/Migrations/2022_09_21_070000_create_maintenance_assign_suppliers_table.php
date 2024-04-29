<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMaintenanceAssignSuppliersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('maintenance_assign_suppliers', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('job_id')->unsigned();
            $table->foreign('job_id')->references('id')->on('maintenances')->onDelete('cascade');
            $table->biginteger('supplier_id')->unsigned()->nullable();
            $table->foreign('supplier_id')->references('id')->on('supplier_contacts')->onDelete('cascade');
            $table->biginteger('owner_id')->unsigned()->nullable();
            $table->foreign('owner_id')->references('id')->on('owner_contacts')->onDelete('cascade');
            $table->biginteger('tenant_id')->unsigned()->nullable();
            $table->foreign('tenant_id')->references('id')->on('tenant_contacts')->onDelete('cascade');

            $table->string('status');
            $table->string('assign_from');

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
        Schema::dropIfExists('maintenance_assign_suppliers');
    }
}
