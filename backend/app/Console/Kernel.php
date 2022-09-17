<?php

namespace App\Console;

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
        Commands\LeadStatusUpdate::class,
        Commands\ActionSendToLeadSMSNotification::class,
        Commands\ActionConnectAgentLeadViaBlindCall::class,
        Commands\ActionPushAgentDeviceNotification::class,
        Commands\ActionChangeLeadStatus::class,
        Commands\ActionSendToLeadEmailNotification::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('leads:update:to:missed')
            ->everyMinute();
        $schedule->command('reminder:notification')
            ->everyMinute();
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
