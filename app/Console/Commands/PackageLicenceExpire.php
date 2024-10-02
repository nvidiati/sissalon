<?php

namespace App\Console\Commands;

use App\Company;
use Notification;
use Carbon\Carbon;
use Illuminate\Console\Command;
use App\Notifications\EndPackage;
use App\Notifications\endTrialPackage;

class PackageLicenceExpire extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'change:package';

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

        $companies = Company::whereNotNull('trial_ends_at')->get();

        // @codingStandardsIgnoreLine
        // @phpstan-ignore-next-line
        foreach ($companies as $company)
        {
            $trial_ends_at = Carbon::parse($company->trial_ends_at)->format('Y-m-d');

            if ($trial_ends_at == $today) {
                $company->package_id = 1;
                $company->trial_ends_at = null;
                $company->licence_expire_on = null;
                $company->save();

                Notification::send($company->user, new endTrialPackage());
            }
        }

        $allCompanies = Company::whereNotNull('licence_expire_on')->get();
        
        // @codingStandardsIgnoreLine
        // @phpstan-ignore-next-line
        foreach ($allCompanies as $company)
        {
            $licence_expire_on = Carbon::parse($company->licence_expire_on)->format('Y-m-d');

            if ($licence_expire_on == $today) {
                $company->package_id = 1;
                $company->trial_ends_at = null;
                $company->licence_expire_on = null;
                $company->save();

                Notification::send($company->user, new EndPackage());
            }
        }
    }

}
