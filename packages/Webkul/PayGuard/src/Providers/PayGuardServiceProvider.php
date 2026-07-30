<?php

namespace Webkul\PayGuard\Providers;

use Illuminate\Support\Facades\Event;
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

        $this->loadViewsFrom(dirname(__DIR__).'/Resources/views', 'payguard');

        // Inject the PayGuard transaction ID into the admin order payment section.
        Event::listen('bagisto.admin.sales.order.payment-method.after', function ($event) {
            $order = $event->getParam('order');

            if ($order && in_array($order->payment->method ?? '', ['payguard_bkash', 'payguard_nagad'])) {
                $event->addTemplate('payguard::admin.orders.payment-info', compact('order'));
            }
        });
    }
}