<?php

namespace Modules\Contacts\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Modules\Properties\Entities\PropertyActivityEmail;

class PropertyActivityEmailTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();
        PropertyActivityEmail::factory()->times(20)->create();


        // $this->call("OthersTableSeeder");
    }
}
