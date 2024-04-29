<?php

namespace Modules\Properties\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Modules\Properties\Entities\PropertySalesAgreement;

class PropertySalesAgreementTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();
        PropertySalesAgreement::factory()->times(20)->create();

        // $this->call("OthersTableSeeder");
    }
}
