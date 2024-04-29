<?php

namespace Modules\Contacts\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Modules\Tasks\Entities\TaskLabel;

class TaskLabelTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();
        TaskLabel::factory()->times(20)->create();

        // $this->call("OthersTableSeeder");
    }
}
