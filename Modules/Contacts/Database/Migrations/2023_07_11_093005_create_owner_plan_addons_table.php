<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOwnerPlanAddonsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('owner_plan_addons', function (Blueprint $table) {
            $table->id();
            $table->biginteger('owner_contact_id')->unsigned()->nullable();
            $table->foreign('owner_contact_id')->references('id')->on('owner_contacts');
            $table->biginteger('owner_folio_id')->unsigned()->nullable();
            $table->foreign('owner_folio_id')->references('id')->on('owner_folios');
            $table->biginteger('plan_id')->unsigned()->nullable();
            $table->foreign('plan_id')->references('id')->on('menu_plans');
            $table->biginteger('addon_id')->unsigned()->nullable();
            $table->foreign('addon_id')->references('id')->on('addons');
            $table->boolean('optional_addon')->default(false);
            $table->biginteger('company_id')->unsigned()->nullable();
            $table->foreign('company_id')->references('id')->on('companies');
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
        Schema::dropIfExists('owner_plan_addons');
    }
}
