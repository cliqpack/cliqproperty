<?php

namespace Modules\Properties\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Modules\Properties\Entities\PropertiesAddress;

class PropertiesAddressTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();
        // Properties::factory()->times(100)->create();
        PropertiesAddress::factory()->times(10)->create();

        // $this->call("OthersTableSeeder");
    }
}
