<?php
namespace Ht_Easy_Ga4\Vue_Settings;

class Settings_REST_API {
    use \Ht_Easy_Ga4\Helper_Trait;
    
    private static $_instance = null;
    private $option_name = 'ht_easy_ga4_options';

    /**
     * Get Instance
     */
    public static function instance(){
        if( is_null( self::$_instance ) ){
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * Constructor
     */
    public function __construct() {
        add_action('rest_api_init', array($this, 'register_rest_routes'));
    }

    /**
     * Register REST API routes
     */
    public function register_rest_routes() {
        register_rest_route(
            'htga4/v1',
            '/settings',
            array(
                array(
                    'methods'             => \WP_REST_Server::READABLE,
                    'callback'            => array($this, 'get_settings'),
                    'permission_callback' => array($this, 'check_permission'),
                ),
                array(
                    'methods'             => \WP_REST_Server::CREATABLE,
                    'callback'            => array($this, 'update_settings'),
                    'permission_callback' => array($this, 'check_permission'),
                ),
            )
        );

        // To expose the roles
        register_rest_route(
            'htga4/v1',
            '/roles',
            array(
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => array($this, 'get_roles'),
                'permission_callback' => array($this, 'check_permission'),
            )
        );
        
        // Tools endpoints for cache management
        register_rest_route(
            'htga4/v1',
            '/tools/clear-cache',
            array(
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => array($this, 'clear_cache'),
                'permission_callback' => array($this, 'check_permission'),
            )
        );
    }

    /**
     * Check if user has permission
     */
    public function check_permission($request) {
        // First check if user has capability
        if (!current_user_can('manage_options')) {
            return new \WP_Error(
                'rest_forbidden',
                esc_html__('You do not have permissions to manage settings.', 'ht-easy-ga4'),
                array('status' => 401)
            );
        }

        // For POST requests, verify nonce
        if ($request->get_method() === 'POST') {
            $nonce = $request->get_header('X-WP-Nonce');

            if (!$nonce || !wp_verify_nonce($nonce, 'wp_rest')) {
                return new \WP_Error(
                    'rest_forbidden',
                    esc_html__('Nonce verification failed.', 'ht-easy-ga4'),
                    array('status' => 401)
                );
            }
        }

        return true;
    }

    /**
     * Get settings
     */
    public function get_settings() {
        $cs_options = get_option($this->option_name, []);
        $settings = wp_parse_args($cs_options, Settings_Defaults::get_defaults());

        // Add default custom events only if custom_events is empty or doesn't exist
        if (empty($settings['custom_events']) || !isset($settings['custom_events'])) {
            $settings['custom_events'] = $this->get_default_custom_events();
        }

        // Fix Google Ads boolean values
        if (isset($settings['google_ads'])) {
            $settings['google_ads']['enabled'] = (bool) filter_var($settings['google_ads']['enabled'] ?? false, FILTER_VALIDATE_BOOLEAN);
            $settings['google_ads']['track_add_to_cart'] = (bool) filter_var($settings['google_ads']['track_add_to_cart'] ?? true, FILTER_VALIDATE_BOOLEAN);
            $settings['google_ads']['track_form_submit'] = (bool) filter_var($settings['google_ads']['track_form_submit'] ?? true, FILTER_VALIDATE_BOOLEAN);
            $settings['google_ads']['debug_mode'] = (bool) filter_var($settings['google_ads']['debug_mode'] ?? false, FILTER_VALIDATE_BOOLEAN);

            // Fix conversion_labels nested boolean values
            if (isset($settings['google_ads']['conversion_labels']) && is_array($settings['google_ads']['conversion_labels'])) {
                foreach ($settings['google_ads']['conversion_labels'] as $event_type => $config) {
                    if (isset($config['enabled'])) {
                        $settings['google_ads']['conversion_labels'][$event_type]['enabled'] = (bool) filter_var($config['enabled'], FILTER_VALIDATE_BOOLEAN);
                    }
                }
            }
        }

        return rest_ensure_response($settings);
    }

    /**
     * Get default custom events for first-time setup
     */
    private function get_default_custom_events() {
        // Check if user has pro version
        $is_pro = $this->is_pro_plugin_active();
        
        // Base default events (always included)
        $default_events = [
            [
                'id' => 'example_contact_form',
                'active' => false,
                'name' => 'Example - Contact Form Submission',
                'event_name' => 'form_submit',
                'trigger_type' => 'form_submit',
                'trigger_value' => '#contact-form, .contact-form, form[action*="contact"]',
                'event_category' => 'engagement',
                'event_label' => 'Contact Form',
                'event_value' => 1,
                'parameters' => [
                    [
                        'param_key' => 'event_category',
                        'param_value_type' => 'static_text',
                        'param_value' => 'engagement'
                    ],
                    [
                        'param_key' => 'form_name',
                        'param_value_type' => 'static_text',
                        'param_value' => 'Contact Form'
                    ],
                    [
                        'param_key' => 'page_url',
                        'param_value_type' => 'dynamic_page_url',
                        'param_value' => ''
                    ]
                ]
            ],
            [
                'id' => 'example_pdf_download',
                'active' => false,
                'name' => 'Example - PDF Document Downloads',
                'event_name' => 'file_download',
                'trigger_type' => 'click',
                'trigger_value' => 'a[href$=".pdf"]',
                'event_category' => 'download',
                'event_label' => 'PDF Document',
                'event_value' => 1,
                'parameters' => [
                    [
                        'param_key' => 'event_category',
                        'param_value_type' => 'static_text',
                        'param_value' => 'download'
                    ],
                    [
                        'param_key' => 'file_type',
                        'param_value_type' => 'static_text',
                        'param_value' => 'PDF'
                    ],
                    [
                        'param_key' => 'file_name',
                        'param_value_type' => 'dynamic_href_filename',
                        'param_value' => ''
                    ],
                    [
                        'param_key' => 'link_text',
                        'param_value_type' => 'dynamic_click_text',
                        'param_value' => ''
                    ]
                ]
            ]
        ];
        
        // Add additional events for pro users
        if ($is_pro) {
            $default_events[] = [
                'id' => 'example_newsletter_signup',
                'active' => false,
                'name' => 'Example - Newsletter Subscription',
                'event_name' => 'sign_up',
                'trigger_type' => 'form_submit',
                'trigger_value' => 'form[action*="newsletter"], .newsletter-form, #newsletter-signup',
                'event_category' => 'engagement',
                'event_label' => 'Newsletter',
                'event_value' => 1,
                'parameters' => [
                    [
                        'param_key' => 'event_category',
                        'param_value_type' => 'static_text',
                        'param_value' => 'engagement'
                    ],
                    [
                        'param_key' => 'method',
                        'param_value_type' => 'static_text',
                        'param_value' => 'newsletter'
                    ],
                    [
                        'param_key' => 'form_id',
                        'param_value_type' => 'dynamic_form_id',
                        'param_value' => ''
                    ]
                ]
            ];
            
            $default_events[] = [
                'id' => 'example_cta_button',
                'active' => false,
                'name' => 'Example - Call-to-Action Button Clicks',
                'event_name' => 'button_click',
                'trigger_type' => 'click',
                'trigger_value' => '.cta-button, .btn-primary, .call-to-action, button[class*="cta"]',
                'event_category' => 'engagement',
                'event_label' => 'CTA Button',
                'event_value' => 1,
                'parameters' => [
                    [
                        'param_key' => 'event_category',
                        'param_value_type' => 'static_text',
                        'param_value' => 'engagement'
                    ],
                    [
                        'param_key' => 'button_text',
                        'param_value_type' => 'dynamic_click_text',
                        'param_value' => ''
                    ],
                    [
                        'param_key' => 'button_location',
                        'param_value_type' => 'dynamic_data_attribute',
                        'param_value' => 'data-location'
                    ],
                    [
                        'param_key' => 'page_section',
                        'param_value_type' => 'dynamic_closest_section',
                        'param_value' => ''
                    ]
                ]
            ];
        }
        
        return $default_events;
    }

    /**
     * Update settings
     */
    public function update_settings($request) {
        $params = $request->get_params();
        $current_settings = get_option($this->option_name, []);

        // Get allowed fields from schema
        $allowed_fields = array_keys(Settings_Defaults::get_defaults());
        
        // Add custom_events to allowed fields since it's a special field without default
        $allowed_fields[] = 'custom_events';

        // Sanitize and update each allowed field
        foreach ($allowed_fields as $field) {
            if (isset($params[$field])) {
                if (is_array($params[$field])) {
                    $current_settings[$field] = $this->sanitize_array($params[$field]);
                } else {
                    $current_settings[$field] = wp_kses_post($params[$field]); // In the previous version users could use html tags
                }
            }
        }

        update_option($this->option_name, $current_settings);

        return $this->get_settings();
    }

    public function get_roles() {
        return rest_ensure_response(htga4_roles_dropdown_options());
    }

    private function sanitize_array($array, $parent_key = '') {
        $sanitized_array = [];
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                // Pass parent key to track nested context
                $sanitized_array[$key] = $this->sanitize_array($value, $key);
            } else {
                // Special handling for boolean fields in google_ads
                if ($parent_key === 'google_ads') {
                    $boolean_fields = ['enabled', 'track_add_to_cart', 'track_form_submit', 'debug_mode'];
                    if (in_array($key, $boolean_fields)) {
                        $sanitized_array[$key] = (bool) filter_var($value, FILTER_VALIDATE_BOOLEAN);
                        continue;
                    }
                }

                // Special handling for boolean 'enabled' field in conversion_labels (purchase, add_to_cart, form_submit, checkout)
                if ($parent_key === 'purchase' || $parent_key === 'add_to_cart' || $parent_key === 'form_submit' || $parent_key === 'checkout') {
                    if ($key === 'enabled') {
                        $sanitized_array[$key] = (bool) filter_var($value, FILTER_VALIDATE_BOOLEAN);
                        continue;
                    }
                }

                $sanitized_array[$key] = wp_kses_post($value);
            }
        }
        return $sanitized_array;
    }
    
