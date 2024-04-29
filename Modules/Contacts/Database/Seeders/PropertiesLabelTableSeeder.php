<?php

namespace Modules\Contacts\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Modules\Contacts\Database\factories\PropertiesLabelFactory;
use Modules\Properties\Entities\PropertiesLabel;

class PropertiesLabelTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();
        PropertiesLabel::factory()->times(20)->create();

        // $this->call("OthersTableSeeder");
    }
}
