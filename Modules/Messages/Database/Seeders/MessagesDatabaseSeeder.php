<?php

namespace Modules\Messages\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;

class MessagesDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();
        $this->call(MessageActionNameTableSeeder::class);
        $this->call(MessageActionTriggerTableSeeder::class);
        $this->call(MessageActionTriggerPointTableSeeder::class);
        $this->call(MessageActionSeederTableSeeder::class);
        // $this->call("OthersTableSeeder");
    }
}
