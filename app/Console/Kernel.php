<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Artisan;
use Modules\Addons\SendInvoiceWa\Http\Controllers\SendInvoiceWaController;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //
        \App\Console\Commands\SendInvoiceReminders::class,
        \App\Console\Commands\SendReminderInvoiceWA::class,


    ];

    const task =['EmailCampaigns','AutoClientStatusSync','TicketEscalations','DatabaseBackup'];

    protected function scheduleTimezone()
    {
        return 'Asia/Jakarta';
    }

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    // protected function schedule(Schedule $schedule)
    // {
    //     $schedule->command('Automation --do=CreateInvoices')->dailyAt('6:30');
    //     $schedule->command('Automation --do=InvoiceReminders')->dailyAt('6:30');

    //     if (env('APP_ENV') != 'production') {
    //         \Log::debug("schedule run");
    //     }
    // }

    protected function schedule(Schedule $schedule)
    {
        $schedule->command('Automation --do=CreateInvoices')->dailyAt('6:30');
        $schedule->command('Automation --do=InvoiceReminders')->dailyAt('6:30');
        $schedule->command('send:reminder-invoice-wa')->dailyAt('08:10');
        // $schedule->command('Automation --do=InvoiceReminders')->everyMinute();

        // $schedule->command('Automation --do=AutoTerminations')->dailyAt('6:20');
        // $schedule->command('Automation --do=HistoryDepositeSave')->dailyAt('7:20');
        // $schedule->command('Automation --do=DomainRenewalNotices')->dailyAt('9:00');
        // $schedule->command('Automation --do=AddLateFees')->dailyAt('9:15');
        // $schedule->command('Automation --do=CancellationRequests')->dailyAt('9:30');
        // $schedule->command('Automation --do=FixedTermTerminations')->dailyAt('9:45');
        // $schedule->command('Automation --do=CloseInactiveTickets')->dailyAt('10:00');
        // $schedule->command('Automation --do=AutoClientStatusSync')->dailyAt('10:20');
        // $schedule->command('Automation --do=TicketEscalations')->dailyAt('11:00');
        $schedule->command('backup:clean')->weekly();
        
        //$schedule->command('php artisan Automation --do=DomainTransferSync')->daily('3:30');
        //$schedule->command('php artisan Automation --do=DomainStatusSync')->daily('3:30');
        //Log::info('Cronjob berhasil di run');
       
        /*$schedule->command('Automation --do=OverageBilling')->monthly();*/
        
        // Tambahkan command untuk mengirim pengingat invoice
        $schedule->command('invoices:send-reminders')->dailyAt('6:30');
        // $schedule->command('invoices:send-reminders')->everyMinute();

        $schedule->call(function () {
            app(SendInvoiceWaController::class)->sendReminderInvoiceWA();
        })->dailyAt('7:40'); 

        if (env('APP_ENV') != 'production') {
            \Log::debug("schedule run");
        }
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');
        $this->load(__DIR__.'/Crons');

        require base_path('routes/console.php');
    }

    public function command_exists($name)
    {
        return Arr::exists(Artisan::all(), $name);
    }
}