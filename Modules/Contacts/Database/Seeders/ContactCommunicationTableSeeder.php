<?php

namespace Modules\Contacts\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Modules\Contacts\Entities\ContactCommunication;
use Modules\Contacts\Entities\Contacts;

class ContactCommunicationTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();
        ContactCommunication::factory()->times(50)->create();

        // $this->call("OthersTableSeeder");
    }
}
