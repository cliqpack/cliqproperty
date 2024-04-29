<?php

namespace Modules\Inspection\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Modules\Inspection\Entities\Inspection;

class InspectionTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();
        Inspection::factory()->times(20)->create();

        // $this->call("OthersTableSeeder");
    }
}
