<?php

namespace Modules\Properties\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Modules\Properties\Entities\Properties;

class PropertiesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *s
     * @return void
     */
    public function run()
    {
        Model::unguard();

        // $this->call("OthersTableSeeder");
        Properties::factory()->times(10)->create();
    }
}
