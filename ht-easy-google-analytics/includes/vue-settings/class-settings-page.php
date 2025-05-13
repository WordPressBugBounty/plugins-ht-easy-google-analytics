<?php
namespace Ht_Easy_Ga4\Vue_Settings;
use const Ht_Easy_Ga4\PL_URL;
use const Ht_Easy_Ga4\PL_VERSION;

class Settings_Page {
    use \Ht_Easy_Ga4\Helper_Trait;
    // use \Ht_Easy_Ga4\Rest_Request_Handler_Trait;

    public $version;

    public $plugin_screens = array(
        'toplevel_page_ht-easy-ga4-setting-page'
    );

    public $hide_notices_screens = array(
        'toplevel_page_ht-easy-ga4-setting-page'
    );

    private static $_instance = null;
    /**
     * Get Instance
     */
    public static function instance(){
        if( is_null( self::$_instance ) ){
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * Constructor
     */
    public function __construct() {
        // Version with time for cache busting
		if( defined( 'WP_DEBUG' ) && WP_DEBUG ){
			$this->version = time();
		} else {
			$this->version = PL_VERSION;
		}

        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));

        // Add hook to remove admin notices on specific pages
        add_action('admin_head', array($this, 'remove_admin_notices'), 1);
        
        // Intentionally load the editor in the footer to override the css loaded in the header
        add_action('admin_footer', function(){
            $current_screen = get_current_screen();

            if (!in_array($current_screen->id, $this->plugin_screens)) {
                return;
            }

            wp_enqueue_editor();
        });

        
    }

    public function remove_admin_notices() {
        $current_screen = get_current_screen();

        // Check if current screen should have notices removed
        if (in_array($current_screen->id, $this->hide_notices_screens)) {
            // Remove all notices
            remove_all_actions('admin_notices');
            remove_all_actions('all_admin_notices');
        }
    }

    /**
     * Enqueue required scripts and styles
     */
    public function enqueue_scripts($hook) {
        if (!in_array($hook, $this->plugin_screens)) {
            return;
        }

        $is_dev = isset($_SERVER['HTTP_HOST']) && $_SERVER['HTTP_HOST'] === 'plugindev.test';
        

        if ($is_dev && $this->is_vite_running()) {
            // Development mode - load from Vite dev server
            wp_enqueue_script(
                'htga4-vue-settings-vite-client',
                'http://localhost:5173/@vite/client',
                array(),
                null,
                true
            );

            add_filter('script_loader_tag', function($tag, $handle, $src) {
                // For cache busting
                $src = $src . '?v=' . $this->version;

                if ($handle === 'htga4-vue-settings') {
                    return '<script type="module" src="' . esc_url($src) . '"></script>';
                }
                return $tag;
            }, 10, 3);

            wp_enqueue_script(
                'htga4-vue-settings',
                'http://localhost:5173/src/vue-settings/main.js' . '?v=' . $this->version,
                array('htga4-vue-settings-vite-client'),
                null,
                true
            );
        } else {
            // Production mode - load built files
            // CSS
            wp_enqueue_style(
                'htga4-vue-settings-style',
                PL_URL . '/build/vue-settings/style.css',
                array(),
                $this->version,
                'all'
            );

            // JS
            wp_enqueue_script(
                'htga4-vue-settings',
                PL_URL . '/build/vue-settings/main.js',
                array(),
                $this->version,
                true
            );

            // For cache busting
            // $this->enqueue_scripts_from_manifest(); // Updated the vite build process, no longer needed

            add_filter('script_loader_tag', function($tag, $handle, $src) {
                if ($handle === 'htga4-vue-settings') {
                    return '<script type="module" src="' . esc_url($src) . '"></script>';
                }
                return $tag;
            }, 10, 3);
        }

        $menu = htga4_include_plugin_file( 'includes/vue-settings/menu.php' );
        $currency_symbol = function_exists('get_woocommerce_currency_symbol') ? get_woocommerce_currency_symbol() : '';

        // Localize script with nonce and API info
        wp_localize_script('htga4-vue-settings', 'htga4Settings', array(
            'nonce'       => wp_create_nonce('wp_rest'),
            'apiBaseURL'  => esc_url_raw(rest_url()),
            'pluginVersion' => PL_VERSION,
            'apiEndpoint' => 'htga4/v1/settings',
            'rolesApiEndpoint' => 'htga4/v1/wholesaler-roles',
            'proAdvInfo' => array(
                'purchaseURL' => 'https://hasthemes.com/plugins/google-analytics-plugin-for-wordpress?utm_source=wp-org&utm_medium=ht-ga4&utm_campaign=htga4_plugin-page#pricing',
                'message' => __('Our free version is great, but it doesn\'t have all our advanced features. The best way to unlock all of the features in our plugin is by purchasing the pro version.', 'ht-easy-ga4'),
            ),
            'siteUrl' => site_url(),
            'adminUrl' => admin_url(),
            'supportUrl' => 'https://hasthemes.com/contact-us/',
            'docsUrl' => 'https://hasthemes.com/docs/ht-easy-ga4/how-to/',
            'proUrl' => 'https://hasthemes.com/plugins/google-analytics-plugin-for-wordpress?utm_source=wp-org&utm_medium=ht-ga4&utm_campaign=htga4_plugin-page#pricing',
            'loginUrl' => $this->get_auth_url(),

            // Ga4 data
            'email' => get_option( 'htga4_email' ),
            'accessToken' => GA4_API_Service::get_instance()->get_access_token(),
            'ngrokUrl' => htga4_is_ngrok_url(),

            // There is some dynamic defaults so manage it from one place here
            'defaultSettings' => Settings_Defaults::get_defaults(),

            // Translations
            'i18n' => array(
                'save' => esc_html__('General Settings', 'ht-easy-ga4'),
                'loading' => esc_html__('Loading...', 'ht-easy-ga4'),
                'error' => esc_html__('Error', 'ht-easy-ga4'),
            ),


            'isProInstalled' => $this->is_pro_plugin_installed(),
            'isProActive' => $this->is_pro_plugin_active(),

            // Plugins Settings
            'globalSettings' => array(
                'currency_symbol' => $currency_symbol
            ),

            'menu' => $menu,
            'environmentType' => $this->get_environment_type()
        ));

        wp_localize_script('htga4-vue-settings', 'htga4SettingsSchema', Settings_Schema::get_schema());
    }

    /**
     * Get environment type
     * 
     * @return string 'development' | 'staging' | 'production'
     */
    private function get_environment_type() {
        // Check WP_ENVIRONMENT_TYPE first
        if (defined('WP_ENVIRONMENT_TYPE') && in_array(WP_ENVIRONMENT_TYPE, array('development', 'staging', 'production'))) {
            return WP_ENVIRONMENT_TYPE;
        }

        return defined('WP_DEBUG') && WP_DEBUG ? 'development' : 'production';
    }

    /**
     * Check if Vite dev server is running
     */
    private function is_vite_running() {
        $handle = curl_init('http://localhost:5173');
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($handle, CURLOPT_NOBODY, true);

        curl_exec($handle);
        $error = curl_errno($handle);
        curl_close($handle);

        return !$error;
    }

    /**
     * Render the Vue app container
     */
    public function render_app() {
        ?>
        <div class="wrap">
            <div id="htga4-vue-settings-app"></div>
        </div>
        <?php
    }
}
