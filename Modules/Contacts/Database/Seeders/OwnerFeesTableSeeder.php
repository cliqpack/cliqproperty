<?php

namespace Modules\Contacts\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Modules\Contacts\Entities\Contacts;
use Modules\Contacts\Entities\OwnerFees;

class OwnerFeesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();
        OwnerFees::factory()->times(20)->create();

        // $this->call("OthersTableSeeder");
    }
}
