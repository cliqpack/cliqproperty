<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMailAttachmentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('mail_attachments', function (Blueprint $table) {
            $table->id();
            $table->biginteger('mail_id')->unsigned()->nullable();
            $table->foreign('mail_id')->references('id')->on('message_with_mails')->onDelete('cascade');
            $table->biginteger('attachment_id')->unsigned()->nullable();
            $table->foreign('attachment_id')->references('id')->on('attachments')->onDelete('cascade');
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
        Schema::dropIfExists('mail_attachments');
    }
}
