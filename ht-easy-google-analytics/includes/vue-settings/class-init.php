<?php
namespace Ht_Easy_Ga4\Vue_Settings;

class Init {
    /**
     * Singleton instance
     *
     * @var Init
     */
    private static $instance;

    /**
     * Singleton instance
     *
     * @return Init
     */
    public static function instance() {
        if ( ! isset( self::$instance ) ) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Constructor
     */
    public function __construct() {
        $this->includes();
        $this->init();
    }

    /**
     * Include required files
     */
    public function includes() {
        // Core files
        require_once __DIR__ . '/class-settings-schema.php';
        require_once __DIR__ . '/class-settings-defaults.php';
        require_once __DIR__ . '/class-settings-page.php';
        require_once __DIR__ . '/class-settings-rest-api.php';
        
        // Reports
        require_once __DIR__ . '/reports/class-ga4-api.php';
        require_once __DIR__ . '/reports/class-data-transformer.php';
        require_once __DIR__ . '/reports/class-ga4-api-service.php';
        require_once __DIR__ . '/reports/class-standard-reports.php';
        require_once __DIR__ . '/reports/class-ecommerce-reports.php';
        require_once __DIR__ . '/reports/class-realtime-reports.php';
    }

    /**
     * Initialize components
     */
    public function init() {
        // Initialize Rest API (always)
        Settings_REST_API::instance();

        // Initialize Reports
        GA4_API::instance();

        // Initialize Admin pages only in admin
        if (is_admin()) {
            Settings_Page::instance();
        }
    }
}

Init::instance();