<?php

namespace Modules\Contacts\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Modules\Contacts\Entities\OwnerFolio;

class OwnerFolioTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();
        OwnerFolio::factory()->times(10)->create();

        // $this->call("OthersTableSeeder");
    }
}
