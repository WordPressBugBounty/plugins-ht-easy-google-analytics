<?php
/**
 * Conversion Tracker Class
 *
 * Handles conversion tracking for Google Ads
 *
 * @package Ht_Easy_Ga4\GoogleAds
 * @since 1.8.0
 */

namespace Ht_Easy_Ga4\GoogleAds;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Conversion Tracker class for Google Ads
 */
class Conversion_Tracker {

	/**
	 * Settings
	 *
	 * @var array
	 */
	private $settings;

	/**
	 * Constructor
	 *
	 * @param array $settings Optional settings array to avoid circular dependency
	 */
	public function __construct( $settings = null ) {
		if ( $settings !== null ) {
			$this->settings = $settings;
		} else {
			// Fallback to getting settings from manager (backward compatibility)
			$this->settings = Manager::instance()->get_settings();
		}
		$this->init_hooks();
	}

	/**
	 * Initialize hooks
	 */
	private function init_hooks() {
		// WooCommerce conversion tracking (basic)
		if ( class_exists( 'WooCommerce' ) ) {
			// Purchase conversion - ALWAYS available
			// Primary hook: payment_complete fires immediately after successful payment
			add_action( 'woocommerce_payment_complete', array( $this, 'track_purchase_on_payment' ), 10, 1 );

			// Fallback hook: thankyou page (in case payment_complete doesn't fire)
			add_action( 'woocommerce_thankyou', array( $this, 'track_purchase' ), 10, 1 );

			// Additional hook for order status changes (catches manual orders and delayed payments)
			add_action( 'woocommerce_order_status_completed', array( $this, 'track_purchase_on_status_change' ), 10, 1 );
			add_action( 'woocommerce_order_status_processing', array( $this, 'track_purchase_on_status_change' ), 10, 1 );

			// Output delayed tracking script if needed
			add_action( 'wp_footer', array( $this, 'output_delayed_tracking_script' ), 20 );
		}

		// AJAX handler for clearing pending conversions
		add_action( 'wp_ajax_htga4_clear_pending_conversions', array( $this, 'ajax_clear_pending_conversions' ) );
	}

	/**
	 * Track purchase on payment complete (primary method)
	 *
	 * @param int $order_id Order ID
	 */
	public function track_purchase_on_payment( $order_id ) {
		$this->prepare_conversion_tracking( $order_id, 'payment_complete' );
	}

	/**
	 * Track purchase on status change (backup method)
	 *
	 * @param int $order_id Order ID
	 */
	public function track_purchase_on_status_change( $order_id ) {
		$this->prepare_conversion_tracking( $order_id, 'status_change' );
	}

	/**
	 * Track purchase conversion (fallback for thank you page)
	 *
	 * @param int $order_id Order ID
	 */
	public function track_purchase( $order_id ) {
		if ( ! $order_id || empty( $this->settings['conversion_id'] ) ) {
			return;
		}

		// Check if already tracked
		if ( get_post_meta( $order_id, '_htga4_conversion_tracked', true ) ) {
			return;
		}

		// Get rich purchase data using shared helper function
		$purchase_data = htga4_get_purchase_data( $order_id );

		if ( ! $purchase_data ) {
			return;
		}

		// Output conversion tracking script with rich data (immediate on thank you page)
		$this->output_purchase_script( $purchase_data );

		// Mark as tracked
		update_post_meta( $order_id, '_htga4_conversion_tracked', true );
	}

	/**
	 * Prepare conversion tracking data for delayed execution
	 *
	 * @param int $order_id Order ID
	 * @param string $trigger_source Source of the trigger
	 */
	private function prepare_conversion_tracking( $order_id, $trigger_source = 'unknown' ) {
		if ( ! $order_id || empty( $this->settings['conversion_id'] ) ) {
			return;
		}

		// Check if already tracked
		if ( get_post_meta( $order_id, '_htga4_conversion_tracked', true ) ) {
			return;
		}

		// Get rich purchase data using shared helper function
		$purchase_data = htga4_get_purchase_data( $order_id );

		if ( ! $purchase_data ) {
			return;
		}

		// Add tracking metadata
		$purchase_data['order_id'] = $order_id;
		$purchase_data['timestamp'] = current_time( 'timestamp' );
		$purchase_data['trigger_source'] = $trigger_source;

		// Store as order meta for delayed tracking
		update_post_meta( $order_id, '_htga4_pending_conversion', $purchase_data );

		// Mark order as tracked to prevent duplicate tracking on status changes
		update_post_meta( $order_id, '_htga4_conversion_tracked', true );

		// If customer is logged in, store in user meta for next visit
		$order = wc_get_order( $order_id );
		$customer_id = $order->get_customer_id();
		if ( $customer_id ) {
			$pending_conversions = get_user_meta( $customer_id, '_htga4_pending_conversions', true );
			if ( ! is_array( $pending_conversions ) ) {
				$pending_conversions = array();
			}
			$pending_conversions[ $order_id ] = $purchase_data;
			update_user_meta( $customer_id, '_htga4_pending_conversions', $pending_conversions );
		}
	}

