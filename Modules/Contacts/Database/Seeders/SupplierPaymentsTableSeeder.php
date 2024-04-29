<?php

namespace Modules\Contacts\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Modules\Contacts\Entities\SupplierPayments;

class SupplierPaymentsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();
        SupplierPayments::factory()->times(50)->create();

        // $this->call("OthersTableSeeder");
    }
}
