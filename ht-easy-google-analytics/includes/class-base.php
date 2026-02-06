<?php
namespace Ht_Easy_Ga4;

class Base {
	use \Ht_Easy_Ga4\Helper_Trait;

	/**
	 * [$_instance]
	 *
	 * @var null
	 */
	private static $_instance = null;

	/**
	 * [instance] Initializes a singleton instance
	 *
	 * @return Base
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	public function __construct() {
		// Load text domain.
		add_action( 'init', array( $this, 'i18n' ) );

		// Include files.
		add_action( 'plugins_loaded', array( $this, 'includes' ) );

		// Add settings in plugin action.
		add_filter(
			'plugin_action_links_' . HT_EASY_GA4_BASE,
			function( $links ) {
				$link = sprintf( "<a href='%s'>%s</a>", esc_url( admin_url( 'admin.php?page=ht-easy-ga4-setting-page' ) ), __( 'Settings', 'ht-easy-ga4' ) );

				array_push( $links, $link );

				return $links;
			}
		);

		// Save the code for after login
		add_action('plugins_loaded', function(){
			$htga4_email = !empty( $_GET['email']) ? sanitize_email( $_GET['email']) : ''; // phpcs:ignore
			$htga4_sr_api_key = !empty( $_GET['key']) ? sanitize_text_field( $_GET['key']) : ''; // phpcs:ignore
			
			$nonce = !empty( $_GET['_wpnonce']) ? sanitize_text_field( $_GET['_wpnonce']) : '';  // phpcs:ignore
			$nonce_check_result = wp_verify_nonce($nonce, 'htga4_save_key_nonce');

			if( $nonce_check_result && $htga4_email && current_user_can('manage_options') ){
				update_option('htga4_email', $htga4_email);
				update_option('htga4_sr_api_key', $htga4_sr_api_key);

				$admin_url 		= admin_url('admin.php?page=ht-easy-ga4-setting-page');
				if( $this->is_ngrok_url() ){
					$admin_url = $this->get_ngrok_url() . '/wp-admin/admin.php?page=ht-easy-ga4-setting-page';
				}

				header("Location:$admin_url");
			}
		});

		// Action when login & logout.
		add_action( 'admin_init', array( $this, 'login' ) );
		add_action( 'admin_init', array( $this, 'logout' ) );

		// Output centralized JS config
		add_action( 'wp_head', array( $this, 'output_js_config' ), 1 );

		// Set Notice.
		add_action('admin_head', function(){
			if ( ! class_exists( '\Ht_Easy_Ga4\Admin\Notice_Handler' ) ) {
				return;
			}

			$remote_banner_data = $this->get_plugin_remote_data();

			if (!empty($remote_banner_data) && is_array($remote_banner_data)) {
				foreach ($remote_banner_data as $banner) {
					if (empty($banner['disable'])) {
						Admin\Notice_Handler::set_notice($banner);
					}
				}
			}
		});
	}

	public function i18n() {
		load_plugin_textdomain( 'ht-easy-ga4', false, dirname( plugin_basename( HT_EASY_GA4_ROOT ) ) . '/languages/' );
	}

	public function includes() {
		require_once HT_EASY_GA4_PATH . 'admin/class-admin.php';
		require_once HT_EASY_GA4_PATH . 'admin/class-menu.php';
		require_once HT_EASY_GA4_PATH . 'frontend/class-ga4-tracker.php';

		require_once HT_EASY_GA4_PATH . 'admin/class-recommended-plugins.php';
		require_once HT_EASY_GA4_PATH . 'admin/class-recommended-plugins-init.php';
		require_once HT_EASY_GA4_PATH . 'includes/cookie-notice/class-cookie-notice.php';

		if( is_admin() ){
			// require_once ( HT_EASY_GA4_PATH .'admin/class-trial.php' );
			require_once ( HT_EASY_GA4_PATH .'admin/class-diagnostic-data.php' );

			require_once HT_EASY_GA4_PATH . 'admin/class-notice-handler.php';
		}

		require_once HT_EASY_GA4_PATH . 'frontend/class-frontend.php';

		// Load GA4 Inspector module
		require_once HT_EASY_GA4_PATH . 'includes/inspector/class-inspector.php';

		// Load Google Ads module
		$this->load_google_ads_module();

		// Load Server-Side Tracking module
		$this->load_server_side_module();

		// Load Events Tracking module
		$this->load_events_tracking_module();
	}

	/**
	 * Load Google Ads module
	 */
	private function load_google_ads_module() {
		// Load helper functions first
		require_once HT_EASY_GA4_PATH . 'includes/google-ads/helper-functions.php';

		// Check if Pro version is active
		if ( htga4_is_pro() && file_exists( WP_PLUGIN_DIR . '/ht-easy-google-analytics-pro/includes/google-ads-pro/class-pro-manager.php' ) ) {
			// Load Pro version
			require_once WP_PLUGIN_DIR . '/ht-easy-google-analytics-pro/includes/google-ads-pro/class-pro-manager.php';
			\Ht_Easy_Ga4\GoogleAdsPro\Pro_Manager::instance();
		} else {
			// Load main version
			require_once HT_EASY_GA4_PATH . 'includes/google-ads/class-manager.php';
			\Ht_Easy_Ga4\GoogleAds\Manager::instance();
		}
	}

