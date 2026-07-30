<?php

return [
    'payguard_bkash' => [
        'code'        => 'payguard_bkash',
        'title'       => 'bKash',
        'description' => 'Pay securely with bKash via PayGuard',
        'class'       => 'Webkul\PayGuard\Payment\PayGuardBkash',
        'active'      => true,
        'sort'        => 5,
    ],

    'payguard_nagad' => [
        'code'        => 'payguard_nagad',
        'title'       => 'Nagad',
        'description' => 'Pay securely with Nagad via PayGuard',
        'class'       => 'Webkul\PayGuard\Payment\PayGuardNagad',
        'active'      => true,
        'sort'        => 6,
    ],
];