	/**
	 * Get conversion label for specific event type
	 *
	 * @param string $event_type Event type (purchase, add_to_cart, checkout, view_product, view_category)
	 * @return string|false Conversion label or false if not configured
	 */
	private function get_conversion_label( $event_type ) {
		// Pro-only events (only purchase is free)
		$pro_events = array( 'add_to_cart', 'checkout', 'view_product', 'view_category' );

		// Check if this is a pro event and pro is not active
		if ( in_array( $event_type, $pro_events, true ) && ! Manager::is_pro_available() ) {
			return false;
		}

		// Check if event is enabled and has a label
		if ( isset( $this->settings['conversion_labels'][ $event_type ] ) ) {
			$config = $this->settings['conversion_labels'][ $event_type ];

			// Return label only if event is enabled and label is not empty
			if ( ! empty( $config['enabled'] ) && ! empty( $config['label'] ) ) {
				return $config['label'];
			}
		}

		return false;
	}

	/**
	 * Output purchase conversion script with rich data
	 *
	 * @param array $purchase_data Purchase data with items
	 */
	private function output_purchase_script( $purchase_data ) {
		$conversion_label = $this->get_conversion_label( 'purchase' );

		if ( empty( $conversion_label ) ) {
			return;
		}

		$conversion_id = 'AW-' . esc_js( $this->settings['conversion_id'] );
		$conversion_label = esc_js( $conversion_label );

		// Use items directly from purchase_data (GA4 format is compatible with Google Ads)
		$items = ! empty( $purchase_data['items'] ) ? $purchase_data['items'] : array();

		?>
		<!-- Google Ads Purchase Conversion - HT Easy GA4 -->
		<script>
			(function() {
				// Wait for gtag to be available
				function trackConversion() {
					if (typeof gtag !== 'undefined') {
						gtag('event', 'conversion', {
							'send_to': '<?php echo $conversion_id; ?>/<?php echo $conversion_label; ?>',
							'transaction_id': '<?php echo esc_js( $purchase_data['transaction_id'] ); ?>',
							'value': <?php echo floatval( $purchase_data['value'] ); ?>,
							'currency': '<?php echo esc_js( $purchase_data['currency'] ); ?>',
							'tax': <?php echo floatval( $purchase_data['tax'] ); ?>,
							'shipping': <?php echo floatval( $purchase_data['shipping'] ); ?>,
							'coupon': '<?php echo esc_js( $purchase_data['coupon'] ); ?>'<?php if ( ! empty( $items ) ) : ?>,
							'items': <?php echo wp_json_encode( $items ); ?>
							<?php endif; ?>
						});

						<?php if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) : ?>
						console.log('Google Ads Conversion tracked:', {
							conversion_id: '<?php echo $conversion_id; ?>',
							label: '<?php echo $conversion_label; ?>',
							transaction_id: '<?php echo esc_js( $purchase_data['transaction_id'] ); ?>',
							value: <?php echo floatval( $purchase_data['value'] ); ?>,
							currency: '<?php echo esc_js( $purchase_data['currency'] ); ?>',
							tax: <?php echo floatval( $purchase_data['tax'] ); ?>,
							shipping: <?php echo floatval( $purchase_data['shipping'] ); ?>,
							items_count: <?php echo count( $items ); ?>
						});
						<?php endif; ?>
					} else {
						// Retry if gtag not available yet
						setTimeout(trackConversion, 500);
					}
				}

				// Start tracking
				trackConversion();
			})();
		</script>
		<!-- End Google Ads Purchase Conversion -->
		<?php
	}

	/**
	 * AJAX handler for clearing pending conversions
	 */
	public function ajax_clear_pending_conversions() {
		// Verify nonce for security
		check_ajax_referer( 'htga4_clear_pending_conversions', 'nonce' );

		// Verify user is logged in
		if ( ! is_user_logged_in() ) {
			wp_send_json_error( 'User not logged in' );
		}

		$user_id = get_current_user_id();

		// Clear pending conversions from user meta
		delete_user_meta( $user_id, '_htga4_pending_conversions' );

		wp_send_json_success( array(
			'cleared' => true,
			'user_id' => $user_id,
		) );
	}

	/**
	 * Output delayed tracking script for pending conversions
	 */
	public function output_delayed_tracking_script() {
		// Only output on frontend pages
		if ( is_admin() || empty( $this->settings['conversion_id'] ) ) {
			return;
		}

		// Check for logged-in users with pending conversions
		$pending_conversions = array();

		if ( is_user_logged_in() ) {
			$user_id = get_current_user_id();
			$user_pending = get_user_meta( $user_id, '_htga4_pending_conversions', true );

			if ( is_array( $user_pending ) && ! empty( $user_pending ) ) {
				$pending_conversions = $user_pending;
			}
		}

		// Also check for guest conversions via session/cookie
		if ( isset( $_COOKIE['htga4_pending_conversions'] ) ) {
			$cookie_conversions = json_decode( stripslashes( $_COOKIE['htga4_pending_conversions'] ), true );
			if ( is_array( $cookie_conversions ) ) {
				$pending_conversions = array_merge( $pending_conversions, $cookie_conversions );
			}
		}

		if ( empty( $pending_conversions ) ) {
			return;
		}

		$conversion_id = 'AW-' . esc_js( $this->settings['conversion_id'] );
		// Get purchase label for legacy delayed conversions
		$purchase_label = $this->get_conversion_label( 'purchase' );

		?>
		<!-- Google Ads Delayed Conversion Tracking - HT Easy GA4 -->
		<script>
			(function() {
				// Wait for gtag to be available
				function trackDelayedConversions() {
					if (typeof gtag !== 'undefined') {
						<?php if ( ! empty( $pending_conversions ) && ! empty( $purchase_label ) ) : ?>
						// Track pending purchase conversions with full GA4-compatible data
						var pendingConversions = <?php echo wp_json_encode( $pending_conversions ); ?>;

						for (var orderId in pendingConversions) {
							if (pendingConversions.hasOwnProperty(orderId)) {
								var conversion = pendingConversions[orderId];

								// Prepare conversion data (same structure as GA4)
								var conversionData = {
									'send_to': '<?php echo $conversion_id; ?>/<?php echo esc_js( $purchase_label ); ?>',
									'transaction_id': conversion.transaction_id,
									'value': parseFloat(conversion.value),
									'currency': conversion.currency,
									'tax': parseFloat(conversion.tax || 0),
									'shipping': parseFloat(conversion.shipping || 0),
									'coupon': conversion.coupon || ''
								};

								// Add items if available (use GA4 format directly)
								if (conversion.items && Array.isArray(conversion.items) && conversion.items.length > 0) {
									conversionData.items = conversion.items;
								}

								// Send conversion to Google Ads
								gtag('event', 'conversion', conversionData);

								<?php if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) : ?>
								console.log('Google Ads Delayed Conversion tracked:', conversionData);
								<?php endif; ?>
							}
						}
						<?php endif; ?>


						// Clear pending conversions
						<?php if ( is_user_logged_in() && ! empty( $pending_conversions ) ) : ?>
						// Clear user meta via AJAX
						if (typeof jQuery !== 'undefined') {
							jQuery.ajax({
								url: '<?php echo admin_url( 'admin-ajax.php' ); ?>',
								type: 'POST',
								data: {
									action: 'htga4_clear_pending_conversions',
									nonce: '<?php echo wp_create_nonce( 'htga4_clear_pending_conversions' ); ?>',
									user_id: <?php echo get_current_user_id(); ?>
								}
							});
						}
						<?php endif; ?>

						// Clear cookie for guest users
						document.cookie = 'htga4_pending_conversions=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;';
					} else {
						// Retry if gtag not available
						setTimeout(trackDelayedConversions, 1000);
					}
				}

				// Start delayed tracking
				setTimeout(trackDelayedConversions, 2000);
			})();
		</script>
		<?php
	}
}