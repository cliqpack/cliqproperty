<?php

namespace App\Console;

use App\Console\Commands\FolioLedgerMonthlyUpdate;
use App\Console\Commands\FolioBalanceMonthlyUpdate;
use App\Console\Commands\RecurringBill;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        RecurringBill::class,
        FolioLedgerMonthlyUpdate::class,
        FolioBalanceMonthlyUpdate::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('recurring:bills')->everyMinute();
        $schedule->command('recurring:feebills')->everyMinute();
        $schedule->command('recurring:propertyfeebills')->everyMinute();
        // $schedule->command('trigger:fees')->everyMinute();
        $schedule->command('trigger:plan')->everyMinute();
       
        $schedule->command('imap:externalmail')->everyFiveMinutes();
        // $schedule->command('ledger:monthly-update')->monthlyOn(1, '00:00');
        $schedule->command('ledger:store')->monthly();
        $schedule->command('ledger:monthly-update')->monthlyOn(1, '00:00');

    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');
        require base_path('routes/console.php');
    }
}
