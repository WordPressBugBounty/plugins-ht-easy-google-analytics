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
			// Get order ID
			$order_id = empty( $_GET['order'] ) ? ( $GLOBALS['wp']->query_vars['order-received'] ? $GLOBALS['wp']->query_vars['order-received'] : 0 ) : absint( $_GET['order'] ); // phpcs:ignore

			// Skip client-side tracking if server-side already handled this order
			if ( $this->should_skip_client_tracking( $order_id ) ) {
				return;
			}

			// Use shared helper function for consistency with Google Ads
			$datalayer = htga4_get_purchase_data( $order_id );

			if ( ! $datalayer ) {
				return;
			}

			$this->render_gtag_script( 'purchase', $datalayer );
		}
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
