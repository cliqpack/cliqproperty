<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Doctrine\DBAL\Types\FloatType;
use Doctrine\DBAL\Types\Type;

class AddConvertAmountColumnToTenantFoliosTable extends Migration
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
        Schema::table('tenant_folios', function (Blueprint $table) {
            $table->double('rent', 10, 2)->default(0)->change();
            $table->double('part_paid', 10, 2)->default(0)->change();
            $table->double('bond_required', 10, 2)->default(0)->change();
            $table->double('bond_held', 10, 2)->default(0)->change();
            $table->double('bond_already_paid', 10, 2)->default(0)->change();
            $table->double('bond_receipted', 10, 2)->default(0)->change();
            $table->double('bond_arreas', 10, 2)->default(0)->change();
            $table->double('due', 10, 2)->default(0)->change();
            $table->double('total_payout', 10, 2)->default(0)->change();
            $table->double('advance_pay', 10, 2)->default(0)->change();
            $table->double('deposit', 10, 2)->default(0)->change();
            $table->double('money_in', 10, 2)->default(0)->change();
            $table->double('money_in', 10, 2)->default(0)->change();
            $table->double('opening_balance', 10, 2)->default(0)->change();
            $table->string('part_paid_description')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('tenant_folios', function (Blueprint $table) {

        });
    }
}
