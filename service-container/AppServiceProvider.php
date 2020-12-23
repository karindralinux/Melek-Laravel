<?php

namespace App\Providers;

use App\Billing\BankPaymentGateway;
use App\Billing\CreditPaymentGateway;
use App\Billing\PaymentGateway;
use App\Billing\PaymentGatewayContract;
use Faker\Provider\ar_SA\Payment;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(PaymentGatewayContract::class, function($app) {

            if(request()->has('credit')) {
                return new CreditPaymentGateway('idr');
            }

            return new BankPaymentGateway('idr');
        });
    }

    /**
 * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
