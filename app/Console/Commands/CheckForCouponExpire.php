<?php

namespace App\Console\Commands;

use App\Coupon;
use App\GlobalSetting;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CheckForCouponExpire extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'coupon:expired';

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
        $settings = GlobalSetting::first();
        $today = Carbon::now()->setTimezone($settings->timezone);

        $coupons = Coupon::all();

        // @codingStandardsIgnoreLine
        // @phpstan-ignore-next-line
        foreach ($coupons as $coupon)
        {
            $coupon_expires_at = $coupon->end_date_time->setTimezone($settings->timezone);

            if ($today->gt($coupon_expires_at))
            {
                $coupon = Coupon::findOrFail($coupon->id);
                $coupon->status = 'expire';
                $coupon->save();
            }
        }
    }

}
