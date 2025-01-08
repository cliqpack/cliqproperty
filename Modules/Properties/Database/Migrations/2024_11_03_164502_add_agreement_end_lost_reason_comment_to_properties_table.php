<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAgreementEndLostReasonCommentToPropertiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->date('agreement_end')->nullable()->after('status');
            $table->string('lost_reason')->nullable()->after('agreement_end');
            $table->text('comment')->nullable()->after('lost_reason');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->dropColumn(['agreement_end', 'lost_reason', 'comment']);
        });
    }
}
