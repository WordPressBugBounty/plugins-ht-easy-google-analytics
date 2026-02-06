<?php
/**
 * Server-Side Tracking Class
 *
 * Handles server-side event tracking via GA4 Measurement Protocol
 *
 * @package Ht_Easy_Ga4\ServerSide
 * @since 1.9.0
 */

namespace Ht_Easy_Ga4\ServerSide;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Server_Side_Tracking class
 */
class Server_Side_Tracking {

	/**
	 * Singleton instance
	 *
	 * @var Server_Side_Tracking
	 */
	private static $instance = null;

	/**
	 * Measurement Protocol client
	 *
	 * @var Measurement_Protocol
	 */
	protected $mp_client;

	/**
	 * Get singleton instance
	 *
	 * @return Server_Side_Tracking
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
	protected function __construct() {
		// Only initialize if properly configured
		if ( ! $this->is_configured() ) {
			return;
		}

		// Only initialize if tracking is enabled
		if ( ! htga4_get_option( 'server_side_tracking', false ) ) {
			return;
		}

		$this->init_mp_client();
		$this->init_hooks();
	}

	/**
	 * Initialize Measurement Protocol client
	 */
	protected function init_mp_client() {
		$measurement_id = htga4_get_measurement_id();
		$api_secret     = htga4_get_option( 'measurement_protocol_api_secret', '' );

		if ( empty( $api_secret ) ) {
			$api_secret = htga4_get_option( 'measurement_protocol_api_secret_select', '' );
		}

		$this->mp_client = new Measurement_Protocol( $measurement_id, $api_secret );
	}

	/**
	 * Initialize WordPress hooks
	 */
	protected function init_hooks() {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}

		// WooCommerce purchase tracking - use order status changed hook to catch all payment completions
		add_action( 'woocommerce_order_status_changed', array( $this, 'track_on_status_change' ), 10, 4 );
	}

	/**
	 * Check if server-side tracking is properly configured
	 *
	 * @return bool True if configured
	 */
	protected function is_configured() {
		$measurement_id = htga4_get_measurement_id();
		if ( empty( $measurement_id ) ) {
			return false;
		}

		$api_secret = htga4_get_option( 'measurement_protocol_api_secret', '' );
		if ( empty( $api_secret ) ) {
			$api_secret = htga4_get_option( 'measurement_protocol_api_secret_select', '' );
		}

		if ( empty( $api_secret ) ) {
			return false;
		}

		// Check if WooCommerce is active
		if ( ! class_exists( 'WooCommerce' ) ) {
			return false;
		}

		return true;
	}


	/**
	 * Track purchase on order status change
	 *
	 * @param int    $order_id   Order ID.
	 * @param string $old_status Old status.
	 * @param string $new_status New status.
	 * @param object $order      Order object.
	 */
	public function track_on_status_change( $order_id, $old_status, $new_status, $order ) {
		// Only track when transitioning to a paid status
		$paid_statuses = array( 'processing', 'completed' );

		// Check if new status is a paid status and old status wasn't
		if ( in_array( $new_status, $paid_statuses, true ) && ! in_array( $old_status, $paid_statuses, true ) ) {
			$this->track_purchase( $order_id );
		}
	}

	/**
	 * Track purchase event
	 *
	 * @param int $order_id WooCommerce order ID.
	 */
	public function track_purchase( $order_id ) {
		// Check if already tracked (read-only check first for performance)
		if ( get_post_meta( $order_id, '_htga4_server_side_tracked', true ) ) {
			return;
		}

		// Check if user should be excluded from tracking
		if ( $this->should_exclude_user() ) {
			return;
		}

		// Get order
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			error_log( '[HT GA4 Server-Side] Failed to get order #' . $order_id );
			return;
		}

		// Get purchase data from shared helper function
		$purchase_data = htga4_get_purchase_data( $order_id );

		if ( ! $purchase_data ) {
			error_log( '[HT GA4 Server-Side] Failed to get purchase data for order #' . $order_id );
			return;
		}

		// Ensure item_id is string for Measurement Protocol
		if ( ! empty( $purchase_data['items'] ) ) {
			foreach ( $purchase_data['items'] as $index => $item ) {
				$purchase_data['items'][ $index ]['item_id'] = (string) $item['item_id'];

				// Remove discount field (not used in server-side tracking)
				unset( $purchase_data['items'][ $index ]['discount'] );
			}
		}

		// Ensure transaction_id is string for Measurement Protocol
		$purchase_data['transaction_id'] = (string) $purchase_data['transaction_id'];

		// Allow Pro version to modify purchase data
		$purchase_data = apply_filters( 'htga4_server_side_purchase_data', $purchase_data, $order_id, $order );

		// Build context
		$context = array(
			'order'    => $order,
			'order_id' => $order_id,
		);

		// Send to GA4 using simplified API
		$result = $this->mp_client->send_event_simple(
			'purchase',
			$purchase_data,
			$context
		);

		// Only set flag AFTER successful tracking (prevents race condition issues)
		if ( $result['success'] ) {
			// Use unique=true to prevent duplicate flags in race conditions
			add_post_meta( $order_id, '_htga4_server_side_tracked', time(), true );
		} else {
			error_log( '[HT GA4 Server-Side] Failed to track order #' . $order_id . ': ' . ( $result['error'] ?? 'Unknown error' ) );
		}
	}

	/**
	 * Check if current user should be excluded from tracking
	 *
	 * @return bool True if should exclude
	 */
	protected function should_exclude_user() {
		// Get excluded roles from settings
		$exclude_roles = htga4_get_option( 'exclude_roles', array() );

		if ( empty( $exclude_roles ) || ! is_array( $exclude_roles ) ) {
			return false;
		}

		// Check if user has excluded role
		if ( ! is_user_logged_in() ) {
			return false;
		}

		$user       = wp_get_current_user();
		$user_roles = $user->roles;

		foreach ( $user_roles as $role ) {
			if ( in_array( $role, $exclude_roles, true ) ) {
				return true;
			}
		}

		return false;
	}
}
