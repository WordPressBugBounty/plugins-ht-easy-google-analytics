<?php
namespace Ht_Easy_Ga4\Vue_Settings;

class Settings_Schema {
    /**
     * Get the complete settings schema
     */
    public static function get_schema() {
        return array_merge(
            // Generall
            require __DIR__ . '/schema-parts/general.php',

            // Events Tracking
            require __DIR__ . '/schema-parts/events-tracking.php',

            // Custom Events
            require __DIR__ . '/schema-parts/custom-events.php',

            // Cookie Notice
            require __DIR__ . '/schema-parts/cookie-notice.php',

            // Tools
            require __DIR__ . '/schema-parts/tools.php',

            // Cache
            require __DIR__ . '/schema-parts/cache.php',

            // Google Ads
            require __DIR__ . '/schema-parts/google-ads.php',
        );
    }
}
