<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Modules\Settings\Entities\FileFormat;

class CreateFileFormatsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('file_formats', function (Blueprint $table) {
            $table->id();
            $table->string('file_name')->nullable();

            $table->timestamps();
        });
        FileFormat::create(['file_name' => 'Standard ABA format']);
        FileFormat::create(['file_name' => 'ABA format with 1 debit per file']);
        FileFormat::create(['file_name' => 'ABA format with 1 debit per row']);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('file_formats');
    }
}
