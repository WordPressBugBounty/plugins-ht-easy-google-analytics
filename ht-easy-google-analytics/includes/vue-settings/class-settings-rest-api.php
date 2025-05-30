<?php
namespace Ht_Easy_Ga4\Vue_Settings;

class Settings_REST_API {
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

        return rest_ensure_response($settings);
    }

    /**
     * Update settings
     */
    public function update_settings($request) {
        $params = $request->get_params();
        $current_settings = get_option($this->option_name, []);

        // Get allowed fields from schema
        $allowed_fields = array_keys(Settings_Defaults::get_defaults());

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

    private function sanitize_array($array) {
        $sanitized_array = [];
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $sanitized_array[$key] = $this->sanitize_array($value);
            } else {
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
