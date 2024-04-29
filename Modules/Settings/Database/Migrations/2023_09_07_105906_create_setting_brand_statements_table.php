<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSettingBrandStatementsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('setting_brand_statements', function (Blueprint $table) {
            $table->id();
            $table->integer('header_height_by_millimeter')->nullable();
            $table->boolean('hide_report_header')->nullable();
            $table->boolean('is_hard_copy')->nullable();
            $table->boolean('is_logo_include_address')->nullable();
            $table->boolean('is_logo_include_name')->nullable();
            $table->integer('logo_maximum_height')->nullable();
            $table->string('logo_position')->nullable();
            $table->string('logo_width')->nullable();
            $table->string('primary_colour')->nullable();
            $table->boolean('print_address_next_to_logo')->nullable();
            $table->boolean('print_name_next_to_logo')->nullable();
            $table->string('secondary_colour')->nullable();
            $table->boolean('show_report_header')->nullable();
            $table->string('third_colour')->nullable();
            $table->bigInteger('company_id')->unsigned()->nullable();
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');

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
        Schema::dropIfExists('setting_brand_statements');
    }
}
