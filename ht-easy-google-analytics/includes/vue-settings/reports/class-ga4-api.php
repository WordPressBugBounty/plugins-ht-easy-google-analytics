<?php
namespace Ht_Easy_Ga4\Vue_Settings;

class GA4_API {
    /**
     * @var Reports_Manager
     */
    private $reports_manager;

    private static $_instance = null;
    private $option_name = 'ht_easy_ga4_options';

    /**
     * Google API URLs
     *
     * @var array
     */
    private $google_api_urls = [
        'userinfo' => 'https://www.googleapis.com/oauth2/v3/userinfo',
    ];

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
     * Get common REST API arguments
     * 
     * @param string $type Type of arguments to get ('date', 'force_refresh', or 'all')
     * @return array Common REST API arguments
     */
    private function get_common_args($type = 'all') {
        $args = [];
        
        // Date filtering arguments
        if ($type === 'date' || $type === 'all') {
            $args['date_from'] = array(
                'required'          => false,
                'type'              => 'string',
                'description'       => __('Start date for filtering (YYYY-MM-DD format)', 'ht-easy-ga4'),
                'validate_callback' => function($param) {
                    return empty($param) || preg_match('/^\d{4}-\d{2}-\d{2}$/', $param);
                }
            );
            
            $args['date_to'] = array(
                'required'          => false,
                'type'              => 'string',
                'description'       => __('End date for filtering (YYYY-MM-DD format)', 'ht-easy-ga4'),
                'validate_callback' => function($param) {
                    return empty($param) || preg_match('/^\d{4}-\d{2}-\d{2}$/', $param);
                }
            );
        }
        
        // Force refresh argument
        if ($type === 'force_refresh' || $type === 'all') {
            $args['force_refresh'] = array(
                'required'          => false,
                'type'              => 'integer',
                'description'       => __('Force refresh data by clearing transient cache', 'ht-easy-ga4'),
                'validate_callback' => function($param) {
                    return is_numeric($param);
                }
            );
        }
        
        return $args;
    }
    
    /**
     * Get userinfo endpoint arguments
     * 
     * @return array Userinfo endpoint arguments
     */
    private function get_userinfo_args() {
        // Only need force refresh argument since access token is retrieved internally
        $args = $this->get_common_args('force_refresh');
        
        return $args;
    }
    
    /**
     * Get datastream endpoint arguments
     * 
     * @return array Datastream endpoint arguments
     */
    private function get_datastream_args() {
        $args = [
            'property_id' => [
                'required'          => true,
                'type'              => 'string',
                'description'       => __('GA4 Property ID', 'ht-easy-ga4'),
                'validate_callback' => function($param) {
                    return !empty($param);
                }
            ],
            'stream_id' => [
                'required'          => true,
                'type'              => 'string',
                'description'       => __('GA4 Data Stream ID', 'ht-easy-ga4'),
                'validate_callback' => function($param) {
                    return !empty($param);
                }
            ]
        ];
        
        // Add force refresh argument
        $force_refresh_args = $this->get_common_args('force_refresh');
        $args = array_merge($args, $force_refresh_args);
        
        return $args;
    }
    
    /**
     * Get standard report endpoint arguments
     * 
     * @return array Standard report endpoint arguments
     */
    private function get_standard_report_args() {
        // Standard reports need all common arguments
        return $this->get_common_args('all');
    }
    
    /**
     * Get ecommerce report endpoint arguments
     * 
     * @return array Ecommerce report endpoint arguments
     */
    private function get_ecommerce_report_args() {
        // Ecommerce reports need all common arguments
        return $this->get_common_args('all');
    }
    
    /**
     * Get realtime report endpoint arguments
     * 
     * @return array Realtime report endpoint arguments
     */
    private function get_realtime_report_args() {
        // Realtime reports only need force refresh argument
        return $this->get_common_args('force_refresh');
    }
    
