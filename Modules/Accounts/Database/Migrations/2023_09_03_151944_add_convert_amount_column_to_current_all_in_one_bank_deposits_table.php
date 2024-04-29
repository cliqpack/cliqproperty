<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Doctrine\DBAL\Types\FloatType;
use Doctrine\DBAL\Types\Type;
class AddConvertAmountColumnToCurrentAllInOneBankDepositsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Type::hasType('double')) {
            Type::addType('double', FloatType::class);
        }
        Schema::table('current_all_in_one_bank_deposits', function (Blueprint $table) {
            $table->double('cash', 10, 2)->default(0.00)->change();
            $table->double('cheque', 10, 2)->default(0.00)->change();
            $table->double('card', 10, 2)->default(0.00)->change();
            $table->double('total', 10, 2)->default(0.00)->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('current_all_in_one_bank_deposits', function (Blueprint $table) {

        });
    }
}
