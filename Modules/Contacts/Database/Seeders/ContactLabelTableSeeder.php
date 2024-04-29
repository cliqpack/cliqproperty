<?php

namespace Modules\Contacts\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Modules\Contacts\Entities\ContactLabel;

class ContactLabelTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();
        ContactLabel::factory()->times(20)->create();

        // $this->call("OthersTableSeeder");
    }
}
