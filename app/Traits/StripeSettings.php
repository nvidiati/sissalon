<?php
/**
 * Created by PhpStorm.
 * User: DEXTER
 * Date: 24/05/17
 * Time: 11:29 PM
 */

namespace App\Traits;

use App\PaymentGatewayCredentials;
use App\Scopes\CompanyScope;
use Illuminate\Support\Facades\Config;

trait StripeSettings
{

    public function setStripConfigs()
    {
        $settings  = PaymentGatewayCredentials::withoutGlobalScope(CompanyScope::class)->first();

        $key       = ($this->paymentCredential->stripe_client_id) ? $this->paymentCredential->stripe_client_id : env('STRIPE_KEY');
        $apiSecret = ($this->paymentCredential->stripe_secret) ? $this->paymentCredential->stripe_secret : env('STRIPE_SECRET');
        $webhookKey = ($this->paymentCredential->stripe_webhook_secret) ? $this->paymentCredential->stripe_webhook_secret : env('STRIPE_WEBHOOK_SECRET');

        Config::set('cashier.key', $key);
        Config::set('cashier.secret', $apiSecret);
        Config::set('cashier.webhook.secret', $webhookKey);
    }

}



