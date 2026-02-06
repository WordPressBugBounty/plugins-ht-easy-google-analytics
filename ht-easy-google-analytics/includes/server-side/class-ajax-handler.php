<?php
/**
 * AJAX Handler for Custom Events Server-Side Tracking
 *
 * @package Ht_Easy_Ga4\ServerSide
 * @since 1.9.0
 */

namespace Ht_Easy_Ga4\ServerSide;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Ajax_Handler class
 */
class Ajax_Handler {

	/**
	 * Singleton instance
	 *
	 * @var Ajax_Handler
	 */
	private static $instance = null;

	/**
	 * Get singleton instance
	 *
	 * @return Ajax_Handler
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
		$this->init_hooks();
	}

	/**
	 * Initialize WordPress hooks
	 */
	private function init_hooks() {
		// Register custom event AJAX action for both logged-in and non-logged-in users
		add_action( 'wp_ajax_htga4_custom_event_ajax_action', array( $this, 'custom_event_ajax_action' ) );
		add_action( 'wp_ajax_nopriv_htga4_custom_event_ajax_action', array( $this, 'custom_event_ajax_action' ) );
	}

	/**
	 * Handle custom event AJAX action
	 *
	 * Processes custom events via AJAX and routes to server-side or client-side tracking
	 * based on server_side_tracking setting.
	 *
	 * @since 1.9.0
	 */
	public function custom_event_ajax_action() {
		// Verify nonce
		check_ajax_referer( 'htga4_nonce', 'nonce' );

		$post_data = wp_unslash( $_POST );

		// Sanitize input data
		$event_name   = sanitize_text_field( $post_data['event_name'] ?? '' );

		// Safely decode JSON with error handling
		$event_params = array();
		if ( isset( $post_data['event_params'] ) && is_string( $post_data['event_params'] ) ) {
			$decoded = json_decode( $post_data['event_params'], true );
			if ( json_last_error() === JSON_ERROR_NONE && is_array( $decoded ) ) {
				$event_params = $decoded;
			}
		}

		// Validate event name
		if ( empty( $event_name ) ) {
			wp_send_json_error( 'Event name is required' );
			return;
		}

		// Recursively sanitize all parameters
		$data_layer = $this->sanitize_params( $event_params );

		// Skip client-side response if server-side tracking is enabled
		if ( htga4_get_option( 'server_side_tracking', false ) ) {
			$result = $this->track_event_server_side( $event_name, $data_layer );

			if ( $result['success'] ) {
				wp_send_json_success( array(
					'tracked' => 'server',
					'data'    => $data_layer,
				) );
			} else {
				error_log( '[HT GA4 Server-Side] Failed to track custom event ' . $event_name . ': ' . ( $result['error'] ?? 'Unknown error' ) );
				wp_send_json_error( array(
					'message' => 'Failed to track custom event',
					'error'   => $result['error'] ?? 'Unknown error',
				) );
			}
			return;
		}

		// Return data for client-side tracking
		wp_send_json_success( array(
			'tracked' => 'client',
			'data'    => $data_layer,
		) );
	}

	/**
	 * Track event via server-side Measurement Protocol
	 *
	 * @param string $event_name Event name.
	 * @param array  $data_layer Event parameters.
	 * @return array Response with 'success' boolean and optional 'error' message.
	 */
	private function track_event_server_side( $event_name, $data_layer ) {
		// Check if Measurement Protocol is configured
		$measurement_id = htga4_get_measurement_id();
		$api_secret     = htga4_get_option( 'measurement_protocol_api_secret', '' );

		if ( empty( $api_secret ) ) {
			$api_secret = htga4_get_option( 'measurement_protocol_api_secret_select', '' );
		}

		if ( empty( $measurement_id ) || empty( $api_secret ) ) {
			return array(
				'success' => false,
				'error'   => 'Measurement Protocol not configured',
			);
		}

		// Initialize Measurement Protocol client
		$mp_client = new Measurement_Protocol( $measurement_id, $api_secret );

		// Build context for debugging
		$context = array(
			'event_type' => 'custom',
			'event_name' => $event_name,
		);

		// Send to GA4 using simplified API
		return $mp_client->send_event_simple( $event_name, $data_layer, $context );
	}

	/**
	 * Recursively sanitize parameters with depth limit
	 *
	 * @param mixed $value Value to sanitize.
	 * @param int   $depth Current recursion depth.
	 * @return mixed Sanitized value.
	 */
	private function sanitize_params( $value, $depth = 0 ) {
		// Prevent infinite recursion - max 10 levels deep
		if ( $depth > 10 ) {
			return is_array( $value ) ? array() : '';
		}

		if ( is_array( $value ) ) {
			$sanitized = array();
			foreach ( $value as $key => $val ) {
				$sanitized[ sanitize_key( $key ) ] = $this->sanitize_params( $val, $depth + 1 );
			}
			return $sanitized;
		}

		if ( is_numeric( $value ) ) {
			return $value;
		}

		return sanitize_text_field( $value );
	}

}
