<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Modules\Accounts\Http\Controllers\FolioLedgerController;

class FolioBalanceMonthlyUpdate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ledger:monthly-update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process end-of-month ledger balances';

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
        Log::info('FolioBalanceMonthlyUpdate Command Started');

        $ledger = new FolioLedgerController();
        $response = $ledger->next_month_opening_balance();

       
        if (is_object($response) && isset($response->original['Status']) && $response->original['Status'] == 'Successful') {
            Log::info('FolioBalanceMonthlyUpdate Command Executed Successfully');
        } else {
            Log::error('FolioBalanceMonthlyUpdate Command Failed', ['response' => $response]);
        }

        Log::info('FolioBalanceMonthlyUpdate Command Ended');
    }


}
