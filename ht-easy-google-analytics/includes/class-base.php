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

		// Set Notice.
		add_action('admin_head', function(){
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

		require_once HT_EASY_GA4_PATH . 'admin/class-recommended-plugins.php';
		require_once HT_EASY_GA4_PATH . 'admin/class-recommended-plugins-init.php';

		if( is_admin() ){
			require_once ( HT_EASY_GA4_PATH .'admin/class-trial.php' );
			require_once ( HT_EASY_GA4_PATH .'admin/class-diagnostic-data.php' );

			require_once HT_EASY_GA4_PATH . 'admin/class-notice-handler.php';
		}

		require_once HT_EASY_GA4_PATH . 'frontend/class-frontend.php';
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

			$response = wp_remote_post(
				htga4_get_api_url('v1/delete-data'),
				array(
					'timeout'   => 20,
					'body'      => array(
						'email' => sanitize_email( $mail ),
					),
					'sslverify' => false,
				)
			);

			// If the request is success.
			if ( ! is_wp_error( $response ) ) {

				$response_body = json_decode( $response['body'], true );
				if ( ! empty( $response_body['success'] ) ) {
					delete_option( 'htga4_email' );

					$current_admin_url = $this->get_current_admin_url();
					// Remove htga4_logout from URL.
					$current_admin_url = remove_query_arg( 'htga4_logout', $current_admin_url );
					wp_safe_redirect( $current_admin_url );
					return;
				}
			}
		}
	}
}