<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Modules\Settings\Entities\Region;

class CreateRegionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('regions', function (Blueprint $table) {
            $table->id();
            $table->string('region_name');

            $table->timestamps();
        });
        Region::create(['region_name' => 'Australian Capital Territory']);
        Region::create(['region_name' => 'New South Wales']);
        Region::create(['region_name' => 'Northern Territory']);
        Region::create(['region_name' => 'Queensland']);
        Region::create(['region_name' => 'South Australia']);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('regions');
    }
}
