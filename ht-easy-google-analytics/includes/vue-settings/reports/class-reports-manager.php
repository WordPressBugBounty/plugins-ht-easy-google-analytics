<?php
namespace Ht_Easy_Ga4\Vue_Settings;

/**
 * Manages the coordination between different report types
 */
class Reports_Manager {
    /**
     * Get singleton instance
     * 
     * @return Reports_Manager
     */
    public static function instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private static $instance;

    /**
     * @var Standard_Reports
     */
    private $standard_reports;

    private function __construct() {
        // Get the singleton instance of Standard_Reports
        $this->standard_reports = Standard_Reports::instance();
    }

    public function get_standard_report($date_from, $date_to) {
        return $this->standard_reports->get_standard_report($date_from, $date_to);
    }
}