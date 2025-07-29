<?php
/**
 * Include a plugin file safely
 *
 * @param string $path File path relative to plugin directory e.g: 'includes/functions/actions.php'
 * @return bool True if file was included successfully, false otherwise
 */
if( !function_exists('htga4_include_plugin_file') ){
    function htga4_include_plugin_file( $path ){
        // Get plugin directory path
        $plugin_dir = plugin_dir_path( HT_EASY_GA4_ROOT );
        
        // Clean the path and ensure it's relative
        $path = ltrim( str_replace('\\', '/', $path), '/' );
        
        // Build full path
        $full_path = $plugin_dir . $path;
        
        // Validate path is within plugin directory
        if( strpos(realpath($full_path), realpath($plugin_dir)) !== 0 ){
            return false;
        }
        
        if( file_exists($full_path) ){
            return include $full_path;
        }
        
        return false;
    }
}

/**
 * Disable transient cache
 *
 * @return bool True if transient cache is disabled, false otherwise
 */
function htga4_disable_transient_cache(){
    return false;
}


/**
 * Check if the current request is being made through ngrok
 *
 * @return string|null Returns the ngrok URL if the request is being made through ngrok, otherwise returns null
 */
function htga4_is_ngrok_url(){
    // development mode with ngrok
    $forwarded_host = !empty($_SERVER['HTTP_X_FORWARDED_HOST']) ? wp_unslash($_SERVER['HTTP_X_FORWARDED_HOST']) : ''; // phpcs:ignore

    if( $forwarded_host == 'dominant-fleet-swan.ngrok-free.app' ){
        return 'https://dominant-fleet-swan.ngrok-free.app';
    }

    return null;
}

if( !function_exists('htga4_roles_dropdown_options') ){
    function htga4_roles_dropdown_options(){
        global $wp_roles;
        
        if (!isset($wp_roles)) {
            $wp_roles = new WP_Roles();
        }
        
        $roles = $wp_roles->get_names();
        $options = array();
        
        foreach ($roles as $role_slug => $role_name) {
            $options[$role_slug] = $role_name;
        }
        
        return $options;
    }
}

/**
 * Returns the path to the configuration file for the plugin.
 *
 * @return string The path to the configuration file.
 */
if( !function_exists('htga4_get_config_file') ){
    function htga4_get_config_file(){
        // if wp environment is test and debug is true then use test config file
        if( defined('WP_ENVIRONMENT_TYPE') && WP_ENVIRONMENT_TYPE === 'development' ){
            return HT_EASY_GA4_PATH .'/includes/config-test.json';
        } else {
            return HT_EASY_GA4_PATH .'/includes/config.json';
        }
    }
}

/**
* @param name The name of the configuration value to retrieve from the config.json.
* 
* @return string|array
*/
if( !function_exists('htga4_get_config_value') ){
    function htga4_get_config_value( $name = '' ){
        $file         =  htga4_get_config_file();

        $return_value = '';
        $config_arr   = array();

        if( is_readable($file) ){
            $file_content = file_get_contents( $file ); // phpcs:ignore
            $config_arr   = json_decode( $file_content, true );
        }

        if( !empty($name) ){
            $return_value = isset($config_arr['web'][$name]) ? $config_arr['web'][$name] : '';

            if( $name === 'redirect_uris' && is_array($return_value) ){
                $return_value = current($return_value);
            }

            if( $name === 'javascript_origins' && is_array($return_value) ){
                $return_value = current($return_value);
            }
        } else {
            $return_value = $config_arr;
        }

        return $return_value;
    }
}

/**
 * Get the API base URL for GA4 REST API
 *
 * @return string The API base URL
 */
if( !function_exists('htga4_get_api_base_url') ){
    function htga4_get_api_base_url() {
        // Get redirect URI from config
        $redirect_uri = htga4_get_config_value('redirect_uris');
        
        // If config doesn't provide a redirect URI, fall back to site URL
        if(empty($redirect_uri)){
            $redirect_uri = get_site_url();
        }
        
        // Construct and return the REST base URL
        return $redirect_uri . '/index.php?rest_route=/htga4/';
    }
}

/**
 * Get the full URL for a specific GA4 API endpoint
 *
 * @param string $endpoint The API endpoint
 * @return string The full URL for the API endpoint
 */
if( !function_exists('htga4_get_api_url') ){
    function htga4_get_api_url($endpoint) {
        return htga4_get_api_base_url() . $endpoint;
    }
}

/**
 * Get the access token API URL for GA4
 *
 * @return string The access token API URL
 */
if( !function_exists('htga4_get_access_token_url') ){
    function htga4_get_access_token_url() {
        return htga4_get_api_url('v1/get-access-token');
    }
}

/**
 * Get the current access token from transient storage
 *
 * @return string|false The access token if available, false otherwise
 */
if( !function_exists('htga4_get_access_token') ){
    function htga4_get_access_token() {
        return get_transient('htga4_access_token');
    }
}


/**
 * Get the measurement ID with backward compatibility support
 * 
 * @return string The measurement ID
 */
function htga4_get_measurement_id() {
    $options = get_option( 'ht_easy_ga4_options' );
    
    // Check new options first
    if( !empty($options['measurement_id']) ) {
        return $options['measurement_id'];
    }
    
    // Fallback to old option for backward compatibility
    if( get_option('ht_easy_ga4_id') ) {
        return get_option('ht_easy_ga4_id');
    }
    
    return '';
}

/**
 * This function retrieves a specific option value from the 'ht_easy_ga4_options' array or returns
 * a default value if the option is not set.
 * 
 * @param option_name The name of the option to retrieve from the options array.
 * @param default The default value to return if the option is not set or does not exist.
 * 
 * @return string|array
 */
function htga4_get_option( $option_name = '', $default = null ) {
    $options = get_option( 'ht_easy_ga4_options' );
    
    // Look into the updated options first
    if( isset( $options[$option_name] ) ){
        return $options[$option_name];
    }
    
    return $default;
}
