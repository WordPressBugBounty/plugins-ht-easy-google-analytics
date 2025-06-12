<?php
$is_pro_active = Ht_Easy_Ga4\Base::instance()->is_pro_plugin_active();
$is_pro = $is_pro_active ? false : true;
$opacity_class = $is_pro_active ? '' : 'htga4-pro-field-opacity';

return ['events-tracking_route' => [
    'title' => __('Events Tracking', 'ht-easy-ga4'),

    'sections' => [
        'e-commerce-events' => [
            'title' => __('E-Commerce Events', 'ht-easy-ga4')
        ],
        'video-events' => [
            'title' => __('Video Events', 'ht-easy-ga4')
        ],
        'audio-events' => [
            'title' => __('Audio Events', 'ht-easy-ga4')
        ]
    ],

    'fields' => [
        // WooCommerce requirement notice
        'woocommerce_requirement_notice' => [
            'id' => 'woocommerce_requirement_notice',
            'section' => 'e-commerce-events',
            'type' => 'woocommerce_requirement',
        ],
        
        // E-Commerce Events
        'enable_ecommerce_events' => [
            'id' => 'enable_ecommerce_events',
            'section' => 'e-commerce-events',
            'type' => 'hidden',
            'title' => __('Enable E-commerce Events', 'ht-easy-ga4'),
            'default' => false
        ],
        'view_item_event' => [
            'id' => 'view_item_event',
            'section' => 'e-commerce-events',
            'type' => 'switch',
            'title' => __('View Product', 'ht-easy-ga4'),
            'help' => __('Fire the View Item event when a visitor views a content (e.g. when a visitor visits a product details page).', 'ht-easy-ga4'),
            'default' => false,
            'is_pro' => $is_pro,
            'conditionn' => [
                [
                    'key' => 'enable_ecommerce_events',
                    'operator' => '==',
                    'value' => true
                ]
            ]
        ],
        'view_item_list_event' => [
            'id' => 'view_item_list_event',
            'section' => 'e-commerce-events',
            'type' => 'switch',
            'title' => __('View Category', 'ht-easy-ga4'),
            'help' => __('Fire the View Item List event when a visitor views a category or archive page.', 'ht-easy-ga4'),
            'default' => false,
            'class' => $opacity_class,
            'conditionn' => [
                [
                    'key' => 'enable_ecommerce_events',
                    'operator' => '==',
                    'value' => true
                ]
            ]
        ],
        'add_to_cart_event' => [
            'id' => 'add_to_cart_event',
            'section' => 'e-commerce-events',
            'type' => 'switch',
            'title' => __('Add to Cart', 'ht-easy-ga4'),
            'help' => __('Fire the Add To Cart event when a visitor adds a product to their cart.', 'ht-easy-ga4'),
            'default' => false,
            'class' => $opacity_class,
            'conditionn' => [
                [
                    'key' => 'enable_ecommerce_events',
                    'operator' => '==',
                    'value' => true
                ]
            ]
        ],
        'begin_checkout_event' => [
            'id' => 'begin_checkout_event',
            'section' => 'e-commerce-events',
            'type' => 'switch',
            'title' => __('Initiate Checkout', 'ht-easy-ga4'),
            'help' => __('Fire the Initiate Checkout event when a user starts checkout.', 'ht-easy-ga4'),
            'default' => false,
            'class' => $opacity_class,
            'conditionn' => [
                [
                    'key' => 'enable_ecommerce_events',
                    'operator' => '==',
                    'value' => true
                ]
            ]
        ],
        'purchase_event' => [
            'id' => 'purchase_event',
            'section' => 'e-commerce-events',
            'type' => 'switch',
            'title' => __('Purchase', 'ht-easy-ga4'),
            'help' => __('Fire the Purchase event when a visitor completes a purchase.', 'ht-easy-ga4'),
            'default' => false,
            'class' => $opacity_class,
            'conditionn' => [
                [
                    'key' => 'enable_ecommerce_events',
                    'operator' => '==',
                    'value' => true
                ]
            ]
        ],
        
        // Video Events
        'vimeo_video_event' => [
            'id' => 'vimeo_video_event',
            'section' => 'video-events',
            'type' => 'switch',
            'title' => __('Vimeo Video', 'ht-easy-ga4'),
            'help' => __('Track Vimeo video plays on your website.', 'ht-easy-ga4'),
            'default' => false,
            'is_pro' => $is_pro
        ],
        'self_hosted_video_event' => [
            'id' => 'self_hosted_video_event',
            'section' => 'video-events',
            'type' => 'switch',
            'title' => __('Self-Hosted Video', 'ht-easy-ga4'),
            'help' => __('Track self-hosted video plays on your website.', 'ht-easy-ga4'),
            'default' => false,
            'class' => $opacity_class
        ],
        'self_hosted_audio_event' => [
            'id' => 'self_hosted_audio_event',
            'section' => 'audio-events',
            'type' => 'switch',
            'title' => __('Self-Hosted Audio', 'ht-easy-ga4'),
            'help' => __('Track self-hosted audio plays on your website.', 'ht-easy-ga4'),
            'default' => false,
            'is_pro' => $is_pro
        ]
    ]
]];