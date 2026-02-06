<?php
/**
 * Settings Class for Google Ads
 *
 * Handles settings management for Google Ads
 *
 * @package Ht_Easy_Ga4\GoogleAds
 * @since 1.8.0
 */

namespace Ht_Easy_Ga4\GoogleAds;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Settings class for Google Ads
 */
class Settings {

	/**
	 * Settings option name
	 *
	 * @var string
	 */
	const OPTION_NAME = 'htga4_google_ads_settings';

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->init_hooks();
	}

	/**
	 * Initialize hooks
	 */
	private function init_hooks() {
		// Add settings section to Vue component
		add_filter( 'htga4_vue_settings_tabs', array( $this, 'add_settings_tab' ) );

		// Add inline script for Vue settings
		add_action( 'admin_footer', array( $this, 'add_vue_settings_script' ) );
	}

	/**
	 * Add settings tab to Vue settings
	 */
	public function add_settings_tab( $tabs ) {
		$tabs['google_ads'] = array(
			'label' => __( 'Google Ads', 'ht-easy-ga4' ),
			'icon' => 'el-icon-s-marketing',
			'badge' => __( 'Free', 'ht-easy-ga4' ),
		);

		return $tabs;
	}

	/**
	 * Add Vue settings script
	 */
	public function add_vue_settings_script() {
		$screen = get_current_screen();

		if ( ! $screen || strpos( $screen->id, 'ht-easy-ga4-setting-page' ) === false ) {
			return;
		}

		$settings = Manager::instance()->get_settings();
		?>
		<script>
			// Google Ads Settings Configuration
			window.htga4GoogleAdsSettings = {
				current: <?php echo wp_json_encode( $settings ); ?>,
				api_endpoints: {
					get: '<?php echo esc_url( rest_url( 'htga4/v1/google-ads/settings' ) ); ?>',
					update: '<?php echo esc_url( rest_url( 'htga4/v1/google-ads/settings' ) ); ?>',
					test: '<?php echo esc_url( rest_url( 'htga4/v1/google-ads/test' ) ); ?>',
					report: '<?php echo esc_url( rest_url( 'htga4/v1/google-ads/conversions' ) ); ?>',
				},
				nonce: '<?php echo wp_create_nonce( 'wp_rest' ); ?>',
				pro_features: <?php echo wp_json_encode( $this->get_pro_features() ); ?>,
				help_links: {
					conversion_id: 'https://support.google.com/google-ads/answer/7548399',
					conversion_label: 'https://support.google.com/google-ads/answer/6095821',
					setup_guide: 'https://hasthemes.com/docs/ht-easy-ga4/google-ads-setup',
				},
				upgrade_url: '<?php echo esc_url( $this->get_upgrade_url() ); ?>',
			};
		</script>
		<?php
	}

	/**
	 * Get pro features list
	 */
	private function get_pro_features() {
		return array(
			array(
				'title' => __( 'Enhanced Conversions', 'ht-easy-ga4' ),
				'description' => __( 'Get 30% better match rates with privacy-safe hashed customer data', 'ht-easy-ga4' ),
				'icon' => 'shield-check',
			),
			array(
				'title' => __( 'One-Click Setup', 'ht-easy-ga4' ),
				'description' => __( 'Automatic Google Ads authentication and configuration', 'ht-easy-ga4' ),
				'icon' => 'link',
			),
			array(
				'title' => __( 'Server-Side Tracking', 'ht-easy-ga4' ),
				'description' => __( 'Bypass ad blockers and improve tracking accuracy', 'ht-easy-ga4' ),
				'icon' => 'server',
			),
			array(
				'title' => __( 'Advanced Analytics', 'ht-easy-ga4' ),
				'description' => __( 'ROAS tracking, custom date ranges, and detailed reports', 'ht-easy-ga4' ),
				'icon' => 'chart-line',
			),
			array(
				'title' => __( 'Consent Mode v2', 'ht-easy-ga4' ),
				'description' => __( 'Full GDPR compliance with conversion modeling', 'ht-easy-ga4' ),
				'icon' => 'user-shield',
			),
			array(
				'title' => __( 'AI Optimization', 'ht-easy-ga4' ),
				'description' => __( 'AI-powered ad copy generator and campaign tips', 'ht-easy-ga4' ),
				'icon' => 'robot',
			),
		);
	}

	/**
	 * Get upgrade URL
	 */
	private function get_upgrade_url() {
		return 'https://hasthemes.com/plugins/ht-easy-google-analytics-pro/?utm_source=plugin&utm_medium=google_ads&utm_campaign=upgrade';
	}

	/**
	 * Get default settings
	 */
	public static function get_defaults() {
		return array(
			'enabled' => false,
			'conversion_id' => '',
			'conversion_label' => '',
			'track_add_to_cart' => true,
			'excluded_roles' => array( 'administrator' ),
			'debug_mode' => false,
		);
	}

	/**
	 * Sanitize settings
	 *
	 * @param array $settings Raw settings
	 * @return array Sanitized settings
	 */
	public static function sanitize( $settings ) {
		$sanitized = array();

		// Enabled
		$sanitized['enabled'] = ! empty( $settings['enabled'] );

		// Conversion ID (numbers only)
		$sanitized['conversion_id'] = preg_replace( '/[^0-9]/', '', $settings['conversion_id'] );

		// Conversion Label (alphanumeric and underscore)
		$sanitized['conversion_label'] = preg_replace( '/[^a-zA-Z0-9_-]/', '', $settings['conversion_label'] );

		// Tracking options
		$sanitized['track_add_to_cart'] = ! empty( $settings['track_add_to_cart'] );

		// Excluded roles
		if ( isset( $settings['excluded_roles'] ) && is_array( $settings['excluded_roles'] ) ) {
			$sanitized['excluded_roles'] = array_map( 'sanitize_text_field', $settings['excluded_roles'] );
		} else {
			$sanitized['excluded_roles'] = array();
		}

		// Debug mode
		$sanitized['debug_mode'] = ! empty( $settings['debug_mode'] );

		return $sanitized;
	}

	/**
	 * Validate settings
	 *
	 * @param array $settings Settings to validate
	 * @return array|WP_Error Validated settings or error
	 */
	public static function validate( $settings ) {
		$errors = array();

		// If enabled, conversion ID is required
		if ( ! empty( $settings['enabled'] ) ) {
			if ( empty( $settings['conversion_id'] ) ) {
				$errors[] = __( 'Conversion ID is required when Google Ads tracking is enabled.', 'ht-easy-ga4' );
			}

			if ( empty( $settings['conversion_label'] ) ) {
				$errors[] = __( 'Conversion Label is required for purchase tracking.', 'ht-easy-ga4' );
			}

			// Validate conversion ID format (should be 9-10 digits)
			if ( ! empty( $settings['conversion_id'] ) ) {
				if ( strlen( $settings['conversion_id'] ) < 9 || strlen( $settings['conversion_id'] ) > 10 ) {
					$errors[] = __( 'Conversion ID should be 9-10 digits.', 'ht-easy-ga4' );
				}
			}
		}

		if ( ! empty( $errors ) ) {
			return new \WP_Error( 'validation_failed', implode( ' ', $errors ), array( 'errors' => $errors ) );
		}

		return $settings;
	}

	/**
	 * Get settings help text
	 */
	public static function get_help_text() {
		return array(
			'conversion_id' => __( 'Your Google Ads Conversion ID (e.g., 123456789). Found in Google Ads > Tools > Conversions.', 'ht-easy-ga4' ),
			'conversion_label' => __( 'Your conversion label for purchase tracking (e.g., AbC-123xYz). Found in your conversion tag.', 'ht-easy-ga4' ),
			'track_add_to_cart' => __( 'Track when customers add products to cart (Basic tracking in free version).', 'ht-easy-ga4' ),
			'excluded_roles' => __( 'User roles to exclude from conversion tracking.', 'ht-easy-ga4' ),
			'debug_mode' => __( 'Enable console logging for troubleshooting conversion tracking.', 'ht-easy-ga4' ),
		);
	}

	/**
	 * Get setup instructions
	 */
	public static function get_setup_instructions() {
		return array(
			'step1' => array(
				'title' => __( 'Get Your Conversion ID', 'ht-easy-ga4' ),
				'description' => __( 'Log in to Google Ads, go to Tools & Settings > Conversions, and copy your Conversion ID.', 'ht-easy-ga4' ),
			),
			'step2' => array(
				'title' => __( 'Find Your Conversion Label', 'ht-easy-ga4' ),
				'description' => __( 'In the conversion details, find the conversion tag and copy the label (after the / in send_to parameter).', 'ht-easy-ga4' ),
			),
			'step3' => array(
				'title' => __( 'Enter Details Below', 'ht-easy-ga4' ),
				'description' => __( 'Paste your Conversion ID and Label in the fields below and save.', 'ht-easy-ga4' ),
			),
			'step4' => array(
				'title' => __( 'Test Your Setup', 'ht-easy-ga4' ),
				'description' => __( 'Use the Test Conversion button to verify tracking is working correctly.', 'ht-easy-ga4' ),
			),
		);
	}

	/**
	 * Get conversion types for free version
	 */
	public static function get_conversion_types() {
		return array(
			'purchase' => array(
				'label' => __( 'Purchase', 'ht-easy-ga4' ),
				'description' => __( 'Track completed purchases', 'ht-easy-ga4' ),
				'available' => true,
			),
			'add_to_cart' => array(
				'label' => __( 'Add to Cart', 'ht-easy-ga4' ),
				'description' => __( 'Basic cart tracking', 'ht-easy-ga4' ),
				'available' => true,
			),
			// Pro features (shown as locked)
			'begin_checkout' => array(
				'label' => __( 'Begin Checkout', 'ht-easy-ga4' ),
				'description' => __( 'Checkout initiation tracking', 'ht-easy-ga4' ),
				'available' => false,
				'pro' => true,
			),
			'view_item' => array(
				'label' => __( 'View Item', 'ht-easy-ga4' ),
				'description' => __( 'Product page views', 'ht-easy-ga4' ),
				'available' => false,
				'pro' => true,
			),
			'add_to_wishlist' => array(
				'label' => __( 'Add to Wishlist', 'ht-easy-ga4' ),
				'description' => __( 'Wishlist additions', 'ht-easy-ga4' ),
				'available' => false,
				'pro' => true,
			),
		);
	}

	/**
	 * Check if setup is complete
	 *
	 * @return bool
	 */
	public static function is_setup_complete() {
		$settings = Manager::instance()->get_settings();

		return ! empty( $settings['enabled'] ) &&
		       ! empty( $settings['conversion_id'] ) &&
		       ! empty( $settings['conversion_label'] );
	}

	/**
	 * Get setup status
	 *
	 * @return array
	 */
	public static function get_setup_status() {
		$settings = Manager::instance()->get_settings();
		$status = array();

		// Check conversion ID
		$status['conversion_id'] = ! empty( $settings['conversion_id'] );

		// Check conversion label
		$status['conversion_label'] = ! empty( $settings['conversion_label'] );

		// Check if enabled
		$status['enabled'] = ! empty( $settings['enabled'] );

		// Check if WooCommerce is active
		$status['woocommerce'] = class_exists( 'WooCommerce' );

		// Overall status
		$status['complete'] = $status['conversion_id'] && $status['conversion_label'] && $status['enabled'];

		// Get conversion count
		if ( $status['complete'] ) {
			global $wpdb;
			$table_name = $wpdb->prefix . 'htga4_conversion_log';
			$count = $wpdb->get_var( "SELECT COUNT(*) FROM $table_name WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)" );
			$status['conversions_7d'] = intval( $count );
		} else {
			$status['conversions_7d'] = 0;
		}

		return $status;
	}
}