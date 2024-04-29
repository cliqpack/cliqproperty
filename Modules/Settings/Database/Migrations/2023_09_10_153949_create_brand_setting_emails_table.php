<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBrandSettingEmailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('brand_setting_emails', function (Blueprint $table) {
            $table->id();
            $table->boolean('left_header_btn')->default(false);
            $table->boolean('middle_header_btn')->default(false);
            $table->boolean('right_header_btn')->default(false);
            $table->boolean('left_header_text_btn')->default(false);
            $table->boolean('middle_header_text_btn')->default(false);
            $table->boolean('right_header_text_btn')->default(false);
            $table->boolean('left_footer_btn')->default(false);
            $table->boolean('middle_footer_btn')->default(false);
            $table->boolean('right_footer_btn')->default(false);
            $table->boolean('left_footer_text_btn')->default(false);
            $table->boolean('middle_footer_text_btn')->default(false);
            $table->boolean('right_footer_text_btn')->default(false);
            $table->boolean('reason_modal')->default(false);
            $table->boolean('checked')->default(false);
            $table->string('header_bg_color')->nullable();
            $table->string('footer_bg_color')->nullable();
            $table->string('body_color')->nullable();
            $table->string('body_bg_color')->nullable();
            $table->string('height')->nullable();
            $table->string('header_color')->nullable();
            $table->string('footer_color')->nullable();
            $table->string('selected_font')->nullable();
            $table->integer('selected_font_size')->nullable();
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
        Schema::dropIfExists('brand_setting_emails');
    }
}
