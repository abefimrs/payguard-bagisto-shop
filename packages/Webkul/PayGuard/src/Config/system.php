<?php

return [
    [
        'key'    => 'sales.payment_methods.payguard',
        'name'   => 'PayGuard Settings',
        'info'   => 'Shared credentials for PayGuard (bKash & Nagad). Get these from your PayGuard Dashboard → API & Webhooks.',
        'sort'   => 4,
        'fields' => [
            [
                'name'          => 'api_key',
                'title'         => 'API Key',
                'type'          => 'text',
                'validation'    => 'required',
                'channel_based' => false,
            ],
            [
                'name'          => 'base_url',
                'title'         => 'API Base URL',
                'type'          => 'text',
                'default_value' => 'https://app.sourcemonkey.online/api/v1',
                'validation'    => 'required',
                'channel_based' => false,
            ],
            [
                'name'          => 'webhook_secret',
                'title'         => 'Webhook Secret',
                'type'          => 'text',
                'validation'    => 'required',
                'channel_based' => false,
            ],
        ],
    ],

    [
        'key'    => 'sales.payment_methods.payguard_bkash',
        'name'   => 'PayGuard - bKash',
        'info'   => 'Accept payments via bKash through PayGuard.',
        'sort'   => 5,
        'fields' => [
            [
                'name'          => 'active',
                'title'         => 'Status',
                'type'          => 'boolean',
                'default_value' => true,
                'channel_based' => true,
            ],
            [
                'name'          => 'title',
                'title'         => 'Title',
                'type'          => 'text',
                'default_value' => 'bKash',
                'channel_based' => true,
                'locale_based'  => true,
            ],
            [
                'name'          => 'description',
                'title'         => 'Description',
                'type'          => 'textarea',
                'default_value' => 'Pay securely with bKash',
                'channel_based' => true,
                'locale_based'  => true,
            ],
            [
                'name'          => 'connection_id',
                'title'         => 'PayGuard bKash Connection ID',
                'type'          => 'text',
                'validation'    => 'required|integer',
                'channel_based' => false,
            ],
            [
                'name'          => 'sort',
                'title'         => 'Sort Order',
                'type'          => 'text',
                'default_value' => '5',
            ],
        ],
    ],

    [
        'key'    => 'sales.payment_methods.payguard_nagad',
        'name'   => 'PayGuard - Nagad',
        'info'   => 'Accept payments via Nagad through PayGuard.',
        'sort'   => 6,
        'fields' => [
            [
                'name'          => 'active',
                'title'         => 'Status',
                'type'          => 'boolean',
                'default_value' => true,
                'channel_based' => true,
            ],
            [
                'name'          => 'title',
                'title'         => 'Title',
                'type'          => 'text',
                'default_value' => 'Nagad',
                'channel_based' => true,
                'locale_based'  => true,
            ],
            [
                'name'          => 'description',
                'title'         => 'Description',
                'type'          => 'textarea',
                'default_value' => 'Pay securely with Nagad',
                'channel_based' => true,
                'locale_based'  => true,
            ],
            [
                'name'          => 'connection_id',
                'title'         => 'PayGuard Nagad Connection ID',
                'type'          => 'text',
                'validation'    => 'required|integer',
                'channel_based' => false,
            ],
            [
                'name'          => 'sort',
                'title'         => 'Sort Order',
                'type'          => 'text',
                'default_value' => '6',
            ],
        ],
    ],
];
