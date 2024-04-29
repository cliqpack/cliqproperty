<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePropertyDocMandarinsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('property_doc_mandarins', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('property_id')->unsigned();
            $table->index('property_id');
            $table->string('doc_path', 255);
          
            $table->biginteger('contact_id')->unsigned()->nullable();
            $table->foreign('contact_id')->references('id')->on('contacts')->onDelete('cascade');
            $table->biginteger('owner_id')->unsigned()->nullable();
            $table->foreign('owner_id')->references('id')->on('owner_contacts')->onDelete('cascade');
            $table->biginteger('tenant_id')->unsigned()->nullable();
            $table->foreign('tenant_id')->references('id')->on('tenant_contacts')->onDelete('cascade');
            $table->string('name', 255)->nullable();
            $table->string('file_size', 255)->nullable();
            $table->string('generated', 255)->nullable();
            $table->biginteger('supplier_contact_id')->unsigned()->nullable();
            $table->foreign('supplier_contact_id')->references('id')->on('supplier_contacts')->onDelete('cascade');
            $table->biginteger('buyer_contact_id')->unsigned()->nullable();
            $table->foreign('buyer_contact_id')->references('id')->on('buyer_contacts')->onDelete('cascade');
            $table->biginteger('seller_contact_id')->unsigned()->nullable();
            $table->foreign('seller_contact_id')->references('id')->on('seller_contacts')->onDelete('cascade');
            $table->biginteger('company_id')->unsigned()->nullable();
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->string('language_code', 255)->nullable();
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
        Schema::dropIfExists('property_doc_mandarins');
    }
}