    /**
     * Register REST API routes
     */
    public function register_rest_routes() {
        register_rest_route(
            'htga4/v1',
            '/userinfo',
            array(
                array(
                    'methods'             => \WP_REST_Server::READABLE,
                    'callback'            => array($this, 'get_userinfo'),
                    'permission_callback' => array($this, 'check_permission'),
                    'args'                => $this->get_userinfo_args(),
                ),
            )
        );
        
        register_rest_route(
            'htga4/v1',
            '/datastream',
            array(
                array(
                    'methods'             => \WP_REST_Server::READABLE,
                    'callback'            => array($this, 'get_data_stream_cb'),
                    'permission_callback' => array($this, 'check_permission'),
                    'args'                => $this->get_datastream_args(),
                ),
            )
        );

        register_rest_route(
            'htga4/v1',
            '/reports/standard',
            array(
                array(
                    'methods'             => \WP_REST_Server::READABLE,
                    'callback'            => array($this, 'get_standard_report'),
                    'permission_callback' => array($this, 'check_permission'),
                    'args'                => $this->get_standard_report_args(),
                ),
            )
        );

        register_rest_route(
            'htga4/v1',
            '/reports/ecommerce',
            array(
                array(
                    'methods'             => \WP_REST_Server::READABLE,
                    'callback'            => array($this, 'get_ecommerce_report'),
                    'permission_callback' => array($this, 'check_permission'),
                    'args'                => $this->get_ecommerce_report_args(),
                ),
            )
        );

        register_rest_route(
            'htga4/v1',
            '/reports/realtime',
            array(
                array(
                    'methods'             => \WP_REST_Server::READABLE,
                    'callback'            => array($this, 'get_realtime_report'),
                    'permission_callback' => array($this, 'check_permission'),
                    'args'                => $this->get_realtime_report_args(),
                ),
            )
        );

        register_rest_route(
            'htga4/v1',
            '/accounts',
            array(
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => array($this, 'get_accounts_cb'),
                'permission_callback' => array($this, 'check_permission'),
            )
        );

        register_rest_route(
            'htga4/v1',
            '/properties/(?P<account_id>[\w-]+)',
            array(
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => array($this, 'get_properties_cb'),
                'permission_callback' => array($this, 'check_permission'),
                'args'                => array(
                    'account_id' => array(
                        'required'          => true,
                        'type'              => 'string',
                        'description'       => __('GA4 Account ID', 'ht-easy-ga4'),
                    ),
                ),
                'force_refresh' => array(
                    'required'          => false,
                    'type'              => 'integer',
                    'description'       => __('Force refresh data by clearing transient cache', 'ht-easy-ga4'),
                    'validate_callback' => function($param) {
                        return empty($param) || is_numeric($param);
                    }
                )
            )
        );

        register_rest_route(
            'htga4/v1',
            '/datastreams/(?P<property_id>[\w-]+)',
            array(
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => array($this, 'get_datastreams_cb'),
                'permission_callback' => array($this, 'check_permission'),
                'args'                => array(
                    'property_id' => array(
                        'required'          => true,
                        'type'              => 'string',
                        'description'       => __('GA4 Property ID', 'ht-easy-ga4'),
                    ),
                ),
                'force_refresh' => array(
                    'required'          => false,
                    'type'              => 'integer',
                    'description'       => __('Force refresh data by clearing transient cache', 'ht-easy-ga4'),
                    'validate_callback' => function($param) {
                        return empty($param) || is_numeric($param);
                    }
                )
            )
        );
    }

