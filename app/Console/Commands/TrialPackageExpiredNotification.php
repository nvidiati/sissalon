<?php

namespace App\Console\Commands;

use App\Company;
use App\Package;
use Carbon\Carbon;
use Illuminate\Console\Command;
use App\Notifications\TrialEndNotification;
use Notification;

class TrialPackageExpiredNotification extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'trial:notification';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $today = Carbon::now()->format('Y-m-d');

        $companies = Company::active()->whereNotNull('trial_ends_at')->get();

        // @codingStandardsIgnoreLine
        // @phpstan-ignore-next-line
        foreach ($companies as $company)
        {
            $package = Package::where('id', $company->package_id)->first();

            if (!is_null($package->notify_before_days) && $package->notify_before_days > 0) {
            
                $notifyDay = Carbon::parse($company->trial_ends_at)->subDays($package->notify_before_days)->format('Y-m-d');

                if ($today == $notifyDay)
                {
                    Notification::send($company->user, new TrialEndNotification($package));
                }
            }
        }
    }

}
