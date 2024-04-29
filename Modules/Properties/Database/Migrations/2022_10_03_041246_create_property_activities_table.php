<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePropertyActivitiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('property_activities', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('property_id')->unsigned()->nullable();
            $table->foreign('property_id')->references('id')->on('properties');
            $table->biginteger('contact_id')->unsigned()->nullable();
            $table->foreign('contact_id')->references('id')->on('contacts');
            $table->biginteger('owner_contact_id')->unsigned()->nullable();
            $table->foreign('owner_contact_id')->references('id')->on('owner_contacts');
            $table->biginteger('tenant_contact_id')->unsigned()->nullable();
            $table->foreign('tenant_contact_id')->references('id')->on('tenant_contacts');
            $table->bigInteger('task_id')->unsigned()->nullable();
            $table->foreign('task_id')->references('id')->on('tasks');
            $table->bigInteger('inspection_id')->unsigned()->nullable();
            $table->foreign('inspection_id')->references('id')->on('inspections');
            $table->bigInteger('maintenance_id')->unsigned()->nullable();
            $table->foreign('maintenance_id')->references('id')->on('maintenances');
            $table->bigInteger('listing_id')->unsigned()->nullable();
            $table->foreign('listing_id')->references('id')->on('listings');
            $table->text('comment')->nullable();
            $table->date('completed')->nullable();
            $table->string('status')->nullable();
            $table->string('type')->nullable();
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
        Schema::dropIfExists('property_activities');
    }
}
