<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Modules\Settings\Entities\FeeTypeSetting;

class CreateFeeTypeSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('fee_type_settings', function (Blueprint $table) {
            $table->id();
            $table->string('fee_type')->nullable();

            $table->timestamps();
        });
        FeeTypeSetting::create(['fee_type' => 'Every rent receipt']);
        FeeTypeSetting::create(['fee_type' => 'First rent receipt']);
        FeeTypeSetting::create(['fee_type' => 'Agreement date - renewed']);
        FeeTypeSetting::create(['fee_type' => 'Every owner invoice receipt']);
        FeeTypeSetting::create(['fee_type' => 'Inspection - completed entry']);
        FeeTypeSetting::create(['fee_type' => 'Inspection - completed exit']);
        FeeTypeSetting::create(['fee_type' => 'Inspection - completed routine']);
        FeeTypeSetting::create(['fee_type' => 'Supplier bill created']);
        FeeTypeSetting::create(['fee_type' => 'Recurring']);
        FeeTypeSetting::create(['fee_type' => 'Manual']);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('fee_type_settings');
    }
}
