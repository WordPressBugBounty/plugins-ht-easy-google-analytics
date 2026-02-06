<?php
return ['cookie_notice_route' => [
    'title' => __('GDPR Notice', 'ht-easy-ga4'),

    'sections' => [
        'display_setup' => [
            'title' => __('Display Setup', 'ht-easy-ga4'),
            'description' => __('Select how do you want to display this banner', 'ht-easy-ga4'),
        ],
        'content_setup' => [
            'title' => __('Content Setup', 'ht-easy-ga4'),
            'description' => __('Setup banner content from here', 'ht-easy-ga4'),
        ],
        'color_setup' => [
            'title' => __('Color Setup', 'ht-easy-ga4'),
            'description' => __('Setup color of different parts of the banner from here', 'ht-easy-ga4'),
        ],
    ],

    'fields' => [
        'cookie_notice_enabled' => [
            'id' => 'cookie_notice_enabled',
            'type' => 'switch',
            'title' => __('Enable GDPR Notice', 'ht-easy-ga4'),
            'default' => false,
            'help' => __('Enable GDPR notice on your website.', 'ht-easy-ga4')
        ],
        'cookie_notice_overlay_enabled' => [
            'id' => 'cookie_notice_overlay_enabled',
            'type' => 'switch',
            'title' => __('Overlap Notice on Page Content', 'ht-easy-ga4'),
            'default' => true,
            'help' => __('• When checked, the Notice overlaps your content.<br>• Unchecking this will push page content upward to make space for the notice.', 'ht-easy-ga4'),
            'condition' => [
                'key' => 'cookie_notice_enabled',
                'operator' => '==',
                'value' => true
            ]
        ],

        // Display Setup
        'cookie_notice_display_region' => [
            'id' => 'cookie_notice_display_region',
            'section' => 'display_setup',
            'type' => 'select',
            'title' => __('Display This Notice on', 'ht-easy-ga4'),
            'options' => [
                '' => __('All Region', 'ht-easy-ga4'),
                'eu' => __('EU Only', 'ht-easy-ga4')
            ],
            'default' => 'eu',
            'help' => __('• When set to "EU Only", the notice will only appear for EU visitors. Non-EU visitors will track normally without seeing the notice.', 'ht-easy-ga4'),
        ],

        'cookie_notice_layout' => [
            'id' => 'cookie_notice_layout',
            'section' => 'display_setup',
            'type' => 'select',
            'title' => __('Notice Layout', 'ht-easy-ga4'),
            'options' => [
                'default' => __('Default', 'ht-easy-ga4'),
                'sidebar' => __('Sidebar', 'ht-easy-ga4'),
                'floating' => __('Floating', 'ht-easy-ga4'),
            ],
            'default' => 'default',
            'help' => __('• When set to "Default", the notice will be displayed in the default layout.<br>• When set to "Sidebar", the notice will be displayed in a sidebar layout.<br>• When set to "Floating", the notice will be displayed in a floating layout.', 'ht-easy-ga4'),
        ],

        // Content Setup
        'cookie_notice_text' => [
            'id' => 'cookie_notice_text',
            'section' => 'content_setup',
            'type' => 'text',
            'title' => __('Notice Text', 'ht-easy-ga4'),
            'default' => __('This website uses cookies to ensure you get the best experience on our website', 'ht-easy-ga4'),
        ],
        'cookie_notice_privacy_url' => [
            'id' => 'cookie_notice_privacy_url',
            'section' => 'content_setup',
            'type' => 'text',
            'title' => __('Privacy Policy URL', 'ht-easy-ga4'),
            'desc' => __('e.g: https://example.com/privacy-policy', 'ht-easy-ga4'),
            'default' => '',
        ],
        'cookie_notice_privacy_text' => [
            'id' => 'cookie_notice_privacy_text',
            'section' => 'content_setup',
            'type' => 'text',
            'title' => __('Privacy Policy Link text', 'ht-easy-ga4'),
            'default' => __('Privacy Policy', 'ht-easy-ga4'),
        ],
        'cookie_notice_accept_text' => [
            'id' => 'cookie_notice_accept_text',
            'section' => 'content_setup',
            'type' => 'text',
            'title' => __('Accept Button Text', 'ht-easy-ga4'),
            'default' => __('Accept', 'ht-easy-ga4'),
        ],
        'cookie_notice_decline_text' => [
            'id' => 'cookie_notice_decline_text',
            'section' => 'content_setup',
            'type' => 'text',
            'title' => __('Decline Button Text', 'ht-easy-ga4'),
            'default' => __('Decline', 'ht-easy-ga4'),
        ],

        // Duration/Persistence Settings
        'cookie_notice_duration_type' => [
            'id' => 'cookie_notice_duration_type',
            'section' => 'display_setup',
            'type' => 'radio',
            'title' => __('Consent Duration', 'ht-easy-ga4'),
            'help' => __('When set to "No expiry", the cookie will not expire. When set to "Custom duration", the cookie will expire after the specified number of days and not be re-shown to the visitor.', 'ht-easy-ga4'),
            'options' => [
                'no_expiry' => __('No expiry', 'ht-easy-ga4'),
                'custom' => __('Custom duration', 'ht-easy-ga4')
            ],
            'default' => 'no_expiry',
        ],
        'cookie_notice_duration_value' => [
            'id' => 'cookie_notice_duration_value',
            'section' => 'display_setup',
            'type' => 'text',
            'title' => __('Duration (in days)', 'ht-easy-ga4'),
            'help' => __('Enter the number of days for which the cookie will be stored. Once the cookie expires, the visitor will see the notice again.', 'ht-easy-ga4'),
            'default' => '365',
            'condition' => [
                'key' => 'cookie_notice_duration_type',
                'operator' => '==',
                'value' => 'custom'
            ]
        ],
        'cookie_notice_cookie_key' => [
            'id' => 'cookie_notice_cookie_key',
            'section' => 'display_setup',
            'type' => 'text',
            'title' => __('Cookie Key Name', 'ht-easy-ga4'),
            'desc' => __('Changing this will re-show the notice to all visitors.', 'ht-easy-ga4'),
            'default' => 'cookie_consent',
        ],

        // Color Setup
        'cookie_notice_bg_color' => [
            'id' => 'cookie_notice_bg_color',
            'section' => 'color_setup',
            'type' => 'color',
            'title' => __('Notice Background Color', 'ht-easy-ga4'),
            'default' => '#0099ff',
        ],
        'cookie_notice_text_color' => [
            'id' => 'cookie_notice_text_color',
            'section' => 'color_setup',
            'type' => 'color',
            'title' => __('Notice Text Color', 'ht-easy-ga4'),
            'default' => '#ffffff',
        ],
        'cookie_notice_accept_bg_color' => [
            'id' => 'cookie_notice_accept_bg_color',
            'section' => 'color_setup',
            'type' => 'color',
            'title' => __('Accept Button Background Color', 'ht-easy-ga4'),
            'default' => '#ffffff',
        ],
        'cookie_notice_accept_text_color' => [
            'id' => 'cookie_notice_accept_text_color',
            'section' => 'color_setup',
            'type' => 'color',
            'title' => __('Accept Button Text Color', 'ht-easy-ga4'),
            'default' => '#000000',
        ],
        'cookie_notice_decline_bg_color' => [
            'id' => 'cookie_notice_decline_bg_color',
            'section' => 'color_setup',
            'type' => 'color',
            'title' => __('Decline Button Background Color', 'ht-easy-ga4'),
            'default' => 'rgba(248, 5, 5, 0)',
        ],
        'cookie_notice_decline_text_color' => [
            'id' => 'cookie_notice_decline_text_color',
            'section' => 'color_setup',
            'type' => 'color',
            'title' => __('Decline Button Text Color', 'ht-easy-ga4'),
            'default' => '#ffffff',
        ],
        'cookie_notice_privacy_link_color' => [
            'id' => 'cookie_notice_privacy_link_color',
            'section' => 'color_setup',
            'type' => 'color',
            'title' => __('Privacy Policy Link Color', 'ht-easy-ga4'),
            'subtitle' => __('Link Color', 'ht-easy-ga4'),
            'default' => '#ffffff',
        ],
    ]
]];