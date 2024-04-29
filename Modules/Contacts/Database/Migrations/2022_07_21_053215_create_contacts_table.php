<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateContactsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('contacts', function (Blueprint $table) {
            $table->id();
            $table->string('reference');
            $table->string('type')->nullable();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('salutation')->nullable();
            $table->string('company_name')->nullable();
            $table->string('mobile_phone')->nullable();
            $table->string('work_phone')->nullable();
            $table->string('home_phone')->nullable();
            $table->string('email');
            $table->string('abn')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('owner')->default(false);
            $table->boolean('tenant')->default(false);
            $table->boolean('supplier')->default(false);
            $table->boolean('seller')->default(false);
            $table->biginteger('company_id')->unsigned();
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
        Schema::dropIfExists('contacts');
    }
}
