<?php

namespace Modules\Contacts\Database\Seeders;

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

        // PropertiesAddress::factory()->times(10)->create();
        PropertiesAddress::factory()->times(10)->create();
    }
}
