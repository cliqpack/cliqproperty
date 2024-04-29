<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddDeletedAtAndSequenceToPropertyRoomTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('property_rooms', function (Blueprint $table) {
            $table->softDeletes();
            $table->bigInteger('sequence_no')->nullable()->default(1);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('property_rooms', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
}
