<?php
/**
 * Data Transformer Class
 *
 * @package HT_Easy_GA4
 * @since 1.0.0
 *
 * Responsibilities:
 * - Transforms raw API data into frontend-friendly format
 * - Ensures consistent data structure for Vue.js components
 * - Handles data formatting and normalization
 * - Follows state.js data structure requirements
 */
namespace Ht_Easy_Ga4\Vue_Settings;

/**
 * Data Transformer
 */
class Data_Transformer {
    /**
     * @var Data_Transformer
     */
    private static $instance;
    
    /**
     * Constructor
     * 
     * Initializes the Data_Transformer instance
     */
    public function __construct() {
        // Initialize any required properties
    }

    /**
     * Get singleton instance
     * 
     * @return Data_Transformer The singleton instance of this class
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Prepare time series data from API response
     *
     * @param array $response_data The API response data containing time-based metrics
     * @return array Formatted time series data with current and previous datasets
     * @throws \Exception If API response contains an error
     */
    public function prepare_time_series_data($response_data) {
        if( !empty($response_data['error']) ){
            throw new \Exception( esc_html($response_data['error']['message']), esc_html($response_data['error']['code']) );
        }

        $dataset = array(
            'current_dataset'   => array(),
            'previous_dataset'  => array(),
        );

        // Filter out rows with metric values of 0
        $rows = !empty($response_data['rows']) ? $response_data['rows'] : array();
        $rows = array_filter($rows, function($item){
            if($item['metricValues'][0]['value'] != 0){
                return $item;
            }
        });

        // Loop through each rows
        foreach( $rows as $key => $row ){
            $date   =  $row['dimensionValues'][0]['value'];
            $state  = $row['dimensionValues'][1]['value'];
            $matric_value = $row['metricValues'][0]['value'];

            if( $state == 'current' ){
                $dataset['current_dataset'][] = array(
                    'date' => $date,
                    'value' => $matric_value
                );
            } elseif( $state == 'previous' ){
                $dataset['previous_dataset'][] = array(
                    'date' => $date,
                    'value' => $matric_value
                );
            }
        }

        return $dataset;
    }

    /**
     * Prepare device types data from API response
     *
     * @param array $response_data The API response data containing device information
     * @return array Formatted device types data sorted by value in descending order
     */
    public function prepare_device_types($response_data) {
        $result = [];
        $rows = !empty($response_data['rows']) ? $response_data['rows'] : [];

        // Loop through each row
        foreach ($rows as $index => $row) {
            $type = $row['dimensionValues'][0]['value'];
            $value = floatval($row['metricValues'][0]['value']);
            
            // Create object structure matching state.js format
            $result[] = [
                'type' => $type,
                'value' => $value
            ];
        }

        // Sort by value descending to match typical charting needs
        usort($result, function($a, $b) {
            return $b['value'] <=> $a['value'];
        });

        return $result;
    }

    /**
     * Prepare top items list from API response
     *
     * @param array $response_data The API response data containing dimension metrics
     * @return array Formatted list of top items (pages, referrers, countries) sorted by value
     * @throws \Exception If API response contains an error
     */
    public function prepare_top_items_list($response_data) {
        if (!empty($response_data['error'])) {
            throw new \Exception(
                esc_html($response_data['error']['message']), 
                esc_html($response_data['error']['code'])
            );
        }

        $result = [];
        $rows = !empty($response_data['rows']) ? $response_data['rows'] : [];

        // Loop through each row
        foreach ($rows as $row) {
            // Get dimension value (page path, referrer, or country name)
            $dimension_key = $row['dimensionValues'][0]['value'];
            $dimension_name = $this->get_dimension_name_from_request($response_data);
            
            // Get metric value (typically pageviews or sessions)
            $metric_value = floatval($row['metricValues'][0]['value']);
            
            // Format based on the dimension type
            if (strpos($dimension_name, 'pagePath') !== false) {
                $result[] = [
                    'page' => $dimension_key,
                    'value' => $metric_value
                ];
            } elseif (strpos($dimension_name, 'firstUserSource') !== false) {
                $result[] = [
                    'referrer' => $dimension_key,
                    'value' => $metric_value
                ];
            } elseif (strpos($dimension_name, 'country') !== false) {
                $result[] = [
                    'country' => $dimension_key,
                    'value' => $metric_value
                ];
            } else {
                // Generic fallback
                $result[] = [
                    'name' => $dimension_key,
                    'value' => $metric_value
                ];
            }
        }

        // Sort by value in descending order
        usort($result, function($a, $b) {
            return $b['value'] <=> $a['value'];
        });

        return $result;
    }

    /**
     * Get dimension name from the response data
     *
     * @param array $response_data The API response data containing dimension headers
     * @return string The dimension name or empty string if not found
     */
    private function get_dimension_name_from_request($response_data) {
        if (!empty($response_data['dimensionHeaders']) && isset($response_data['dimensionHeaders'][0]['name'])) {
            return $response_data['dimensionHeaders'][0]['name'];
        }
        return '';
    }
}