    /**
     * Clear all cached data
     * 
     * @param WP_REST_Request $request The REST request object
     * @return WP_REST_Response The response
     */
    public function clear_cache($request) {
        global $wpdb;
        $cleared_keys = [];
        $cleared_count = 0;
        
        // Define patterns to match GA4 transients
        $transient_patterns = [
            '_transient_htga4_standard_report_%',
            '_transient_htga4_ecommerce_report_%',
            '_transient_htga4_realtime_report',
            '_transient_htga4_accounts_v3',
            '_transient_htga4_properties_v3',
            '_transient_htga4_datastreams_v3',
            '_transient_htga4_userinfo',
            '_transient_htga4_data_stream_%'
        ];
        
        try {
            // Process each pattern and delete matching transients
            foreach ($transient_patterns as $pattern) {
                // Get transient option names matching the pattern
                $transients = $wpdb->get_results(
                    $wpdb->prepare(
                        "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s",
                        $pattern
                    )
                );
                
                if (!empty($transients)) {
                    foreach ($transients as $transient) {
                        $transient_name = str_replace('_transient_', '', $transient->option_name);
                        
                        // Delete the transient
                        if (delete_transient($transient_name)) {
                            $cleared_keys[] = $transient_name;
                            $cleared_count++;
                        }
                    }
                }
            }
            
            return rest_ensure_response([
                'success' => true,
                'message' => sprintf(
                    __('Successfully cleared %d cache entries.', 'ht-easy-ga4'),
                    $cleared_count
                ),
                'cleared_count' => $cleared_count,
                'cleared_keys' => $cleared_keys,
                'timestamp' => current_time('mysql')
            ]);
            
        } catch (\Exception $e) {
            return rest_ensure_response([
                'success' => false,
                'message' => sprintf(
                    __('Error clearing cache: %s', 'ht-easy-ga4'),
                    $e->getMessage()
                ),
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
