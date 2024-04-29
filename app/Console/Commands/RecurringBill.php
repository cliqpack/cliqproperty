<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Modules\Accounts\Http\Controllers\BillsController;

class RecurringBill extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'recurring:bills';

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
        $bills=new BillsController();
        $bills->checkRecurring();
    }
}