	/**
	 * Load Server-Side Tracking module
	 */
	private function load_server_side_module() {
		// Load Measurement Protocol API client
		require_once HT_EASY_GA4_PATH . 'includes/server-side/class-measurement-protocol.php';
		// Load Free version
		require_once HT_EASY_GA4_PATH . 'includes/server-side/class-server-side-tracking.php';
		\Ht_Easy_Ga4\ServerSide\Server_Side_Tracking::instance();

		// Load AJAX handler for custom events
		require_once HT_EASY_GA4_PATH . 'includes/server-side/class-ajax-handler.php';
		\Ht_Easy_Ga4\ServerSide\Ajax_Handler::instance();
	}

	/**
	 * Load Events Tracking module (Purchase event only)
	 * Pro version handles all events in Manage_Data_Layer.php
	 */
	private function load_events_tracking_module() {
		// Load Events Tracking module (purchase event only)
		require_once HT_EASY_GA4_PATH . 'includes/events-tracking/class-event-tracker.php';
		require_once HT_EASY_GA4_PATH . 'includes/events-tracking/class-manager.php';
		\Ht_Easy_Ga4\EventsTracking\Manager::instance();
	}

	public function login() {
		$get_data = wp_unslash( $_GET ); // phpcs:ignore

		if (  current_user_can('manage_options') && ! empty( $get_data['access_token'] ) && ! empty( $get_data['email'] ) ) {
			set_transient( 'htga4_access_token', sanitize_text_field( $get_data['access_token'] ), ( MINUTE_IN_SECONDS * 58 ) );
			update_option( 'htga4_email', sanitize_email( $get_data['email'] ) );
		}
	}

	public function logout() {
		// Previllage check.
		if( !current_user_can('manage_options') ){
			return;
		}
		
		$get_data = wp_unslash( $_GET ); // phpcs:ignore

		// Return if there is no email, so no post request is sent.
		if ( ! get_option( 'htga4_email' ) ) {
			return;
		}

		$mail = get_option( 'htga4_email' );

		if ( ! empty( $get_data['htga4_logout'] ) ) {
			$this->clear_data();

			// Delete access_token & email.
			// We should not delete because it has other settings like events
			// delete_option( 'ht_easy_ga4_options' );

			$client_site = htga4_is_ngrok_url() !== null ? htga4_is_ngrok_url() : site_url();
			$email = sanitize_email($mail);
			$secret_key = get_option('htga4_secret_key');
			$timestamp = time();
			$sinature_data = $email . '|' . $client_site . '|' . $timestamp . '|';
			$signature = hash_hmac('sha256', $sinature_data, $secret_key);

			$body = [
				'email' => $email,
				'signature' => $signature,
				'timestamp' => $timestamp,
				'client_site' => $client_site
			];

			$response = wp_remote_post(
				htga4_get_api_url('v1/account/delete-data'),
				array(
					'timeout'   => 20,
					'body'      => $body,
					'sslverify' => false,
				)
			);

			// If the request is success.
			if ( ! is_wp_error( $response ) ) {

				$response_body = json_decode( $response['body'], true );
				if ( ! empty( $response_body['success'] ) ) {
					delete_option( 'htga4_email' );
					delete_option( 'htga4_secret_key' );

					$current_admin_url = $this->get_current_admin_url();
					// Remove htga4_logout from URL.
					$current_admin_url = remove_query_arg( 'htga4_logout', $current_admin_url );
					wp_safe_redirect( $current_admin_url );
					return;
				}
			}
		}
	}

	/**
	 * Output centralized JavaScript configuration
	 */
	public function output_js_config() {
		$cookie_notice = Cookie_Notice::get_instance();
		$config = array(
			'debug' => defined('WP_DEBUG') && WP_DEBUG,
			'cookie_notice_enabled' => htga4_get_option('cookie_notice_enabled'),
			'cookie_notice_cookie_key' => 'htga4_' . htga4_get_option('cookie_notice_cookie_key'),
			'cookie_notice_duration_type' => htga4_get_option('cookie_notice_duration_type'),
			'cookie_notice_duration_value' => htga4_get_option('cookie_notice_duration_value'),
			'cookie_notice_overlay_enabled' => htga4_get_option('cookie_notice_overlay_enabled'),
			'should_auto_consent' => $cookie_notice->should_auto_consent(),
			'one_year_seconds' => YEAR_IN_SECONDS,
		);
		?>
		<script>
			window.HTGA4 = <?php echo wp_json_encode($config); ?>;
		</script>
		<?php
	}
}