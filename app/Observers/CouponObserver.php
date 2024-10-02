<?php

namespace App\Observers;

use App\Coupon;

class CouponObserver
{

    public function saving(Coupon $coupon)
    {
        if ($coupon->isDirty('days')){
            $coupon->days = json_encode($coupon->days);
        }
    }

}
