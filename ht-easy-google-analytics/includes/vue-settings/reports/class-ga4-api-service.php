<?php
/**
 * GA4 API Service Class
 *
 * Responsibilities:
 * - Handles GA4 API communication
 * - Manages authentication and tokens
 * - Processes batch requests
 * - Provides reusable API functionality
 */
namespace Ht_Easy_Ga4\Vue_Settings;

/**
 * GA4 API Service
 *
 * Service class for GA4 API communication and batch processing
 */
class GA4_API_Service {
    /**
     * Singleton instance
     *
     * @var GA4_API_Service
     */
    private static $instance;
    
    /**
     * Access token for GA4 API requests
     *
     * @var string
     */
    private $access_token;

    /**
     * GA4 Property ID
     *
     * @var string
     */
    private $property_id;
    
    /**
     * GA4 Batch API URL
     *
     * @var string
     */
    private $ga4_batch_api_url = 'https://analyticsdata.googleapis.com/v1beta/properties/{property_id}:batchRunReports';

    /**
     * Class constructor
     * 
     * Initializes the GA4_API_Service instance with property ID and access token
     */
    private function __construct() {
        // Initialize properties from options
        $options = get_option('ht_easy_ga4_options', []);
        $this->property_id = isset($options['property']) ? $options['property'] : '';
        $this->access_token = $this->get_access_token();
    }

    /**
     * Get singleton instance
     * 
     * @return GA4_API_Service The singleton instance of this class
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Execute a batch request to GA4 API
     * 
     * @param array $requests The requests to batch together
     * @return array API response data or error information
     */
    public function execute_batch_request($requests) {
        $request_url = str_replace('{property_id}', $this->property_id, $this->ga4_batch_api_url);

        $response_raw = wp_remote_post($request_url, [
            'method' => 'POST',
            'timeout' => 20,
            'sslverify' => false,
            'headers' => [
                'timeout' => 20,
                'Authorization' => 'Bearer ' . $this->access_token,
                'Content-Type' => 'application/json',
            ],
            'body' => wp_json_encode([
                'requests' => array_values($requests)
            ])
        ]);

        if (is_wp_error($response_raw)) {
            return [
                'error' => [
                    'message' => $response_raw->get_error_message(),
                    'code' => $response_raw->get_error_code(),
                ]
            ];
        }

        $response_code = wp_remote_retrieve_response_code($response_raw);
        $response_message = wp_remote_retrieve_response_message($response_raw);

        if ($response_code == 200) {
            return json_decode(wp_remote_retrieve_body($response_raw), true);
        } else {
            $response_body = wp_remote_retrieve_body($response_raw);
            $error_data = json_decode($response_body, true);
            
            $error_message = $response_message;
            
            // Check for specific error patterns and provide clearer messages
            if (!empty($error_data) && isset($error_data['error']['message'])) {
                $error_message = $error_data['error']['message'];
            }
            
            return [
                'error' => [
                    'message' => $error_message,
                    'code' => $response_code
                ]
            ];
        }
    }

    /**
     * Create batch requests for GA4 API (max 5 requests per batch)
     *
     * @param array $requests All GA4 API requests to be organized into batches
     * @return array Grouped batches with max 5 requests each and their report types
     */
    public function prepare_batches($requests) {
        $batches = [];
        $current_batch = [];
        $batch_count = 0;
        $batch_index = 0;
        
        // Group requests into batches of 5 (GA4 API limit)
        foreach ($requests as $report_type => $request_params) {
            // If we've reached 5 requests, start a new batch
            if ($batch_count >= 5) {
                $batches[$batch_index] = [
                    'requests' => $current_batch,
                    'report_types' => array_keys($current_batch)
                ];
                $current_batch = [];
                $batch_count = 0;
                $batch_index++;
            }
            
            // Add request to current batch
            $current_batch[$report_type] = $request_params;
            $batch_count++;
        }
        
        // Add the final batch if it has any requests
        if (!empty($current_batch)) {
            $batches[$batch_index] = [
                'requests' => $current_batch,
                'report_types' => array_keys($current_batch)
            ];
        }
        
        return $batches;
    }

    /**
     * Get access token for GA4 API requests
     *
     * @return string Access token from transient or empty string if not available
     */
    public function get_access_token() {
        $cached_token = get_transient('htga4_access_token');

        if ($cached_token) {
            return $cached_token;
        }

        // Fetch
        $token = $this->fetch_access_token();

        if (is_wp_error($token)) {
            return '';
        }

        set_transient('htga4_access_token', $token, MINUTE_IN_SECONDS * 58);

        return $token;
    }

    /**
     * Generate and cache an access token for the given email address
     *
     * @param string $email Email address for authentication
     * @return string|\WP_Error Access token string on success or WP_Error on failure
     */
    public function fetch_access_token() {
        $email = get_option('htga4_email');

        if (!is_email($email)) {
            return new \WP_Error(
                'invalid_email',
                'Invalid email address provided.'
            );
        }
        
        // No cached token, use the REST API endpoint
        $request_url = htga4_get_api_base_url() . 'v1/get-access-token';

        $response = wp_remote_post($request_url, [
            'timeout' => 10,
            'body' => [
                'email' => sanitize_email($email),
                'key'   => get_option('htga4_sr_api_key')
            ],
            'sslverify' => false,
            'headers' => [
                'Accept' => 'application/json'
            ]
        ]);
        
        if (is_wp_error($response)) {
            return new \WP_Error(
                'request_failed',
                sprintf(
                    'Failed to connect to authentication server: %s',
                    $response->get_error_message()
                )
            );
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        $response_data = json_decode($response_body, true);
        
        if (200 === $response_code && !empty($response_data['success']) && !empty($response_data['access_token'])) {
            // Return just the access token for backward compatibility
            return $response_data['access_token'];
        } else {
            $error_message = !empty($response_data['message']) 
                ? $response_data['message'] 
                : 'Unknown error occurred while fetching access token.';
            
            return new \WP_Error(
                'invalid_response',
                $error_message
            );
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        if (!is_array($body) || empty($body['success']) || empty($body['access_token'])) {
            return new \WP_Error(
                'invalid_response_data',
                isset($body['message']) ? $body['message'] : 'Invalid response data'
            );
        }

        $access_token = sanitize_text_field($body['access_token']);

        return $access_token;
    }

    /**
     * Check if API service has valid credentials
     *
     * @return bool True if property ID and access token are available
     */
    public function has_valid_credentials() {
        return !empty($this->property_id) && !empty($this->access_token);
    }

    /**
     * Get property ID
     *
     * @return string The GA4 property ID
     */
    public function get_property_id() {
        return $this->property_id;
    }
}
