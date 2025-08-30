<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class PropertyType extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        Schema::create('property_types', function (Blueprint $table) {
            $table->id('id');
            $table->string('type');
            $table->timestamps();
        });
        DB::table('property_types')->insert([
            [
                'type' => 'Apartment',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'type' => 'House',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'type' => 'Townhouse',
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
        Schema::dropIfExists('property_types');
    }
}
