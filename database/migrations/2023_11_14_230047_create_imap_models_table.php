<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateImapModelsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('imap_models', function (Blueprint $table) {
            $table->id();
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
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('imap_models');
    }
}