<?php

namespace Webkul\PayGuard\Payment;

class PayGuardBkash extends AbstractPayGuardPayment
{
    protected $code = 'payguard_bkash';

    protected string $provider = 'bkash';
}
