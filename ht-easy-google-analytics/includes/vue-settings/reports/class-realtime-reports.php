<?php
/**
 * Realtime Reports Class
 *
 * Responsibilities:
 * - Fetches and processes GA4 realtime reports
 * - Handles realtime-specific API requests
 * - Transforms raw realtime data into frontend-friendly format
 * - Provides current active users and engagement metrics
 */
namespace Ht_Easy_Ga4\Vue_Settings;

/**
 * Realtime Reports
 *
 * Handles fetching, processing, and formatting of GA4 realtime analytics data
 */
class Realtime_Reports {
    /**
     * Singleton instance
     *
     * @var Realtime_Reports
     */
    private static $instance;

    /**
     * Data transformer instance
     *
     * @var Data_Transformer
     */
    private $data_transformer;
    
    /**
     * GA4 API Service instance
     *
     * @var GA4_API_Service
     */
    private $api_service;
    
    /**
     * GA4 Realtime API URL
     *
     * @var string
     */
    private $realtime_api_url = 'https://analyticsdata.googleapis.com/v1beta/properties/{property_id}:runRealtimeReport';

    /**
     * Class constructor
     * 
     * Initializes the Realtime_Reports instance with API service
     */
    private function __construct() {
        $this->api_service = GA4_API_Service::get_instance();
        $this->data_transformer = Data_Transformer::get_instance();
    }

    /**
     * Get singleton instance
     * 
     * @return Realtime_Reports The singleton instance of this class
     */
    public static function instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Get GA4 realtime requests configuration
     * 
     * @return array GA4 API request configurations for realtime reports
     */
    public function get_ga4_requests() {
        // Ensure all requests follow the correct format for the GA4 Realtime API
        $requests = array(
            'active_users' => array(
                'metrics' => array(
                    array(
                        'name' => 'activeUsers'
                    )
                ),
                // Realtime API requires at least one dimension
                'dimensions' => array(
                    array(
                        'name' => 'unifiedScreenName'
                    )
                ),
                // Add minuteRanges to specify time period
                'minuteRanges' => array(
                    array(
                        'startMinutesAgo' => 29,
                        'endMinutesAgo' => 0
                    )
                )
            ),
            'page_views' => array(
                'metrics' => array(
                    array(
                        'name' => 'screenPageViews'
                    )
                ),
                'dimensions' => array(
                    array(
                        'name' => 'minutesAgo'
                    )
                ),
                'orderBys' => array(
                    array(
                        'dimension' => array(
                            'orderType' => 'NUMERIC',
                            'dimensionName' => 'minutesAgo'
                        )
                    )
                ),
                'minuteRanges' => array(
                    array(
                        'startMinutesAgo' => 29,
                        'endMinutesAgo' => 0
                    )
                )
            ),
            'top_pages' => array(
                'metrics' => array(
                    array(
                        'name' => 'screenPageViews'
                    )
                ),
                'dimensions' => array(
                    array(
                        'name' => 'unifiedScreenName' // Using valid dimension for realtime API
                    )
                ),
                'orderBys' => array(
                    array(
                        'metric' => array(
                            'metricName' => 'screenPageViews'
                        ),
                        'desc' => true
                    )
                ),
                'limit' => 10,
                'minuteRanges' => array(
                    array(
                        'startMinutesAgo' => 29,
                        'endMinutesAgo' => 0
                    )
                )
            ),
            'top_events' => array(
                'dimensions' => [
                    'name' => 'eventName'
                ],
                'metrics' => [
                    'name' => 'eventCount'
                ],
                'limit' => 10,
                'minuteRanges' => array(
                    array(
                        'startMinutesAgo' => 29,
                        'endMinutesAgo' => 0
                    )
                )
            ),
            'country' => array(
                'metrics' => array(
                    array(
                        'name' => 'activeUsers'
                    )
                ),
                'dimensions' => array(
                    array(
                        'name' => 'country'
                    )
                ),
                'orderBys' => array(
                    array(
                        'metric' => array(
                            'metricName' => 'activeUsers'
                        ),
                        'desc' => true
                    )
                ),
                'limit' => 10,
                'minuteRanges' => array(
                    array(
                        'startMinutesAgo' => 29,
                        'endMinutesAgo' => 0
                    )
                )
            )
        );

        return $requests;
    }