    /**
     * Get user information from Google API
     * 
     * @param WP_REST_Request $request The REST API request
     * @return WP_REST_Response The response with user information
     */
    public function get_userinfo($request) {
        // Get access token from API
        $access_token = GA4_API_Service::get_instance()->get_access_token();
            
        if (empty($access_token)) {
            return new \WP_REST_Response(array(
                'error' => array(
                    'message' => __('Failed to retrieve access token. Please check your authentication settings.', 'ht-easy-ga4'),
                    'code'    => 401,
                )
            ), 401);
        }
        
        // Check if we should use transient cache
        $force_refresh = $request->get_param('force_refresh');
        $disable_cache = htga4_disable_transient_cache() || !empty($force_refresh);
        
        // Check transient cache if not disabled
        $transient_key = 'htga4_userinfo';
        if (!$disable_cache) {
            $cached_data = get_transient($transient_key);
            
            if ($cached_data) {
                return new \WP_REST_Response(json_decode($cached_data, true));
            }
        }
        
        // Make request to Google API
        $request_url = $this->get_google_api_url('userinfo');
        $request_args = array(
            'timeout'  => 20,
            'headers'  => array(
                'Authorization' => 'Bearer ' . $access_token,
                'Content-Type' => 'application/json',
            ),
            'sslverify' => false,
        );
        
        $response = wp_remote_get($request_url, $request_args);
        
        // Handle errors
        if (is_wp_error($response)) {
            return new \WP_REST_Response(array(
                'error' => array(
                    'message' => $response->get_error_message(),
                    'code'    => 500,
                )
            ), 500);
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        
        // Cache successful responses
        if ($response_code === 200) {
            set_transient($transient_key, $response_body, HOUR_IN_SECONDS);
        }
        
        return new \WP_REST_Response(json_decode($response_body, true), $response_code);
    }
    
    /**
     * Check if user has permission
     */
    /**
     * Get Google API URL
     * 
     * @param string $endpoint The API endpoint key
     * @return string The full API URL
     */
    private function get_google_api_url($endpoint) {
        return isset($this->google_api_urls[$endpoint]) ? $this->google_api_urls[$endpoint] : '';
    }
    
    /**
     * Get data stream information from Google Analytics API
     * 
     * @param WP_REST_Request $request The REST API request
     * @return WP_REST_Response The response with data stream information
     */
    public function get_data_stream_cb($request) {
        // Get parameters from request
        $access_token = GA4_API_Service::get_instance()->get_access_token();
        $property_id = $request->get_param('property_id');
        $stream_id = $request->get_param('stream_id');
        $force_refresh = $request->get_param('force_refresh');
        
        // Validate required parameters
        if (empty($access_token) || empty($property_id) || empty($stream_id)) {
            return new \WP_REST_Response(array(
                'error' => array(
                    'message' => __('The request does not have proper data!', 'ht-easy-ga4'),
                    'code'    => 400,
                )
            ), 400);
        }
        
        // Check if we should use transient cache
        $disable_cache = htga4_disable_transient_cache() || !empty($force_refresh);
        
        // Check transient cache if not disabled
        $transient_key = 'htga4_data_stream_' . $stream_id;
        if (!$disable_cache) {
            $cached_data = get_transient($transient_key);
            
            if ($cached_data) {
                return new \WP_REST_Response(json_decode($cached_data, true));
            }
        }
        
        // Make request to Google Analytics API
        $request_url = "https://analyticsadmin.googleapis.com/v1beta/properties/{$property_id}/dataStreams/{$stream_id}";
        $request_args = array(
            'timeout'  => 20,
            'headers'  => array(
                'Authorization' => 'Bearer ' . $access_token,
                'Content-Type' => 'application/json',
            ),
            'sslverify' => false,
        );
        
        $response = wp_remote_get($request_url, $request_args);
        
        // Handle errors
        if (is_wp_error($response)) {
            return new \WP_REST_Response(array(
                'error' => array(
                    'message' => $response->get_error_message(),
                    'code'    => 500,
                )
            ), 500);
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        
        // Cache successful responses
        if ($response_code === 200) {
            set_transient($transient_key, $response_body, HOUR_IN_SECONDS);
        }
        
        return new \WP_REST_Response(json_decode($response_body, true), $response_code);
    }
    
    /**
     * Check if user has permission
     */
    public function check_permission($request) {
        // First check if user has capability
        if (!current_user_can('manage_options')) {
            return new \WP_Error(
                'rest_forbidden',
                esc_html__('You do not have permissions to manage reports.', 'ht-easy-ga4'),
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
     * Prepare date ranges for the report
     * 
     * @param string $date_from The start date in Y-m-d format
     * @param string $date_to The end date in Y-m-d format
     * @return array The date ranges for GA4 API
     */
    public function prepare_date_ranges($date_from, $date_to) {
        // Calculate the period length in days
        $period_length = $this->get_diff_days($date_from, $date_to) + 1; // +1 to include both start and end dates
        
        // Calculate previous period dates
        $prev_end_date = date('Y-m-d', strtotime($date_from . ' -1 day'));
        $prev_start_date = date('Y-m-d', strtotime($prev_end_date . ' -' . ($period_length - 1) . ' days'));
        
        // Convert all dates to GA4 format using the new function
        $current_start_date = $this->convert_to_ga4_date($date_from);
        $current_end_date = $this->convert_to_ga4_date($date_to);
        $previous_start_date = $this->convert_to_ga4_date($prev_start_date);
        $previous_end_date = $this->convert_to_ga4_date($prev_end_date);
        
        return array(
            array(
                'startDate' => $current_start_date,
                'endDate' => $current_end_date,
                'name' => 'current'
            ),
            array(
                'startDate' => $previous_start_date,
                'endDate' => $previous_end_date,
                'name' => 'previous'
            )
        );
    }

    /**
     * Convert a date to GA4 API date format
     * 
     * @param string $date The date in Y-m-d format
     * @return string The date in GA4 API format (today, yesterday, NdaysAgo)
     */
    public function convert_to_ga4_date($date) {
        // Get today's date
        $today = date('Y-m-d');
        
        // Calculate days difference
        $date_timestamp = strtotime($date);
        $today_timestamp = strtotime($today);
        $days_diff = (int)floor(($today_timestamp - $date_timestamp) / (60 * 60 * 24));
        
        // Format according to GA4 API requirements
        if ($days_diff === 0) {
            return 'today';
        } elseif ($days_diff === 1) {
            return 'yesterday';
        } elseif ($days_diff > 1) {
            return $days_diff . 'daysAgo';
        } else {
            // For future dates, use absolute value with daysAgo format
            // This is a workaround since GA4 API doesn't support future dates in relative format
            return abs($days_diff) . 'daysAgo';
        }
    }

    /**
     * Get difference in days between two dates
     * 
     * @param string $date_from The start date in Y-m-d format
     * @param string $date_to The end date in Y-m-d format
     * @return int The difference in days
     */
    public function get_diff_days($date_from, $date_to) {
        $date_from_timestamp = strtotime($date_from);
        $date_to_timestamp = strtotime($date_to);
        
        return floor(($date_to_timestamp - $date_from_timestamp) / (60 * 60 * 24));
    }

    /**
     * Get standard report
     *
     * @param WP_REST_Request $request The REST API request.
     * @return WP_REST_Response The response.
     */
    public function get_standard_report($request) {
        $date_from = $request->get_param('date_from');
        $date_to = $request->get_param('date_to');

        // Create cache key based on date range
        $cache_key = 'htga4_standard_report_' . $date_from . '_' . $date_to;

        // Check if force refresh is requested
        $force_refresh = $request->get_param('force_refresh');

        // Check if we should use transient cache
        $disable_cache = htga4_disable_transient_cache() || !empty($force_refresh);

        if ($disable_cache) {
            delete_transient($cache_key);
        }

        // Try to get cached data
        $cached_data = get_transient($cache_key);
        if (false !== $cached_data && !$disable_cache) {
            // Add source information to the response
            if (is_array($cached_data)) {
                $cached_data['_meta'] = [
                    'source' => 'cache',
                    'cache_key' => $cache_key
                ];
            }
            return rest_ensure_response($cached_data);
        }

        // Prepare date ranges
        $date_ranges = $this->prepare_date_ranges($date_from, $date_to);

        // If not cached or force refresh requested, process the data
        $result = Standard_Reports::instance()->get_reports_data($date_ranges);

        // Add source information to the response
        if (is_array($result)) {
            $result['_meta'] = [
                'source' => 'api',
                'cache_key' => $cache_key
            ];
        }
        
        // Set cache for 1 hour if caching is not disabled
        // Store the result without the _meta information in the cache
        $cache_result = $result;
        if (is_array($cache_result) && isset($cache_result['_meta'])) {
            // Create a copy for caching without the _meta field
            $cache_result = $result;
            unset($cache_result['_meta']);
        }
        
        if (!$disable_cache) {
            set_transient($cache_key, $cache_result, HOUR_IN_SECONDS);
        }

        return rest_ensure_response($result);
    }

    /**
     * Get ecommerce report
     *
     * @param WP_REST_Request $request The REST API request.
     * @return WP_REST_Response The response.
     */
    public function get_ecommerce_report($request) {
        $date_from = $request->get_param('date_from');
        $date_to = $request->get_param('date_to');

        // Create cache key based on date range
        $cache_key = 'htga4_ecommerce_report_' . $date_from . '_' . $date_to;

        // Check if force refresh is requested
        $force_refresh = $request->get_param('force_refresh');
        
        // Check if we should use transient cache
        $disable_cache = htga4_disable_transient_cache() || !empty($force_refresh);

        if ($disable_cache) {
            delete_transient($cache_key);
        }

        // Try to get cached data
        $cached_data = get_transient($cache_key);
        if (false !== $cached_data && !$disable_cache) {
            // Add source information to the response
            if (is_array($cached_data)) {
                $cached_data['_meta'] = [
                    'source' => 'cache',
                    'cache_key' => $cache_key
                ];
            }
            return rest_ensure_response($cached_data);
        }

        // Prepare date ranges
        $date_ranges = $this->prepare_date_ranges($date_from, $date_to);

        // If not cached or force refresh requested, process the data
        $result = Ecommerce_Reports::instance()->get_reports_data($date_ranges);

        // Add source information to the response
        if (is_array($result)) {
            $result['_meta'] = [
                'source' => 'api',
                'cache_key' => $cache_key
            ];
        }
        
        // Set cache for 1 hour if caching is not disabled
        $cache_result = $result;
        if (is_array($cache_result) && isset($cache_result['_meta'])) {
            // Create a copy for caching without the _meta field
            $cache_result = $result;
            unset($cache_result['_meta']);
        }
        
        if (!$disable_cache) {
            set_transient($cache_key, $cache_result, HOUR_IN_SECONDS);
        }

        return rest_ensure_response($result);
    }

    /**
     * Get access token
     *
     * @param WP_REST_Request $request The REST API request.
     * @return WP_REST_Response The response.
     */
    public function get_access_token_cb( $request ) {
        // Get email from request
        $email = $request->get_param('email');
        
        if (!is_email($email)) {
            return new \WP_REST_Response([
                'success' => false,
                'message' => __('The provided authentication identifier is not valid.', 'ht-easy-ga4')
            ], 400);
        }
        
        // Check if we have a cached token
        $transient_key = 'htga4_access_token';
        $cached_token = get_transient($transient_key);
        
        if ($cached_token) {
            return new \WP_REST_Response([
                'success' => true,
                'access_token' => $cached_token,
                'source' => 'cache'
            ], 200);
        }
        
        // No cached token, fetch from API
        $api_key = get_option('htga4_sr_api_key');
        
        $request_url = htga4_get_access_token_url();
        $response = wp_remote_post($request_url, [
            'timeout' => 10,
            'body' => [
                'email' => sanitize_email($email),
                'key'   => $api_key
            ],
            'sslverify' => false,
            'headers' => [
                'Accept' => 'application/json'
            ]
        ]);
        
        if (is_wp_error($response)) {
            return new \WP_REST_Response([
                'success' => false,
                'message' => sprintf(
                    __('Failed to connect to authentication server: %s', 'ht-easy-ga4'),
                    $response->get_error_message()
                )
            ], 400);
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        $response_data = json_decode($response_body, true);
        
        if ($response_code !== 200 || !isset($response_data['access_token'])) {
            $error_message = isset($response_data['message']) ? $response_data['message'] : __('Unknown error occurred while fetching access token.', 'ht-easy-ga4');
            return new \WP_REST_Response([
                'success' => false,
                'message' => $error_message
            ], $response_code);
        }
        
        // Cache the token for 58 minutes (slightly less than 1 hour to be safe)
        $access_token = $response_data['access_token'];
        set_transient($transient_key, $access_token, 58 * MINUTE_IN_SECONDS);
        
        // Return the access token
        return new \WP_REST_Response([
            'success' => true,
            'access_token' => $access_token,
            'source' => 'api'
        ], 200);
    }
    
    /**
     * Get realtime report
     *
     * @param WP_REST_Request $request The REST API request.
     * @return WP_REST_Response The response.
     */
    public function get_realtime_report($request) {
        // Create cache key for realtime report
        $cache_key = 'htga4_realtime_report';

        // Check if force refresh is requested
        $force_refresh = $request->get_param('force_refresh');
        
        // Check if we should use transient cache
        $disable_cache = htga4_disable_transient_cache() || !empty($force_refresh);

        if ($disable_cache) {
            delete_transient($cache_key);
        }

        // Try to get cached data - use a shorter cache time for realtime data
        $cached_data = get_transient($cache_key);
        if (false !== $cached_data && !$disable_cache) {
            // Add source information to the response
            if (is_array($cached_data)) {
                $cached_data['_meta'] = [
                    'source' => 'cache',
                    'cache_key' => $cache_key
                ];
            }
            return rest_ensure_response($cached_data);
        }

        try {
            // If not cached or force refresh requested, process the data
            $result = Realtime_Reports::instance()->get_reports_data();
            
            // Add source information to the response
            if (is_array($result)) {
                $result['_meta'] = [
                    'source' => 'api',
                    'cache_key' => $cache_key
                ];
            }
            
            // Set cache for 1 minute if caching is not disabled (shorter time for realtime data)
            // Store the result without the _meta information in the cache
            $cache_result = $result;
            if (is_array($cache_result) && isset($cache_result['_meta'])) {
                // Create a copy for caching without the _meta field
                $cache_result = $result;
                unset($cache_result['_meta']);
            }
            
            if (!$disable_cache) {
                set_transient($cache_key, $cache_result, MINUTE_IN_SECONDS);
            }
            
            return rest_ensure_response($result);
        } catch (\Exception $e) {
            error_log('GA4 Realtime Exception: ' . $e->getMessage());
            return rest_ensure_response([
                'error' => [
                    'message' => $e->getMessage(),
                    'code' => 500
                ]
            ]);
        }
    }

    /**
     * Get GA4 accounts through the WordPress backend
     * 
     * @param WP_REST_Request $request The REST API request
     * @return WP_REST_Response
     */
    public function get_accounts_cb($request){
        $access_token = GA4_API_Service::get_instance()->get_access_token();

        if (!$access_token) {
            return rest_ensure_response([
                'error' => [
                    'message' => __('Access token is missing!', 'ht-easy-ga4'),
                    'code'    => 400,
                ]
            ]);
        }
        
        // Check if force refresh is requested
        $force_refresh = $request->get_param('force_refresh');
        
        // Create cache key
        $cache_key = 'htga4_accounts_v3';
        
        if (!empty($force_refresh)) {
            delete_transient($cache_key);
        }
        
        // Try to get cached data
        $cached_data = get_transient($cache_key);
        if (false !== $cached_data && empty($force_refresh)) {
            return rest_ensure_response($cached_data);
        }
        
        // If not cached or force refresh requested, fetch from API
        $request_url = 'https://analyticsadmin.googleapis.com/v1beta/accounts';
        $request_args = [
            'timeout'   => 20,
            'headers'   => [
                'Authorization' => 'Bearer ' . $access_token,
                'Content-Type' => 'application/json',
            ],
            'sslverify' => false,
        ];
        
        $response = wp_remote_get($request_url, $request_args);
        
        if (is_wp_error($response)) {
            return rest_ensure_response([
                'error' => [
                    'message' => $response->get_error_message(),
                    'code'    => $response->get_error_code(),
                ]
            ]);
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        $response_data = json_decode($response_body, true);
        
        if ($response_code !== 200) {
            $error_message = isset($response_data['error']['message']) 
                ? $response_data['error']['message'] 
                : __('Unknown error occurred while fetching accounts.', 'ht-easy-ga4');
                
            return rest_ensure_response([
                'error' => [
                    'message' => $error_message,
                    'code'    => $response_code,
                ]
            ]);
        }
        
        // Cache the response for 58 minutes (slightly less than 1 hour to be safe)
        if( !htga4_disable_transient_cache() ){
            set_transient($cache_key, $response_data, 58 * MINUTE_IN_SECONDS);
        }
        
        return rest_ensure_response($response_data);
    }

    /**
     * Get properties for an account through the WordPress backend
     * 
     * @param WP_REST_Request $request The REST API request
     * @return WP_REST_Response
     */
    public function get_properties_cb($request){
        $account_id = $request->get_param('account_id');
        $access_token = GA4_API_Service::get_instance()->get_access_token();

        // Check if force refresh is requested
        $force_refresh = $request->get_param('force_refresh');

        // Create cache key
        $cache_key = 'htga4_properties_v3';
        
        if (!empty($force_refresh)) {
            delete_transient($cache_key);
        }
        
        // Try to get cached data
        $cached_data = get_transient($cache_key);
        if (false !== $cached_data && empty($force_refresh)) {
            return rest_ensure_response($cached_data);
        }

        if (!$access_token) {
            return rest_ensure_response([
                'error' => [
                    'message' => __('Access token is missing!', 'ht-easy-ga4'),
                    'code'    => 400,
                ]
            ]);
        }

        // Cache
        $cache_key = 'htga4_properties_v3';
        $transient_data = get_transient($cache_key);

        if( $transient_data && !wp_doing_ajax() ){
            return $transient_data;
        }


        $request_url = "https://analyticsadmin.googleapis.com/v1alpha/properties?filter=parent:accounts/{$account_id}";
        $request_args = [
            'timeout'   => 20,
            'headers'   => [
                'Authorization' => 'Bearer ' . $access_token,
                'Content-Type' => 'application/json',
            ],
            'sslverify' => false,
        ];

        $response = wp_remote_get($request_url, $request_args);

        if (is_wp_error($response)) {
            return rest_ensure_response([
                'error' => [
                    'message' => $response->get_error_message(),
                    'code'    => $response->get_error_code(),
                ]
            ]);
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        $response_data = json_decode($response_body, true);

        if ($response_code !== 200) {
            $error_message = isset($response_data['error']['message']) 
                ? $response_data['error']['message'] 
                : __('Unknown error occurred while fetching properties.', 'ht-easy-ga4');
                
            return rest_ensure_response([
                'error' => [
                    'message' => $error_message,
                    'code'    => $response_code,
                ]
            ]);
        }

        if( !htga4_disable_transient_cache() ){
            set_transient($cache_key, $response_data, (MINUTE_IN_SECONDS * 58));
        }

        return rest_ensure_response($response_data);
    }

    /**
     * Get datastreams from Google API
     * 
     * @param WP_REST_Request $request The REST API request
     * @return WP_REST_Response The response with datastreams
     */
    public function get_datastreams_cb($request){
        $property_id = $request->get_param('property_id');

        $access_token = GA4_API_Service::get_instance()->get_access_token();

        if (!$access_token) {
            return rest_ensure_response([
                'error' => [
                    'message' => __('Access token is missing!', 'ht-easy-ga4'),
                    'code'    => 400,
                ]
            ]);
        }

        // Check if force refresh is requested
        $force_refresh = $request->get_param('force_refresh');

        // Create cache key
        $cache_key = 'htga4_datastreams_v3';
        
        if (!empty($force_refresh)) {
            delete_transient($cache_key);
        }
        
        // Try to get cached data
        $cached_data = get_transient($cache_key);
        if (false !== $cached_data && empty($force_refresh)) {
            return rest_ensure_response($cached_data);
        }

        $request_url = 'https://analyticsadmin.googleapis.com/v1beta/properties/' . $property_id . '/dataStreams';
        $request_args = [
            'timeout'   => 20,
            'headers'   => [
                'Authorization' => 'Bearer ' . $access_token,
                'Content-Type' => 'application/json',
            ],
            'sslverify' => false,
        ];

        $response = wp_remote_get($request_url, $request_args);

        if (is_wp_error($response)) {
            return rest_ensure_response([
                'error' => [
                    'message' => $response->get_error_message(),
                    'code'    => $response->get_error_code(),
                ]
            ]);
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        $response_data = json_decode($response_body, true);

        if ($response_code !== 200) {
            $error_message = isset($response_data['error']['message']) 
                ? $response_data['error']['message'] 
                : __('Unknown error occurred while fetching datastreams.', 'ht-easy-ga4');
                
            return rest_ensure_response([
                'error' => [
                    'message' => $error_message,
                    'code'    => $response_code,
                ]
            ]);
        }

        if( !htga4_disable_transient_cache() ){
            set_transient($cache_key, $response_data, (MINUTE_IN_SECONDS * 58));
        }

        return rest_ensure_response($response_data);
    }
}
