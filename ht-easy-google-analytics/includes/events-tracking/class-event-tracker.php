<?php
/**
 * Event Tracker Class
 *
 * Handles basic event tracking - Purchase event only
 *
 * @package Ht_Easy_Ga4\EventsTracking
 * @since 1.8.0
 */

namespace Ht_Easy_Ga4\EventsTracking;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Event Tracker class for Events Tracking
 */
class Event_Tracker {
	use \Ht_Easy_Ga4\Helper_Trait;

	/**
	 * Instance
	 *
	 * @var Event_Tracker
	 */
	private static $instance = null;

	/**
	 * Get instance
	 *
	 * @return Event_Tracker
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
	private function __construct() {
		add_action( 'wp_footer', array( $this, 'render_data_layer_script' ), 200 );
	}

	/**
	 * Render data layer script for purchase event
	 */
	public function render_data_layer_script() {
		// Only handle purchase event
		if ( is_order_received_page() && htga4_get_option( 'purchase_event' ) ) {
			$order = $this->get_verified_order();

			if ( ! $order ) {
				return;
			}

			// Payment declined: don't track and don't flag, so a successful retry is still tracked.
			if ( $order->has_status( 'failed' ) ) {
				return;
			}

			$order_id = $order->get_id();

			// Skip client-side tracking if server-side already handled this order
			if ( $this->should_skip_client_tracking( $order_id ) ) {
				return;
			}

			// Send the purchase event only once per order (page reloads, revisits)
			if ( $order->get_meta( '_htga4_purchase_sent' ) ) {
				return;
			}

			// Use shared helper function for consistency with Google Ads
			$datalayer = htga4_get_purchase_data( $order_id );

			if ( ! $datalayer ) {
				return;
			}

			$this->render_gtag_script( 'purchase', $datalayer );

			// Save only the flag, not the whole order (avoids firing order-save hooks on the thank-you page).
			$order->update_meta_data( '_htga4_purchase_sent', time() );
			$order->save_meta_data();
		}
	}

	/**
	 * Get the order shown on the order-received page, only if the order key in the URL matches.
	 *
	 * @return \WC_Order|false
	 */
	protected function get_verified_order() {
		$order_id = isset( $GLOBALS['wp']->query_vars['order-received'] ) ? absint( $GLOBALS['wp']->query_vars['order-received'] ) : 0;

		if ( ! $order_id ) {
			return false;
		}

		$order = wc_get_order( $order_id );
		$key   = isset( $_GET['key'] ) ? wc_clean( wp_unslash( $_GET['key'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( ! $order || ! $key || ! $order->key_is_valid( $key ) ) {
			return false;
		}

		return $order;
	}

	/**
	 * Check if client-side tracking should be skipped for this order
	 *
	 * @param int $order_id Order ID
	 * @return bool True if should skip client-side tracking
	 */
	protected function should_skip_client_tracking( $order_id ) {
		// If server-side tracking is enabled, always skip client-side
		if ( htga4_get_option( 'server_side_tracking', false ) ) {
			return true; // Trust server-side to handle it
		}

		// If server-side is disabled, check if already tracked (safety check)
		if ( get_post_meta( $order_id, '_htga4_server_side_tracked', true ) ) {
			return true; // Already tracked, skip
		}

		return false;
	}

	/**
	 * Render gtag script
	 *
	 * @param string $event_name Event name
	 * @param array  $datalayer  Data layer
	 */
	public function render_gtag_script( $event_name, $datalayer ) {
		if ( ! $datalayer ) {
			return;
		}

		$html5_support = current_theme_supports( 'html5' );

		if ( version_compare( PHP_VERSION, '5.4.0' ) >= 0 ) {
			$datalayer_json = json_encode( $datalayer, JSON_UNESCAPED_UNICODE );
		} else {
			$datalayer_json = json_encode( $datalayer );
		}
		?>
		<script <?php echo ( $html5_support ? 'type="text/javascript"' : '' ); ?>>
			var ga4_datalayer_obj = <?php echo wp_kses_post( $datalayer_json ); ?>;
			gtag("event", '<?php echo esc_attr( $event_name ); ?>' , ga4_datalayer_obj);
		</script>
		<?php
	}
}
