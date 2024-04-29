<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMessageWithMailRepliesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('message_with_mail_replies', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('master_mail_id')->unsigned()->nullable();
            $table->foreign('master_mail_id')->references('id')->on('message_with_mails')->onDelete('cascade');
            $table->string('to');
            $table->string('from');
            $table->string('subject')->nullable();
            $table->text('body')->nullable();
            $table->string('status')->nullable();
            $table->biginteger('company_id')->unsigned()->nullable();
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
        Schema::dropIfExists('message_with_mail_replies');
    }
}
