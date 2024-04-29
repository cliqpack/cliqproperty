<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\Imap\ImapController;

class ImapMails extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'imap:externalmail';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch external inbox emails using imap';

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
        $imapMails = new ImapController();
        $imapMails->index();
        return 0;
    }
}