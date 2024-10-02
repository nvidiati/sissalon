<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use App\Console\Commands\BookingNotification;
use App\Console\Commands\ChangeModulePermissions;
use App\Console\Commands\CheckForCouponExpire;
use App\Console\Commands\HideCron;
use App\Console\Commands\PackageLicenceExpire;
use App\Console\Commands\TrialPackageExpiredNotification;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        ChangeModulePermissions::class,
        BookingNotification::class,
        PackageLicenceExpire::class,
        TrialPackageExpiredNotification::class,
        CheckForCouponExpire::class,
        HideCron::class
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('booking:notification')->everyMinute();
        $schedule->command('change:package')->everyMinute();
        $schedule->command('trial:notification')->everyMinute();
        $schedule->command('coupon:expired')->everyMinute();
        $schedule->command('hide:cron')->daily();
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
