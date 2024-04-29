<?php

namespace Modules\Maintenance\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Modules\Maintenance\Entities\MaintenanceLabel;

class MaintenanceLabelTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();
        MaintenanceLabel::factory()->times(20)->create();

        // $this->call("OthersTableSeeder");
    }
}
