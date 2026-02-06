<?php
/**
 * Google Ads Settings Schema
 *
 * @package HT Easy GA4
 * @since 1.8.0
 */

return [
    'google-ads' => [
        'label' => __('Google Ads Settings', 'ht-easy-ga4'),
        'fields' => [
            'google_ads' => [
                'type' => 'fieldset',
                'fields' => [
                    'enabled' => [
                        'type' => 'boolean',
                        'label' => __('Enable Google Ads Tracking', 'ht-easy-ga4'),
                        'default' => false,
                        'description' => __('Enable Google Ads conversion tracking on your website', 'ht-easy-ga4')
                    ],
                    'conversion_id' => [
                        'type' => 'string',
                        'label' => __('Conversion ID', 'ht-easy-ga4'),
                        'default' => '',
                        'description' => __('Your Google Ads Conversion ID (e.g., 123456789)', 'ht-easy-ga4'),
                        'placeholder' => '123456789'
                    ],
                    'conversion_label' => [
                        'type' => 'string',
                        'label' => __('Conversion Label', 'ht-easy-ga4'),
                        'default' => '',
                        'description' => __('Your Google Ads Conversion Label', 'ht-easy-ga4'),
                        'placeholder' => 'ABC123XYZ'
                    ],
                    'track_add_to_cart' => [
                        'type' => 'boolean',
                        'label' => __('Track Add to Cart', 'ht-easy-ga4'),
                        'default' => true,
                        'description' => __('Track when users add items to cart', 'ht-easy-ga4')
                    ],
                    'track_form_submit' => [
                        'type' => 'boolean',
                        'label' => __('Track Form Submissions', 'ht-easy-ga4'),
                        'default' => true,
                        'description' => __('Track form submission events', 'ht-easy-ga4')
                    ],
                    'excluded_roles' => [
                        'type' => 'array',
                        'label' => __('Exclude User Roles', 'ht-easy-ga4'),
                        'default' => ['administrator'],
                        'description' => __('Select user roles to exclude from tracking', 'ht-easy-ga4')
                    ],
                    'debug_mode' => [
                        'type' => 'boolean',
                        'label' => __('Debug Mode', 'ht-easy-ga4'),
                        'default' => false,
                        'description' => __('Enable debug mode to see tracking information in browser console', 'ht-easy-ga4')
                    ]
                ]
            ]
        ]
    ]
];