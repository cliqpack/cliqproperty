<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Modules\Settings\Entities\Bank;

class AddShortColumnToBanksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('banks', function (Blueprint $table) {
            $table->string('abatype')->nullable();
            $table->string('bank_identity')->nullable();
            $table->string('short_name')->nullable();
        });

        Bank::create([
            'bank_name' => 'Other',
            'abatype' => 'Unknown',
            'bank_identity' => '000000',
            'short_name' => '    '
        ]);
        Bank::create([
            'bank_name' => 'ANZ Bank',
            'abatype' => 'AbaWithOneDebitPerFile',
            'bank_identity' => '000000',
            'short_name' => 'ANZ'
        ]);
        Bank::create([
            'bank_name' => 'Commonwealth Bank',
            'abatype' => 'AbaWithOneDebitPerFile',
            'bank_identity' => '301500',
            'short_name' => 'CBA'
        ]);
        Bank::create(['bank_name' => 'Macquarie Bank', 'abatype' => 'AbaWithOneDebitPerFile', 'bank_identity' => '000000', 'short_name' => 'MBL']);
        Bank::create(['bank_name' => 'NAB Bank', 'abatype' => 'AbaWithOneDebitPerFile', 'bank_identity' => '000000', 'short_name' => 'NAB']);
        Bank::create(['bank_name' => 'Westpac Bank', 'abatype' => 'AbaWithOneDebitPerFile', 'bank_identity' => '037819', 'short_name' => 'WBC']);
        Bank::create(['bank_name' => 'Bankwest', 'abatype' => 'AbaWithOneDebitPerFile', 'bank_identity' => '175029', 'short_name' => 'BWA']);
        Bank::create(['bank_name' => 'St. George Bank', 'abatype' => 'AbaWithOneDebitPerFile', 'bank_identity' => '000000', 'short_name' => 'STG']);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('banks', function (Blueprint $table) {
        });
    }
}
