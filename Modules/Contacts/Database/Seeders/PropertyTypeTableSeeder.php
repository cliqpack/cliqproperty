<?php

namespace Modules\Contacts\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Modules\Properties\Entities\PropertyType;

class PropertyTypeTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();
        PropertyType::factory()->times(10)->create();

        // $this->call("OthersTableSeeder");
    }
}
