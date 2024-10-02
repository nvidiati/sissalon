<?php

namespace App\Console\Commands;

use App\GlobalSetting;
use Carbon\Carbon;
use Illuminate\Console\Command;

class HideCron extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hide:cron';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Hide cron';

    /**
     * Execute the console command.
     *
     * @return int
     */

    public function handle()
    {
        $global = GlobalSetting::first();
        $global->hide_cron_message = 1;
        $global->last_cron_run = Carbon::now();
        /* @phpstan-ignore-next-line */
        $global->update();
    }

}
