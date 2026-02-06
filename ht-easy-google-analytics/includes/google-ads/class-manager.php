<?php
/**
 * Google Ads Manager Class
 *
 * Handles Google Ads conversion tracking
 *
 * @package Ht_Easy_Ga4\GoogleAds
 * @since 1.8.0
 */

namespace Ht_Easy_Ga4\GoogleAds;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Manager class for Google Ads
 */
class Manager {

	/**
	 * Instance
	 *
	 * @var Manager|null
	 */
	private static $instance = null;

	/**
	 * Conversion Tracker instance
	 *
	 * @var Conversion_Tracker
	 */
	private $conversion_tracker;

	/**
	 * Settings
	 *
	 * @var array
	 */
	private $settings;

	/**
	 * Get singleton instance
	 *
	 * @return Manager
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->settings = $this->get_settings();
		$this->load_dependencies();
		$this->init_hooks();
		$this->init_components();
	}

	/**
	 * Load required dependencies
	 */
	private function load_dependencies() {
		// Load conversion tracker
		require_once HT_EASY_GA4_PATH . 'includes/google-ads/class-conversion-tracker.php';
		require_once HT_EASY_GA4_PATH . 'includes/google-ads/class-settings.php';
		require_once HT_EASY_GA4_PATH . 'includes/google-ads/class-upgrade-notice.php';
	}

	/**
	 * Initialize hooks
	 */
	private function init_hooks() {
		// Initialize tracking if enabled
		add_action( 'init', array( $this, 'init_tracking' ) );

		// Enqueue frontend scripts
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_scripts' ) );

		// Enqueue admin scripts
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );

		// Load gtag in admin for testing purposes
		add_action( 'admin_head', array( $this, 'load_gtag_in_admin' ), 5 );

