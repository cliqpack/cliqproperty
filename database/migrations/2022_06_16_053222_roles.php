<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Roles extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        Schema::create('roles', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('created_by');
            $table->boolean('soft_delete');
            $table->timestamps();
        });
        DB::table('roles')->insert([
            [
                'name' => 'Property Manager',
                'created_by' => "Admin",
                'soft_delete' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Strata Manager',
                'created_by' => "Admin",
                'soft_delete' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Staff Manager',
                'created_by' => "Admin",
                'soft_delete' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
