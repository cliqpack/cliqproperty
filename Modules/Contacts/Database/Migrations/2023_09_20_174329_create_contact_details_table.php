<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateContactDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('contact_details', function (Blueprint $table) {
            $table->id();
            $table->biginteger('contact_id')->unsigned();
            $table->foreign('contact_id')->references('id')->on('contacts')->onDelete('cascade');
            $table->string('reference');
            $table->string('first_name');
            $table->string('last_name');
            $table->string('salutation')->nullable();
            $table->string('company_name')->nullable();
            $table->string('mobile_phone')->nullable();
            $table->string('work_phone')->nullable();
            $table->string('home_phone')->nullable();
            $table->string('email')->nullable();
            $table->string('email_send_type')->default('all_email');
            $table->string('email1')->nullable();
            $table->string('email1_send_type')->nullable();
            $table->string('email2')->nullable();
            $table->string('email2_send_type')->nullable();
            $table->boolean('primary')->nullable();
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
        Schema::dropIfExists('contact_details');
    }
}
