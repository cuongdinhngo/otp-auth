<?php

namespace Cuongnd88\OtpAuth;

use Illuminate\Support\ServiceProvider;

class OtpAuthServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
         $this->commands([
            \Cuongnd88\OtpAuth\MakeOtpAth::class,
            \Cuongnd88\OtpAuth\Commands\MakeOtpAth::class,
        ]);
    }
}