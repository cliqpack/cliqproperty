<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRentActionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('rent_actions', function (Blueprint $table) {
            $table->id();
            $table->string('action')->nullable();
            $table->string('details')->nullable();
            $table->double('amount', 10, 2)->nullable();
            $table->date('date')->nullable();
            $table->bigInteger('tenant_folio_id')->unsigned()->nullable();
            $table->foreign('tenant_folio_id')->references('id')->on('tenant_folios');
            $table->biginteger('company_id')->unsigned()->nullable();
            $table->foreign('company_id')->references('id')->on('companies');
            $table->boolean('status')->default(true);
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
        Schema::dropIfExists('rent_actions');
    }
}
