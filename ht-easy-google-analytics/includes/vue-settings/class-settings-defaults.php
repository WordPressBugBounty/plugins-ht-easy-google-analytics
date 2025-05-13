<?php
namespace Ht_Easy_Ga4\Vue_Settings;

class Settings_Defaults {
    /**
     * Get default values from the schema
     *
     * @return array Array of default values
     */
    public static function get_defaults_old() {
        $fields_schema = Settings_Schema::get_schema();

        return self::extract_defaults( $fields_schema );
    }

    public static function get_defaults() {
        $fields_schema = Settings_Schema::get_schema();

        return self::extract_defaults_new( $fields_schema );
    }

    /**
     * Recursively extract default values from fields
     *
     * @param array $fields Field configurations
     * @return array Extracted default values
     */
    public static function extract_defaults_new($route_schema = array()) {
        $defaults = [];

        foreach ($route_schema as $route_key => $route_info) {
           $fields = $route_info['fields'];

           $defaults = array_merge( $defaults, self::extract_defaults( $fields ) );
        }

        return $defaults;
    }

    /**
     * Recursively extract default values from fields
     *
     * @param array $fields Field configurations
     * @return array Extracted default values
     */
    public static function extract_defaults($fields = array()) {
        $defaults = [];

        foreach ($fields as $field_name => $field_config) {

            // Handle field type fieldset
            if( $field_config['type'] == 'fieldset' ) {

                $defaults[$field_name] = self::extract_defaults( $field_config['fields'] );

            } else if( isset($field_config['default']) ) {

                $defaults[$field_name] = $field_config['default'];

            }
        }

        return $defaults;
    }
}
