<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Modules\Accounts\Http\Controllers\FolioLedgerController;

class FolioLedgerMonthlyUpdate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ledger:store';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Folio ledger store';

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
        $ledger = new FolioLedgerController();
        $data = $ledger->folioLedgerUpdate();
        if ($data === 'Successful') {
            Log::info('worked');
        } else {
            Log::info('failed');
        }
    }
}
