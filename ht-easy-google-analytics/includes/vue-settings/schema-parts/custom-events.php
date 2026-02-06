<?php
return ['custom-events_route' => [
    'title' => __('Custom Events', 'ht-easy-ga4'),
    
    'sections' => [],
    
    'texts' => [
        'edit' => __('Edit', 'ht-easy-ga4'),
        'delete' => __('Delete', 'ht-easy-ga4'),
        'add_new' => __('Add New Event', 'ht-easy-ga4'),
        'basic_settings' => __('Basic Settings', 'ht-easy-ga4'),
        'trigger_settings' => __('Trigger Settings', 'ht-easy-ga4'),
        'event_parameters' => __('Event Parameters', 'ht-easy-ga4'),
        'cancel' => __('Cancel', 'ht-easy-ga4'),
        'save' => __('Save', 'ht-easy-ga4'),
        'enable' => __('Enable', 'ht-easy-ga4'),
        'disable' => __('Disabled', 'ht-easy-ga4'),
    ],
    
    'fields' => [
        'custom_events' => [
            'id' => 'custom_events',
            'type' => 'group',
            'title' => __('Custom Events', 'ht-easy-ga4'),
            'class' => 'ht_custom_events_manager',
            'button_title' => '<i class="fa fa-plus"></i>',
            'fields' => [
                'name' => [
                    'id' => 'name',
                    'type' => 'text',
                    'title' => __('Event Name', 'ht-easy-ga4'),
                    'desc' => __('A human-friendly name for this custom event (shown in admin only)', 'ht-easy-ga4'),
                    'placeholder' => __('e.g: Contact Form Submission', 'ht-easy-ga4'),
                    'default' => '',
                    'required' => true,
                ],
                'event_name' => [
                    'id' => 'event_name',
                    'type' => 'text',
                    'title' => __('GA4 Event Name', 'ht-easy-ga4'),
                    'desc' => __('The actual event name sent to GA4. Must be in snake_case format.', 'ht-easy-ga4'),
                    'placeholder' => __('e.g: form_submit, button_click, file_download', 'ht-easy-ga4'),
                    'default' => '',
                    'required' => true,
                    'validation' => 'snake_case',
                ],
                'active' => [
                    'id' => 'active',
                    'type' => 'switch',
                    'title' => __('Enable', 'ht-easy-ga4'),
                    'label' => __('Yes', 'ht-easy-ga4'),
                    'default' => false,
                ],
                'trigger_type' => [
                    'id' => 'trigger_type',
                    'type' => 'select',
                    'title' => __('Trigger Type', 'ht-easy-ga4'),
                    'desc' => __('What action should fire this event?', 'ht-easy-ga4'),
                    'options' => [
                        'click' => __('Click', 'ht-easy-ga4'),
                        'form_submit' => __('Form Submit', 'ht-easy-ga4'),
                        'page_view' => __('Page View', 'ht-easy-ga4'),
                    ],
                    'default' => 'click',
                    'required' => true,
                ],
                'trigger_value' => [
                    'id' => 'trigger_value',
                    'type' => 'text',
                    'title' => __('Trigger Target', 'ht-easy-ga4'),
                    'desc' => __('CSS selector (e.g., .button, #form) or URL path (e.g., /thank-you). Use presets above for common elements.', 'ht-easy-ga4'),
                    'placeholder' => __('e.g: .cta-button, #contact-form, /thank-you', 'ht-easy-ga4'),
                    'default' => '',
                    'required' => true,
                ],
                'trigger_preset' => [
                    'id' => 'trigger_preset',
                    'type' => 'select',
                    'title' => __('Quick Preset', 'ht-easy-ga4'),
                    'desc' => __('Choose a preset to auto-fill the trigger target', 'ht-easy-ga4'),
                    'options' => [
                        '' => __('— Select What to Track —', 'ht-easy-ga4'),
                        'custom' => __('✏️ Use custom selector', 'ht-easy-ga4'),
                        'File Downloads' => [
                            'pdf_download' => __('Track PDF downloads', 'ht-easy-ga4'),
                            'zip_download' => __('Track ZIP downloads', 'ht-easy-ga4'),
                            'doc_download' => __('Track Word document downloads', 'ht-easy-ga4'),
                            'all_file_downloads' => __('Track all file downloads', 'ht-easy-ga4'),
                        ],
                        'Link Clicks' => [
                            'external_link' => __('Track external links', 'ht-easy-ga4'),
                            'affiliate_link' => __('Track affiliate links', 'ht-easy-ga4'),
                            'email_click' => __('Track email address clicks', 'ht-easy-ga4'),
                            'phone_click' => __('Track phone number clicks', 'ht-easy-ga4'),
                        ],
                        'UI Buttons' => [
                            'add_to_cart' => __('Track Add to Cart button', 'ht-easy-ga4'),
                            'checkout_button' => __('Track Checkout button', 'ht-easy-ga4'),
                            'signup_button' => __('Track Signup/Subscribe button', 'ht-easy-ga4'),
                        ],
                        'Behavioral Actions' => [
                            'video_play' => __('Track Play Video button', 'ht-easy-ga4'),
                            'reveal_coupon' => __('Track Reveal Coupon', 'ht-easy-ga4'),
                            'faq_toggle' => __('Track FAQ Toggle', 'ht-easy-ga4'),
                        ],
                    ],
                    'default' => '',
                ],
                'parameter_mode' => [
                    'id' => 'parameter_mode',
                    'type' => 'select',
                    'title' => __('Parameter Mode', 'ht-easy-ga4'),
                    'desc' => __('Choose between simple or advanced parameter configuration', 'ht-easy-ga4'),
                    'options' => [
                        'simple' => __('Simple Mode', 'ht-easy-ga4'),
                        'advanced' => __('Advanced Mode', 'ht-easy-ga4'),
                    ],
                    'default' => 'simple',
                ],
                'event_category' => [
                    'id' => 'event_category',
                    'type' => 'text',
                    'title' => __('Event Category', 'ht-easy-ga4'),
                    'desc' => __('Category for grouping events in GA4 (e.g: engagement, conversion, download)', 'ht-easy-ga4'),
                    'placeholder' => __('e.g: engagement', 'ht-easy-ga4'),
                    'default' => '',
                    'condition' => [
                        [
                            'key' => 'parameter_mode',
                            'operator' => '==',
                            'value' => 'simple'
                        ]
                    ],
                ],
                'event_label' => [
                    'id' => 'event_label',
                    'type' => 'text',
                    'title' => __('Event Label', 'ht-easy-ga4'),
                    'desc' => __('Additional label for the event (e.g: Contact Form, Header CTA)', 'ht-easy-ga4'),
                    'placeholder' => __('e.g: Contact Form', 'ht-easy-ga4'),
                    'default' => '',
                    'condition' => [
                        [
                            'key' => 'parameter_mode',
                            'operator' => '==',
                            'value' => 'simple'
                        ]
                    ],
                ],
                'event_value' => [
                    'id' => 'event_value',
                    'type' => 'number',
                    'title' => __('Event Value', 'ht-easy-ga4'),
                    'desc' => __('Numeric value for the event (optional)', 'ht-easy-ga4'),
                    'placeholder' => __('e.g: 1', 'ht-easy-ga4'),
                    'default' => 1,
                    'condition' => [
                        [
                            'key' => 'parameter_mode',
                            'operator' => '==',
                            'value' => 'simple'
                        ]
                    ],
                    'attributes' => [
                        'min' => '0',
                        'step' => '1',
                    ]
                ],
                'parameters' => [
                    'id' => 'parameters',
                    'type' => 'group',
                    'title' => __('Advanced Parameters', 'ht-easy-ga4'),
                    'desc' => __('Define custom GA4 parameters for advanced tracking', 'ht-easy-ga4'),
                    'condition' => [
                        [
                            'key' => 'parameter_mode',
                            'operator' => '==',
                            'value' => 'advanced'
                        ]
                    ],
                    'fields' => [
                        'param_key' => [
                            'id' => 'param_key',
                            'type' => 'text',
                            'title' => __('Parameter Key', 'ht-easy-ga4'),
                            'desc' => __('The parameter name sent to GA4', 'ht-easy-ga4'),
                            'placeholder' => __('e.g: event_category, cta_text, page_url', 'ht-easy-ga4'),
                            'default' => '',
                            'required' => true,
                        ],
                        'param_value_type' => [
                            'id' => 'param_value_type',
                            'type' => 'select',
                            'title' => __('Value Type', 'ht-easy-ga4'),
                            'desc' => __('How to get the parameter value', 'ht-easy-ga4'),
                            'options' => [
                                'static_text' => __('Static Text', 'ht-easy-ga4'),
                                'dynamic_click_text' => __('Dynamic: Clicked Element Text', 'ht-easy-ga4'),
                                'dynamic_href_filename' => __('Dynamic: File Name from Href', 'ht-easy-ga4'),
                                'dynamic_page_url' => __('Dynamic: Current Page URL', 'ht-easy-ga4'),
                                'dynamic_form_id' => __('Dynamic: Form ID', 'ht-easy-ga4'),
                                'dynamic_data_attribute' => __('Dynamic: Data Attribute', 'ht-easy-ga4'),
                                'dynamic_closest_section' => __('Dynamic: Closest Section', 'ht-easy-ga4'),
                            ],
                            'default' => 'static_text',
                            'required' => true,
                        ],
                        'param_value' => [
                            'id' => 'param_value',
                            'type' => 'text',
                            'title' => __('Parameter Value', 'ht-easy-ga4'),
                            'desc' => __('Static value or attribute name for dynamic values', 'ht-easy-ga4'),
                            'placeholder' => __('e.g: engagement or data-location', 'ht-easy-ga4'),
                            'default' => '',
                        ],
                    ],
                    'default' => [],
                ],
            ],
            'accordion_title_prefix' => 'Event: ',
            'accordion_title_number' => true,
            'default' => [],
        ]
    ]
]];