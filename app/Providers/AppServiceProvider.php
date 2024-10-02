<?php

namespace App\Providers;

use App\Gateways\Razorpay;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;

class AppServiceProvider extends ServiceProvider
{

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        if (\config('app.redirect_https')) {
            \URL::forceScheme('https');
        }

        Paginator::useBootstrap();
        Schema::defaultStringLength(191);

        $this->app->singleton('razorpay', function () {
            return new Razorpay();
        });
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        if (in_array(\config('app.env'), ['development','local'])){
            $this->app->register(\Barryvdh\LaravelIdeHelper\IdeHelperServiceProvider::class);
        }
    }

}
