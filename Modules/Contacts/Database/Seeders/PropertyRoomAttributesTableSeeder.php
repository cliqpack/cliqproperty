<?php

namespace Modules\Contacts\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Modules\Properties\Entities\PropertyRoomAttributes;

class PropertyRoomAttributesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();
        PropertyRoomAttributes::factory()->times(20)->create();

        // $this->call("OthersTableSeeder");
    }
}
