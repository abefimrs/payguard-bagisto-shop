<?php

namespace Webkul\PayGuard\Providers;

use Illuminate\Support\ServiceProvider;

class PayGuardServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            dirname(__DIR__).'/Config/payment-methods.php',
            'payment_methods'
        );

        $this->mergeConfigFrom(
            dirname(__DIR__).'/Config/system.php',
            'core'
        );
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(dirname(__DIR__).'/Routes/web.php');
    }
}
