<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Modules\UserACL\Http\Controllers\OwnerPlanController;

class TriggerPlan extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'trigger:plan';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $triggerPlan = new OwnerPlanController();
        $triggerPlan->triggerPlan();
        return 0;
    }
}
