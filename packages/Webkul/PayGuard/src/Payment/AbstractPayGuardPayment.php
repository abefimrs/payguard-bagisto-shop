<?php

namespace Webkul\PayGuard\Payment;

use Webkul\Payment\Payment\Payment;

abstract class AbstractPayGuardPayment extends Payment
{
    /**
     * The PayGuard MFS name used in route generation and API calls: 'bkash' | 'nagad'.
     */
    protected string $provider = '';

    public function getRedirectUrl()
    {
        return route('payguard.redirect', $this->provider);
    }

    public function getAdditionalDetails()
    {
        return [
            'title' => $this->getConfigData('title') ?? $this->code,
            'value' => 'PayGuard',
        ];
    }

    public function getConfigData($field)
    {
        return core()->getConfigData('sales.payment_methods.'.$this->code.'.'.$field);
    }

    /**
     * The PayGuard `mfs_connection_id` configured for this specific method.
     */
    public function getConnectionId()
    {
        return $this->getConfigData('connection_id');
    }
}