    /**
     * Fetch realtime reports from GA4 API
     * 
     * @return array Complete realtime dashboard data or error information
     */
    public function get_reports_data() {
        // Check if we have necessary data
        if (!$this->api_service->has_valid_credentials()) {
            return [
                'error' => [
                    'message' => __('Missing property ID or access token', 'ht-easy-ga4'),
                    'code' => 400
                ]
            ];
        }

        $results = [
            'active_users' => 0,
            'page_views' => [],
            'top_pages' => [],
            'top_events' => [],
            'countries' => [],
        ];

        try {
            $requests = $this->get_ga4_requests();
            $property_id = $this->api_service->get_property_id();
            
            // Process each request individually since realtime API doesn't support batching
            foreach ($requests as $report_type => $request_params) {
                $response = $this->execute_realtime_request($property_id, $request_params);
                
                if (isset($response['error'])) {
                    return $response; // Return error if any request fails
                }
                
                // Process successful response
                $this->process_realtime_response($response, $report_type, $results);
            }

            return $results;
        } catch (\Exception $e) {
            error_log('Realtime_Reports fetch_reports - Exception: ' . $e->getMessage());
            return [
                'error' => [
                    'message' => $e->getMessage(),
                    'code' => 500
                ]
            ];
        }
    }

    /**
     * Execute a realtime request to GA4 API
     * 
     * @param string $property_id The GA4 property ID
     * @param array $request_params The request parameters
     * @return array API response data or error information
     */
    private function execute_realtime_request($property_id, $request_params) {
        $request_url = str_replace('{property_id}', $property_id, $this->realtime_api_url);
        $access_token = $this->api_service->get_access_token();
        
        $request_body = wp_json_encode($request_params);
        
        $response_raw = wp_remote_post($request_url, [
            'method' => 'POST',
            'timeout' => 15,
            'sslverify' => false,
            'headers' => [
                'Authorization' => 'Bearer ' . $access_token,
                'Content-Type' => 'application/json',
            ],
            'body' => $request_body
        ]);
        
        if (is_wp_error($response_raw)) {
            return [
                'error' => [
                    'message' => $response_raw->get_error_message(),
                    'code' => $response_raw->get_error_code()
                ]
            ];
        }

        $response_code = wp_remote_retrieve_response_code($response_raw);
        $response_message = wp_remote_retrieve_response_message($response_raw);
        
        if ($response_code == 200) {
            $response_body = wp_remote_retrieve_body($response_raw);
            return json_decode($response_body, true);
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
     * Process realtime response and fill results array
     * 
     * @param array $response The API response containing report data
     * @param string $report_type The type of report
     * @param array &$results Reference to results array to populate with formatted data
     */
    private function process_realtime_response($response, $report_type, &$results) {
        if (!isset($response['rows']) && $report_type !== 'active_users') {
            // No data available for this report type
            return;
        }

        switch ($report_type) {
            case 'active_users':
                // Active users is a simple count
                $results['active_users'] = isset($response['rows']) && isset($response['rows'][0]['metricValues'][0]['value']) 
                    ? (int)$response['rows'][0]['metricValues'][0]['value'] 
                    : 0;
                break;
                
            case 'page_views':
                $results['page_views'] = $this->prepare_time_series_data($response);
                break;
                
            case 'top_pages':
                $results['top_pages'] = $this->prepare_dimension_list($response, 'page');
                break;

            case 'top_events':
                $results['top_events'] = $this->prepare_dimension_list($response, 'event');
                break;
                
            case 'user_sources':
                $results['user_sources'] = $this->prepare_dimension_list($response, 'source');
                break;
                
            case 'device_category':
                $results['device_category'] = $this->prepare_dimension_list($response, 'device');
                break;
                
            case 'country':
                $results['countries'] = $this->prepare_dimension_list($response, 'country');
                break;
        }
    }

    /**
     * Prepare time series data from realtime response
     *
     * @param array $response_data The API response containing time-based metrics
     * @return array Formatted time series data
     */
    private function prepare_time_series_data($response_data) {
        $dataset = [];
        $rows = !empty($response_data['rows']) ? $response_data['rows'] : [];

        foreach ($rows as $row) {
            $minutes_ago = (int)$row['dimensionValues'][0]['value'];
            $value = (int)$row['metricValues'][0]['value'];
            
            $dataset[] = [
                'minutes_ago' => $minutes_ago,
                'value' => $value
            ];
        }

        // Sort by minutes_ago ascending
        usort($dataset, function($a, $b) {
            return $a['minutes_ago'] - $b['minutes_ago'];
        });

        return $dataset;
    }

    /**
     * Prepare dimension list from realtime response
     *
     * @param array $response_data The API response containing dimension data
     * @param string $dimension_key The key to use for the dimension in the result
     * @return array Formatted dimension list
     */
    private function prepare_dimension_list($response_data, $dimension_key) {
        $dataset = [];
        $rows = !empty($response_data['rows']) ? $response_data['rows'] : [];

        foreach ($rows as $row) {
            $dimension_value = $row['dimensionValues'][0]['value'];
            $metric_value = (int)$row['metricValues'][0]['value'];
            
            $dataset[] = [
                $dimension_key => $dimension_value,
                'value' => $metric_value
            ];
        }

        return $dataset;
    }
}
