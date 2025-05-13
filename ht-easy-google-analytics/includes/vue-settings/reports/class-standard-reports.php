<?php
/**
 * Standard Reports Class
 *
 * Responsibilities:
 * - Fetches and processes standard GA4 reports
 * - Manages API requests and authentication
 * - Transforms raw data into dashboard-ready format
 */
namespace Ht_Easy_Ga4\Vue_Settings;

/**
 * Standard Reports
 *
 * Handles fetching, processing, and formatting of GA4 analytics data
 */
class Standard_Reports {
    /**
     * Singleton instance
     *
     * @var Standard_Reports
     */
    private static $instance;

    private $data_transformer;
    
    /**
     * GA4 API Service instance
     *
     * @var GA4_API_Service
     */
    private $api_service;

    /**
     * Class constructor
     * 
     * Initializes the Standard_Reports instance with API service
     */
    private function __construct() {
        $this->api_service = GA4_API_Service::get_instance();
        $this->data_transformer = Data_Transformer::get_instance();
    }

    /**
     * Get singleton instance
     * 
     * @return Standard_Reports The singleton instance of this class
     */
    public static function instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Get GA4 requests configuration
     * 
     * @param array $date_ranges Date ranges for GA4 API in required format
     * @return array GA4 API request configurations for all report types
     */
    public function get_ga4_requests($date_ranges) {
        $requests = array(
            'session' => array(
                'dateRanges' => $date_ranges,
                'metrics' => array(
                    array(
                        'name' => 'sessions'
                    ),
                ),
                'dimensions' => array(
                    array(
                        'name' => 'date'
                    ),
                ),
                'orderBys' => array(
                    'dimension' => array(
                        'orderType' => 'ALPHANUMERIC',
                        'dimensionName' => 'date'
                    )
                )
            ),
            'pageView' => array(
                'dateRanges' => $date_ranges,
                'metrics' => array(
                    array(
                        'name' => 'screenPageViews'
                    ),
                ),
                'dimensions' => array(
                    array(
                        'name' => 'date'
                    ),
                ),
                'orderBys' => array(
                    'dimension' => array(
                        'orderType' => 'ALPHANUMERIC',
                        'dimensionName' => 'date'
                    )
                )
            ),
            'bounceRate' => array(
                'dateRanges' => $date_ranges,
                'metrics' => array(
                    array(
                        'name' => 'bounceRate'
                    ),
                ),
                'dimensions' => array(
                    array(
                        'name' => 'date'
                    ),
                ),
                'orderBys' => array(
                    'dimension' => array(
                        'orderType' => 'ALPHANUMERIC',
                        'dimensionName' => 'date'
                    )
                )
            ),
            'pagePath' => array(
                'dateRanges' => [$date_ranges[0]],
                'metrics' => array(
                    array(
                        'name' => 'screenPageViews'
                    ),
                ),
                'dimensions' => array(
                    array(
                        'name' => 'pagePath'
                    ),
                ),
                'limit' => 10
            ),
            'referrer' => array(
                'dateRanges' => [$date_ranges[0]],
                'metrics' => array(
                    array(
                        'name' => 'sessions'
                    ),
                ),
                'dimensions' => array(
                    array(
                        'name' => 'firstUserSource'
                    ),
                ),
                'limit' => 10
            ),
            'countries' => array(
                'dateRanges' => [$date_ranges[0]],
                'metrics' => array(
                    array(
                        'name' => 'sessions'
                    ),
                ),
                'dimensions' => array(
                    array(
                        'name' => 'country'
                    ),
                ),
                'limit' => 10
            ),
            'userTypes' => array(
                'dateRanges' => [$date_ranges[0]],
                'metrics' => array(
                    array(
                        'name' => 'activeUsers'
                    ),
                ),
                'dimensions' => array(
                    array(
                        'name' => 'newVsReturning'
                    ),
                )
            ),
            'deviceTypes' => array(
                'dateRanges' => [$date_ranges[0]],
                'metrics' => array(
                    array(
                        'name' => 'activeUsers'
                    ),
                ),
                'dimensions' => array(
                    array(
                        'name' => 'deviceCategory'
                    ),
                )
            ),
        );

        return $requests;
    }

    /**
     * Fetch reports from GA4 API
     * 
     * @param array $ga4_date_ranges Date ranges (current and previous periods) in GA4 API format
     * @return array Complete dashboard data or error information
     */
    public function get_reports_data($ga4_date_ranges) {
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
            'sessions' => [],
            'pageviews' => [],
            'bounceRate' => [],
            'deviceTypes' => [],
            'userTypes' => [],
            'topCountries' => [],
            'topPages' => [],
            'topReferrers' => [],
            'dateRanges' => $ga4_date_ranges
        ];

        try {
            // Create batch requests (max 5 requests per batch)
            $batches = $this->api_service->prepare_batches($this->get_ga4_requests($ga4_date_ranges));
            
            // Process each batch
            foreach ($batches as $batch) {
                $batch_response = $this->api_service->execute_batch_request($batch['requests']);
                
                if (isset($batch_response['error'])) {
                    return $batch_response; // Return error if any batch fails
                }
                
                // Process successful batch response
                $this->process_batch_response($batch_response, $batch['report_types'], $results);
            }

            return $results;
        } catch (\Exception $e) {
            error_log('Standard_Reports fetch_reports - Exception: ' . $e->getMessage());
            return [
                'error' => [
                    'message' => $e->getMessage(),
                    'code' => 500
                ]
            ];
        }
    }



    /**
     * Process batch response and fill results array
     * 
     * @param array $batch_response The API response containing report data
     * @param array $report_types The types of reports included in this batch
     * @param array &$results Reference to results array to populate with formatted data
     */
    private function process_batch_response($batch_response, $report_types, &$results) {
        if (!isset($batch_response['reports']) || !is_array($batch_response['reports'])) {
            return;
        }

        foreach ($batch_response['reports'] as $index => $response_item) {
            $report_type = $report_types[$index];
            
            switch ($report_type) {
                case 'session':
                    $results['sessions'] = $this->data_transformer->prepare_time_series_data($response_item);
                    break;
                case 'pageView':
                    $results['pageviews'] = $this->data_transformer->prepare_time_series_data($response_item);
                    break;
                case 'bounceRate':
                    $results['bounceRate'] = $this->data_transformer->prepare_time_series_data($response_item);
                    break;
                case 'pagePath':
                    $results['topPages'] = $this->data_transformer->prepare_top_items_list($response_item);
                    break;
                case 'referrer':
                    $results['topReferrers'] = $this->data_transformer->prepare_top_items_list($response_item);
                    break;
                case 'countries':
                    $results['topCountries'] = $this->data_transformer->prepare_top_items_list($response_item);
                    break;
                case 'userTypes':
                    $results['userTypes'] = $this->data_transformer->prepare_device_types($response_item);
                    break;
                case 'deviceTypes':
                    $results['deviceTypes'] = $this->data_transformer->prepare_device_types($response_item);
                    break;
            }
        }
    }
}