		// Register REST API endpoints
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );

		// Add to Vue settings schema
		add_filter( 'htga4_settings_schema', array( $this, 'add_settings_schema' ) );

		// Add to Vue settings defaults
		add_filter( 'htga4_settings_defaults', array( $this, 'add_settings_defaults' ) );
	}

	/**
	 * Initialize components
	 */
	private function init_components() {
		if ( ! empty( $this->settings['enabled'] ) && ! empty( $this->settings['conversion_id'] ) ) {
			// Initialize conversion tracking with settings passed to avoid circular dependency
			$this->conversion_tracker = new Conversion_Tracker( $this->settings );
		}

		// Initialize upgrade notices
		new Upgrade_Notice();
	}

	/**
	 * Initialize tracking
	 */
	public function init_tracking() {
		if ( empty( $this->settings['enabled'] ) || empty( $this->settings['conversion_id'] ) ) {
			return;
		}

		// Check if user should be excluded
		if ( $this->should_exclude_user() ) {
			return;
		}

		// Output Google Ads global site tag
		add_action( 'wp_head', array( $this, 'output_global_site_tag' ), 15 );
	}

	/**
	 * Check if current user should be excluded from tracking
	 *
	 * @return bool
	 */
	private function should_exclude_user() {
		if ( ! is_user_logged_in() ) {
			return false;
		}

		$excluded_roles = isset( $this->settings['excluded_roles'] ) ? $this->settings['excluded_roles'] : array();

		if ( empty( $excluded_roles ) ) {
			return false;
		}

		$user = wp_get_current_user();
		$user_roles = (array) $user->roles;

		return ! empty( array_intersect( $user_roles, $excluded_roles ) );
	}

	/**
	 * Load gtag in admin for testing purposes
	 */
	public function load_gtag_in_admin() {
		// Only load on our settings page
		$screen = get_current_screen();
		if ( ! $screen || $screen->id !== 'toplevel_page_ht-easy-ga4-setting-page' ) {
			return;
		}

		// Only load if enabled and configured
		if ( empty( $this->settings['enabled'] ) || empty( $this->settings['conversion_id'] ) ) {
			return;
		}

		// Output the gtag script for admin testing
		$this->output_global_site_tag();
	}

	/**
	 * Output Google Ads global site tag
	 */
	public function output_global_site_tag() {
		$conversion_id = esc_js( $this->settings['conversion_id'] );

		// Check if gtag.js is already loaded by GA4
		$ga4_settings = get_option( 'htga4_settings', array() );
		$ga4_measurement_id = isset( $ga4_settings['general']['measurement_id'] ) ? $ga4_settings['general']['measurement_id'] : '';

		?>
		<!-- Google Ads Conversion Tracking - HT Easy GA4 -->
		<?php if ( ! $ga4_measurement_id ) : ?>
		<script async src="https://www.googletagmanager.com/gtag/js?id=AW-<?php echo $conversion_id; ?>"></script>
		<?php endif; ?>
		<script>
			window.dataLayer = window.dataLayer || [];
			function gtag(){dataLayer.push(arguments);}

			// Initialize gtag if not already done
			if (typeof window.htga4_gtag_initialized === 'undefined') {
				gtag('js', new Date());
				window.htga4_gtag_initialized = true;
			}

			// Configure Google Ads
			gtag('config', 'AW-<?php echo $conversion_id; ?>', {
				'send_page_view': false
			});

			// Store conversion ID for later use
			window.htga4_google_ads = {
				conversion_id: 'AW-<?php echo $conversion_id; ?>'
			};
		</script>
		<!-- End Google Ads Conversion Tracking -->
		<?php
	}

	/**
	 * Enqueue frontend scripts
	 */
	public function enqueue_frontend_scripts() {
		if ( empty( $this->settings['enabled'] ) || empty( $this->settings['conversion_id'] ) ) {
			return;
		}

		// Enqueue conversion tracking script
		wp_enqueue_script(
			'htga4-google-ads-tracking',
			HT_EASY_GA4_URL . 'assets/js/google-ads-tracking.js',
			array( 'jquery' ),
			HT_EASY_GA4_VERSION,
			true
		);

		// Localize script
		wp_localize_script(
			'htga4-google-ads-tracking',
			'htga4_google_ads',
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce' => wp_create_nonce( 'htga4_google_ads' ),
				'settings' => array(
					'conversion_id' => $this->settings['conversion_id'],
				),
				'is_woocommerce' => class_exists( 'WooCommerce' ),
				'currency' => function_exists( 'get_woocommerce_currency' ) ? get_woocommerce_currency() : 'USD',
			)
		);
	}

	/**
	 * Enqueue admin scripts
	 */
	public function enqueue_admin_scripts( $hook ) {
		// Only load on our admin pages
		if ( strpos( $hook, 'ht-easy-ga4-setting-page' ) === false ) {
			return;
		}

		// Enqueue admin styles
		wp_enqueue_style(
			'htga4-google-ads-admin',
			HT_EASY_GA4_URL . 'assets/css/google-ads-admin.css',
			array(),
			HT_EASY_GA4_VERSION
		);
	}

	/**
	 * Register REST API routes
	 */
	public function register_rest_routes() {
		// Settings endpoints
		register_rest_route(
			'htga4/v1',
			'/google-ads/settings',
			array(
				array(
					'methods' => 'GET',
					'callback' => array( $this, 'get_settings_endpoint' ),
					'permission_callback' => function() {
						return current_user_can( 'manage_options' );
					},
				),
				array(
					'methods' => 'POST',
					'callback' => array( $this, 'update_settings_endpoint' ),
					'permission_callback' => function() {
						return current_user_can( 'manage_options' );
					},
				),
			)
		);

		// Test conversion endpoint
		register_rest_route(
			'htga4/v1',
			'/google-ads/test',
			array(
				'methods' => 'POST',
				'callback' => array( $this, 'test_conversion' ),
				'permission_callback' => function() {
					return current_user_can( 'manage_options' );
				},
			)
		);
	}

	/**
	 * Add settings schema for Vue
	 */
	public function add_settings_schema( $schema ) {
		$schema['google_ads'] = array(
			'type' => 'object',
			'properties' => array(
				'enabled' => array(
					'type' => 'boolean',
					'default' => false,
				),
				'conversion_id' => array(
					'type' => 'string',
					'default' => '',
				),
				'conversion_labels' => array(
					'type' => 'object',
					'default' => array(),
				),
				'excluded_roles' => array(
					'type' => 'array',
					'items' => array(
						'type' => 'string',
					),
					'default' => array( 'administrator' ),
				),
			),
		);

		return $schema;
	}

	/**
	 * Add settings defaults for Vue
	 */
	public function add_settings_defaults( $defaults ) {
		$defaults['google_ads'] = array(
			'enabled' => false,
			'conversion_id' => '',
			'conversion_labels' => array(
				'purchase' => array(
					'enabled' => false,
					'label' => ''
				),
				'add_to_cart' => array(
					'enabled' => false,
					'label' => ''
				),
				'checkout' => array(
					'enabled' => false,
					'label' => ''
				),
				'view_product' => array(
					'enabled' => false,
					'label' => ''
				),
				'view_category' => array(
					'enabled' => false,
					'label' => ''
				),
			),
			'excluded_roles' => array( 'administrator' ),
		);

		return $defaults;
	}

	/**
	 * Get settings
	 *
	 * @return array
	 */
	public function get_settings() {
		// Get from main settings option used by Vue settings system
		$main_settings = get_option( 'ht_easy_ga4_options', array() );

		$defaults = array(
			'enabled' => false,
			'conversion_id' => '',
			'excluded_roles' => array( 'administrator' ),
			// Individual conversion tracking with separate labels
			'conversion_labels' => array(
				'purchase' => array(
					'enabled' => false,
					'label' => ''
				),
				'add_to_cart' => array(
					'enabled' => false,
					'label' => ''
				),
				'checkout' => array(
					'enabled' => false,
					'label' => ''
				),
				'view_product' => array(
					'enabled' => false,
					'label' => ''
				),
				'view_category' => array(
					'enabled' => false,
					'label' => ''
				),
			),
		);

		if ( isset( $main_settings['google_ads'] ) ) {
			// Don't use wp_parse_args for excluded_roles as it can't distinguish empty array from unset
			$settings = $main_settings['google_ads'];

			// Manually apply defaults for missing keys only
			foreach ( $defaults as $key => $default_value ) {
				if ( ! isset( $settings[ $key ] ) ) {
					$settings[ $key ] = $default_value;
				}
			}

			// Ensure conversion_labels structure is merged properly
			if ( isset( $main_settings['google_ads']['conversion_labels'] ) ) {
				foreach ( $defaults['conversion_labels'] as $event_type => $default_config ) {
					if ( ! isset( $settings['conversion_labels'][ $event_type ] ) ) {
						$settings['conversion_labels'][ $event_type ] = $default_config;
					} else {
						$settings['conversion_labels'][ $event_type ] = wp_parse_args(
							$settings['conversion_labels'][ $event_type ],
							$default_config
						);
					}
				}
			}
		} else {
			$settings = $defaults;
		}

		// Ensure boolean values are properly cast
		$settings['enabled'] = (bool) filter_var( $settings['enabled'] ?? false, FILTER_VALIDATE_BOOLEAN );

		// Cast conversion label booleans
		if ( isset( $settings['conversion_labels'] ) && is_array( $settings['conversion_labels'] ) ) {
			foreach ( $settings['conversion_labels'] as $event_type => $config ) {
				if ( isset( $config['enabled'] ) ) {
					$settings['conversion_labels'][ $event_type ]['enabled'] = (bool) filter_var( $config['enabled'], FILTER_VALIDATE_BOOLEAN );
				}
			}
		}

		return $settings;
	}

	/**
	 * Update settings
	 *
	 * @param array $settings Settings to update
	 * @return bool
	 */
	public function update_settings( $settings ) {
		// Ensure boolean values are properly cast before saving
		$settings['enabled'] = (bool) filter_var( $settings['enabled'] ?? false, FILTER_VALIDATE_BOOLEAN );

		// Cast conversion_labels booleans
		if ( isset( $settings['conversion_labels'] ) && is_array( $settings['conversion_labels'] ) ) {
			foreach ( $settings['conversion_labels'] as $event_type => $config ) {
				$settings['conversion_labels'][ $event_type ]['enabled'] = (bool) filter_var( $config['enabled'] ?? false, FILTER_VALIDATE_BOOLEAN );
				$settings['conversion_labels'][ $event_type ]['label'] = sanitize_text_field( $config['label'] ?? '' );
			}
		}

		// Update in main settings option used by Vue settings system
		$main_settings = get_option( 'ht_easy_ga4_options', array() );
		$main_settings['google_ads'] = $settings;
		$result = update_option( 'ht_easy_ga4_options', $main_settings );

		// Update instance settings
		$this->settings = $settings;

		return $result;
	}

	/**
	 * REST API: Get settings
	 */
	public function get_settings_endpoint( $request ) {
		$settings = $this->get_settings();

		// Ensure booleans are properly cast for REST response
		$settings['enabled'] = (bool) $settings['enabled'];

		// Ensure conversion_labels booleans are cast
		if ( isset( $settings['conversion_labels'] ) && is_array( $settings['conversion_labels'] ) ) {
			foreach ( $settings['conversion_labels'] as $event_type => $config ) {
				$settings['conversion_labels'][ $event_type ]['enabled'] = (bool) ( $config['enabled'] ?? false );
			}
		}

		return new \WP_REST_Response( $settings, 200 );
	}

	/**
	 * REST API: Update settings
	 */
	public function update_settings_endpoint( $request ) {
		$settings = $request->get_json_params();

		// Sanitize basic settings
		$settings['enabled'] = (bool) ( $settings['enabled'] ?? false );
		$settings['conversion_id'] = sanitize_text_field( $settings['conversion_id'] ?? '' );
		// Preserve excluded_roles as array, even if empty
		$settings['excluded_roles'] = isset( $settings['excluded_roles'] ) && is_array( $settings['excluded_roles'] )
			? array_map( 'sanitize_text_field', $settings['excluded_roles'] )
			: array();

		// Sanitize conversion labels for each event type
		if ( isset( $settings['conversion_labels'] ) && is_array( $settings['conversion_labels'] ) ) {
			foreach ( $settings['conversion_labels'] as $event_type => $config ) {
				$settings['conversion_labels'][ $event_type ] = array(
					'enabled' => (bool) ( $config['enabled'] ?? false ),
					'label' => sanitize_text_field( $config['label'] ?? '' )
				);
			}
		}

		$result = $this->update_settings( $settings );

		if ( $result ) {
			return new \WP_REST_Response( array( 'success' => true, 'message' => __( 'Settings saved successfully', 'ht-easy-ga4' ) ), 200 );
		}

		return new \WP_Error( 'save_failed', __( 'Failed to save settings', 'ht-easy-ga4' ), array( 'status' => 500 ) );
	}

	/**
	 * REST API: Test conversion tracking
	 */
	public function test_conversion( $request ) {
		// Get fresh settings
		$current_settings = $this->get_settings();

		if ( empty( $current_settings['conversion_id'] ) ) {
			return new \WP_Error( 'not_configured', __( 'Please configure your Google Ads Conversion ID first', 'ht-easy-ga4' ), array( 'status' => 400 ) );
		}

		// Find the first enabled conversion event with a label (prioritize purchase event)
		$test_label = '';
		if ( isset( $current_settings['conversion_labels'] ) && is_array( $current_settings['conversion_labels'] ) ) {
			// First check if purchase event is enabled and has a label
			if ( ! empty( $current_settings['conversion_labels']['purchase']['enabled'] ) &&
			     ! empty( $current_settings['conversion_labels']['purchase']['label'] ) ) {
				$test_label = $current_settings['conversion_labels']['purchase']['label'];
			} else {
				// Otherwise, use the first enabled event with a label
				foreach ( $current_settings['conversion_labels'] as $event_type => $config ) {
					if ( ! empty( $config['enabled'] ) && ! empty( $config['label'] ) ) {
						$test_label = $config['label'];
						break;
					}
				}
			}
		}

		if ( empty( $test_label ) ) {
			return new \WP_Error( 'not_configured', __( 'Please enable at least one conversion event with a conversion label', 'ht-easy-ga4' ), array( 'status' => 400 ) );
		}

		// Generate JavaScript code to trigger the conversion on the frontend
		$gtag_code = sprintf(
			"gtag('event', 'conversion', {
				'send_to': 'AW-%s/%s',
				'value': 10.00,
				'currency': 'USD',
				'transaction_id': 'TEST_%s'
			});",
			esc_js( $current_settings['conversion_id'] ),
			esc_js( $test_label ),
			uniqid()
		);

		return new \WP_REST_Response( array(
			'success' => true,
			'message' => __( 'Test conversion prepared. Execute the tracking code in your browser to send it to Google Ads.', 'ht-easy-ga4' ),
			'settings' => array(
				'conversion_id' => $current_settings['conversion_id'],
				'conversion_label' => $test_label
			),
			'tracking_code' => $gtag_code,
			'requires_frontend_execution' => true
		), 200 );
	}

	/**
	 * Get instance of Conversion Tracker
	 *
	 * @return Conversion_Tracker|null
	 */
	public function get_conversion_tracker() {
		return $this->conversion_tracker;
	}

	/**
	 * Check if pro version is available
	 *
	 * @return bool
	 */
	public static function is_pro_available() {
		return function_exists( 'htga4_is_pro' ) && htga4_is_pro();
	}
}