<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateThreadImapModelsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('thread_imap_models', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('imap_model_id')->index();
            $table->string('message_id')->unique();
            $table->string('message_uid')->nullable();
            $table->string('message_no')->nullable();
            $table->string('in_reply_to_id')->nullable();
            $table->string('reply_to_id')->nullable();
            $table->string('subject')->nullable();
            $table->string('from')->nullable();
            $table->json('to')->nullable();
            $table->json('cc')->nullable();
            $table->json('bcc')->nullable();
            $table->string('date')->nullable();
            $table->longText('body')->nullable();
            $table->timestamps();
            $table->foreign('imap_model_id')->references('id')->on('imap_models')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('thread_imap_models');
    }
}