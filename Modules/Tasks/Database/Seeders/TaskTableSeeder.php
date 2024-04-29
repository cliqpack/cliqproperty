<?php

namespace Modules\Tasks\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Modules\Tasks\Entities\Task;
use Modules\Tasks\Entities\TaskLabel;

class TaskTableSeeder extends Seeder
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
