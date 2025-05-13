<?php
/**
 * Ecommerce Reports Class
 *
 * Responsibilities:
 * - Fetches and processes GA4 ecommerce reports
 * - Manages ecommerce API requests and authentication
 * - Transforms raw ecommerce data into frontend-friendly format
 * - Handles ecommerce-specific metrics and dimensions
 */
namespace Ht_Easy_Ga4\Vue_Settings;

/**
 * Ecommerce Reports
 *
 * Handles fetching, processing, and formatting of GA4 ecommerce analytics data
 */
class Ecommerce_Reports {
    /**
     * Singleton instance
     *
     * @var Ecommerce_Reports
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
     * Class constructor
     * 
     * Initializes the Ecommerce_Reports instance with API service
     */
    private function __construct() {
        $this->api_service = GA4_API_Service::get_instance();
        $this->data_transformer = Data_Transformer::get_instance();
    }

    /**
     * Get singleton instance
     * 
     * @return Ecommerce_Reports The singleton instance of this class
     */
    public static function instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Get GA4 ecommerce requests configuration
     * 
     * @param array $date_ranges Date ranges for GA4 API in required format
     * @return array GA4 API request configurations for all ecommerce report types
     */
    public function get_ga4_requests($date_ranges) {
        $requests = array(
            'transactions' => array(
                'dateRanges' => $date_ranges,
                'metrics' => array(
                    array(
                        'name' => 'transactions'
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
            'averagePurchaseRevenue' => array(
                'dateRanges' => $date_ranges,
                'metrics' => array(
                    array(
                        'name' => 'averagePurchaseRevenue'
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
            'purchaseRevenue' => array(
                'dateRanges' => $date_ranges,
                'metrics' => array(
                    array(
                        'name' => 'purchaseRevenue'
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
            'itemsViewed' => array(
                'dateRanges' => $date_ranges,
                'metrics' => array(
                    array(
                        'name' => 'itemsViewed'
                    ),
                ),
            ),
            'itemsAddedToCart' => array(
                'dateRanges' => $date_ranges,
                'metrics' => array(
                    array(
                        'name' => 'addToCarts'
                    ),
                )
            ),
            'itemsCheckedOut' => array(
                'dateRanges' => $date_ranges,
                'metrics' => array(
                    array(
                        'name' => 'itemsCheckedOut'
                    ),
                ),
            ),
            'itemsPurchased' => array(
                'dateRanges' => $date_ranges,
                'metrics' => array(
                    array(
                        'name' => 'itemsPurchased'
                    ),
                ),
            ),
            'topProducts' => array(
                'dateRanges' => [$date_ranges[0]],
                'metrics' => array(
                    array(
                        'name' => 'itemRevenue', // Response will be order by this (first) metric
                    ),
                    array(
                        'name' => 'itemsPurchased',
                    ),
                    array(
                        'name' => 'itemsViewed',
                    ),
                    array(
                        'name' => 'itemsAddedToCart',
                    ),
                    array(
                        'name' => 'cartToViewRate',
                    ),
                    array(
                        'name' => 'purchaseToViewRate',
                    ),
                ),
                'dimensions' => array(
                    array(
                        'name' => 'itemName'
                    ),
                ),
                'limit' => 10,
            ),
            'topBrands' => array(
                'dateRanges' => [$date_ranges[0]],
                'metrics' => array(
                    array(
                        'name' => 'itemRevenue', // Response will be order by this (first) metric
                    ),
                    array(
                        'name' => 'itemsViewed',
                    ),
                    array(
                        'name' => 'itemsPurchased',
                    ),
                ),
                'dimensions' => array(
                    array(
                        'name' => 'itemBrand'
                    ),
                ),
                'limit' => 10
            ),
            'topReferrers' => array(
                'dateRanges' => [$date_ranges[0]],
                'metrics' => array(
                    array(
                        'name' => 'itemRevenue', // Response will be order by this (first) metric
                    ),
                    array(
                        'name' => 'itemsViewed',
                    ),
                    array(
                        'name' => 'itemsPurchased',
                    ),
                ),
                'dimensions' => array(
                    array(
                        'name' => 'firstUserSource'
                    ),
                ),
                'limit' => 10
            ),
        );

        return $requests;
    }

    /**
     * Fetch ecommerce reports from GA4 API
     * 
     * @param array $ga4_date_ranges Date ranges (current and previous periods) in GA4 API format
     * @return array Complete ecommerce dashboard data or error information
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
            'transactions' => [],
            'averagePurchaseRevenue' => [],
            'purchaseRevenue' => [],
            'itemsViewed' => [],
            'itemsAddedToCart' => [],
            'itemsCheckedOut' => [],
            'itemsPurchased' => [],
            'topProducts' => [],
            'topBrands' => [],
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
            error_log('Ecommerce_Reports fetch_reports - Exception: ' . $e->getMessage());
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
                case 'transactions':
                case 'averagePurchaseRevenue':
                case 'purchaseRevenue':
                    $results[$report_type] = $this->data_transformer->prepare_time_series_data($response_item);
                    break;
                case 'itemsViewed':
                case 'itemsAddedToCart':
                case 'itemsCheckedOut':
                case 'itemsPurchased':
                    $results[$report_type] = $this->prepare_simple_data($response_item);
                    break;
                case 'topProducts':
                case 'topBrands':
                case 'topReferrers':
                    $results[$report_type] = $this->prepare_items_table($response_item);
                    break;
            }
        }
    }



    /**
     * Prepare items data from API response
     *
     * @param array $response_data The API response containing items data
     * @return array Formatted items data with current and previous values
     * @throws \Exception If API response contains an error
     */
    private function prepare_simple_data($response_data) {
        if (!empty($response_data['error'])) {
            throw new \Exception(
                esc_html($response_data['error']['message']), 
                esc_html($response_data['error']['code'])
            );
        }

        $dataset = [
            'previous' => 0,
            'current' => 0,
        ];

        $rows = !empty($response_data['rows']) ? $response_data['rows'] : [];

        // Loop through each row
        foreach ($rows as $row) {
            // Check if required array keys exist
            if (!isset($row['dimensionValues']) || !isset($row['metricValues'])) {
                continue; // Skip this row if required keys don't exist
            }
            
            // Safely access the dimension value
            $state = isset($row['dimensionValues'][0]['value']) ? $row['dimensionValues'][0]['value'] : 'current';
            
            // Safely access the metric value
            $metric_value = isset($row['metricValues'][0]['value']) ? floatval($row['metricValues'][0]['value']) : 0;

            if ($state == 'current') {
                $dataset['current'] = $metric_value;
            } elseif ($state == 'previous') {
                $dataset['previous'] = $metric_value;
            }
        }

        return $dataset;
    }

    /**
     * Prepare top items data from API response
     *
     * @param array $response_data The API response containing top items data
     * @return array Formatted items table data with metrics and dimensions
     * @throws \Exception If API response contains an error
     */
    private function prepare_items_table($response_data) {
        if (!empty($response_data['error'])) {
            throw new \Exception(
                esc_html($response_data['error']['message']), 
                esc_html($response_data['error']['code'])
            );
        }

        $dataset = [];
        $rows = !empty($response_data['rows']) ? $response_data['rows'] : [];

        // Loop through each row
        foreach ($rows as $key => $row) {
            $item_data = [];
            
            // Extract metric values if they exist
            if (isset($row['metricValues']) && is_array($row['metricValues'])) {
                foreach ($row['metricValues'] as $mkey => $value) {
                    $item_data[] = isset($value['value']) ? floatval($value['value']) : 0;
                }
            }
            
            // Extract dimension values if they exist
            if (isset($row['dimensionValues']) && is_array($row['dimensionValues'])) {
                foreach ($row['dimensionValues'] as $dkey => $value) {
                    $item_data[] = isset($value['value']) ? $value['value'] : '';
                }
            }
            
            $dataset[$key] = $item_data;
        }

        return $dataset;
    }
}
