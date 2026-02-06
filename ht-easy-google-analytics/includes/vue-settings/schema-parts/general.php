<?php
return ['general_route' => [
    'title' => __('General Options', 'ht-easy-ga4'),

    'sections' => [],

    'fields' => [
        'auth_google_button' => [
            'id' => 'auth_google',
            'type' => 'auth_button',
            'title' => __('Authentication with Google', 'ht-easy-ga4'),
            'default' => '',
            'help' => __('Connect your Google Analytics account to start tracking & view reports.', 'ht-easy-ga4'),
            'button_text' => __('Sign in with your Google Analytics account', 'ht-easy-ga4'),
            'logout_text' => __('Logout (%s)', 'ht-easy-ga4')
        ],
        'auth_google' => [
            'id' => 'auth_google',
            'type' => 'hidden',
            'default' => '',
        ],
        'account' => [
            'id' => 'account',
            'type' => 'select',
            'title' => __('Select Account', 'ht-easy-ga4'),
            'help' => __('Choose account from your Google Analytics account', 'ht-easy-ga4'),
            'default' => '',
            'options' => 'accounts',
            'placeholder' => __('Select account', 'ht-easy-ga4'),
            'searchable' => true,
            'condition' => [
                'key' => 'auth_google',
                'operator' => '!=',
                'value' => ''
            ]
        ],
        'property' => [
            'id' => 'property',
            'type' => 'select',
            'title' => __('Property', 'ht-easy-ga4'),
            'help' => __('Select property from your Google Analytics account', 'ht-easy-ga4'),
            'default' => '',
            'placeholder' => __('Select property', 'ht-easy-ga4'),
            'options' => 'properties',
            'condition' => [
                'key' => 'account|auth_google',
                'operator' => '!=',
                'value' => ''
            ]
        ],
        'data_stream_id' => [
            'id' => 'data_stream_id',
            'type' => 'select',
            'title' => __('Data Stream', 'ht-easy-ga4'),
            'help' => __('Select data stream from your Google Analytics account', 'ht-easy-ga4'),
            'default' => '',
            'placeholder' => __('Select data stream', 'ht-easy-ga4'),
            'options' => 'data_streams',
            'condition' => [
                'key' => 'property|auth_google',
                'operator' => '!=',
                'value' => ''
            ]
        ],
        'measurement_id' => [
            'id' => 'measurement_id',
            'type' => 'text',
            'title' => __('GA4 Measurement / Tracking ID', 'ht-easy-ga4'),
            'desc' => __('GA4 Tracking / Measurement ID. This will be automatically populated when you select a data stream.', 'ht-easy-ga4'),
            'default' => '',
            'placeholder' => 'G-XXXXXXXXXX'
        ],
        'exclude_roles' => [
            'id' => 'exclude_roles',
            'type' => 'select',
            'title' => __('Exclude Tracking For', 'ht-easy-ga4'),
            'help' => __('The users of the selected Role(s) will not be tracked', 'ht-easy-ga4'),
            'default' => ['administrator'],
            'options' => 'roles',
            'multiple' => true,
            'placeholder' => __('Select Role', 'ht-easy-ga4'),
            // 'is_pro' => true,
        ],
        'server_side_tracking' => [
            'id' => 'server_side_tracking',
            'type' => 'switch',
            'title' => __('Server-Side Tracking', 'ht-easy-ga4'),
            'help' => __('Enable server-side event tracking via GA4 Measurement Protocol. This bypasses client-side tracking restrictions like ad blockers.', 'ht-easy-ga4'),
            'desc' => __('When enabled, events will be sent directly from your server to Google Analytics 4 using the Measurement Protocol API.', 'ht-easy-ga4'),
            'default' => false,
        ],
        'measurement_protocol_api_secret_select' => [
            'id' => 'measurement_protocol_api_secret_select',
            'type' => 'select',
            'title' => __('Select API Secret from GA4 Account', 'ht-easy-ga4'),
            'help' => __('Choose an existing Measurement Protocol API Secret from your connected Google Analytics account.', 'ht-easy-ga4'),
            'desc' => __('This list auto-populates from your GA4 data stream. If you don\'t see any secrets, create one in: GA4 Admin > Data Streams > Select Stream > Measurement Protocol > Create, or enter it manually below.', 'ht-easy-ga4'),
            'default' => '',
            'placeholder' => __('Select API Secret', 'ht-easy-ga4'),
            'options' => 'measurement_protocol_secrets',
            'condition' => [
                'key' => 'server_side_tracking|auth_google',
                'operator' => '==|==',
                'value' => '1|1'
            ]
        ],
        'measurement_protocol_api_secret' => [
            'id' => 'measurement_protocol_api_secret',
            'type' => 'text',
            'title' => __('Enter API Secret Manually', 'ht-easy-ga4'),
            'help' => __('Enter your GA4 Measurement Protocol API Secret manually if you prefer not to select from the list above.', 'ht-easy-ga4'),
            'desc' => __('Get your API Secret from: GA4 Admin > Data Streams > Select Stream > Measurement Protocol > Create. Keep this secure and do not share it publicly.', 'ht-easy-ga4'),
            'default' => '',
            'placeholder' => __('Enter API Secret (e.g., lqyULSCbSOKT9_YxmKqCsw)', 'ht-easy-ga4'),
            'condition' => [
                'key' => 'server_side_tracking|auth_google',
                'operator' => '==|!=',
                'value' => '1|1'
            ]
        ]
    ]
]];