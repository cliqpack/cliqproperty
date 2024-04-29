<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddStatusAndAddonsToPreRequisiteMenusTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('pre_requisite_menus', function (Blueprint $table) {
            $table->bigInteger('menu_id')->unsigned()->nullable()->change();
            $table->string('addons')->nullable();
            $table->string('status')->default('menu');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('pre_requisite_menus', function (Blueprint $table) {
            $table->bigInteger('menu_id')->nullable(false)->change();
        });
    }
}
