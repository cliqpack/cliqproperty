<?php

namespace Modules\Settings\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Modules\Settings\Database\factories\BankFactory;
use Modules\Settings\Entities\Bank;

class BanksDatabaseSeederTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $seedData = [
            ['bank_name' => 'Macquarie Bank'],
            ['bank_name' => 'ANZ Bank'],
            ['bank_name' => 'Commonwealth Bank'],
            ['bank_name' => 'NAB Bank'],
            ['bank_name' => 'Westpac Bank'],
            ['bank_name' => 'Bankwest'],
            ['bank_name' => 'Other'],
        ];
        Model::unguard();


        foreach ($seedData as $data) {

            $existingBank = Bank::where('bank_name', $data['bank_name'])->first();

            if (!$existingBank) {

                BankFactory::new()->create($data);
            }
        }
    }
}
