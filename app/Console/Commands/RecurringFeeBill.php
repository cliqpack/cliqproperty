<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Modules\Accounts\Http\Controllers\BillsController;
use Modules\Accounts\Http\Controllers\RecurringFeeBillController;

class RecurringFeeBill extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'recurring:feebills';

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
        $bills=new RecurringFeeBillController();
        $bills->recurringFeeBill();
    }
}
