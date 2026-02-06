<?php
/**
 * GA4 Measurement Protocol API Client
 *
 * Handles communication with Google Analytics 4 Measurement Protocol
 *
 * @package Ht_Easy_Ga4\ServerSide
 * @since 1.9.0
 */

namespace Ht_Easy_Ga4\ServerSide;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Measurement Protocol class
 */
class Measurement_Protocol {

	/**
	 * GA4 Measurement ID
	 *
	 * @var string
	 */
	private $measurement_id;

	/**
	 * Measurement Protocol API Secret
	 *
	 * @var string
	 */
	private $api_secret;

	/**
	 * Constructor
	 *
	 * @param string $measurement_id GA4 Measurement ID.
	 * @param string $api_secret     Measurement Protocol API Secret.
	 */
	public function __construct( $measurement_id, $api_secret ) {
		$this->measurement_id = $measurement_id;
		$this->api_secret     = $api_secret;

		// Validate measurement ID format
		if ( ! empty( $measurement_id ) && ! $this->is_valid_measurement_id( $measurement_id ) ) {
			$this->log_error( 'Invalid measurement ID format: ' . $measurement_id . '. Expected format: G-XXXXXXXXXX' );
		}
	}

	/**
	 * Validate GA4 Measurement ID format
	 *
	 * @param string $measurement_id Measurement ID to validate.
	 * @return bool True if valid format
	 */
	public function is_valid_measurement_id( $measurement_id ) {
		// GA4 measurement IDs start with G- followed by alphanumeric characters
		return (bool) preg_match( '/^G-[A-Z0-9]+$/i', $measurement_id );
	}

	/**
	 * Send event to GA4
	 *
	 * @param array $payload Event payload.
	 * @return array Response with success status and data
	 */
	public function send_event( $payload ) {
		// Validate API credentials
		if ( empty( $this->api_secret ) ) {
			$this->log_error( 'API secret is not configured' );
			return array(
				'success' => false,
				'error'   => 'API secret not configured',
			);
		}

		// Validate payload
		if ( empty( $payload['client_id'] ) ) {
			$this->log_error( 'Missing client_id in payload' );
			return array(
				'success' => false,
				'error'   => 'Missing client_id',
			);
		}

		if ( empty( $payload['events'] ) || ! is_array( $payload['events'] ) ) {
			$this->log_error( 'Missing or invalid events array' );
			return array(
				'success' => false,
				'error'   => 'Invalid events',
			);
		}

		// Build API URL
		$url = $this->get_api_url();

		// Prepare request body
		$body = wp_json_encode( $payload );

		// Send HTTP POST request
		$response = wp_remote_post(
			$url,
			array(
				'timeout'   => 5,
				'headers'   => array(
					'Content-Type' => 'application/json',
				),
				'body'      => $body,
				'sslverify' => true,
			)
		);

		// Handle WP errors
		if ( is_wp_error( $response ) ) {
			$error_message = $response->get_error_message();
			$this->log_error( 'HTTP request failed: ' . $error_message );

			return array(
				'success' => false,
				'error'   => $error_message,
			);
		}

		// Check HTTP response code
		$response_code = wp_remote_retrieve_response_code( $response );
		if ( $response_code < 200 || $response_code >= 300 ) {
			$response_body = wp_remote_retrieve_body( $response );
			$this->log_error( 'GA4 API error (HTTP ' . $response_code . '): ' . $response_body );

			return array(
				'success' => false,
				'error'   => 'HTTP ' . $response_code,
				'body'    => $response_body,
			);
		}

		return array(
			'success' => true,
			'code'    => $response_code,
		);
	}

	/**
	 * Send event to GA4 (simplified API)
	 *
	 * Automatically handles client_id, user_id, timestamp, and payload structure.
	 * Callers only need to specify event name and parameters.
	 *
	 * @param string $event_name   Event name (e.g., 'purchase', 'add_to_cart').
	 * @param array  $event_params Event parameters.
	 * @param array  $context      Optional context (order, user_id, timestamp).
	 * @return array Response with success status and data
	 */
	public function send_event_simple( $event_name, $event_params, $context = array() ) {
		// Get or generate client_id
		$client_id = $this->get_client_id( $context );

		// Add engagement_time_msec for better event processing (allow override via event_params)
		$enhanced_params = array_merge(
			array(
				'engagement_time_msec' => 100, // Default value, can be overridden
			),
			$event_params
		);

		// Add session_id if available
		$session_id = $this->get_session_id( $context );
		if ( $session_id ) {
			$enhanced_params['session_id'] = $session_id;
		}

		// Build base payload
		$payload = array(
			'client_id' => $client_id,
			'events'    => array(
				array(
					'name'   => $event_name,
					'params' => $enhanced_params,
				),
			),
		);

		// Add user_id if available
		$user_id = $this->get_user_id( $context );
		if ( $user_id ) {
			$payload['user_id'] = $user_id;
		}

		// Add timestamp
		$payload['timestamp_micros'] = $this->get_timestamp_micros( $context );

		// Allow filtering for Pro extensibility
		$payload = apply_filters(
			'htga4_mp_payload',
			$payload,
			$event_name,
			$event_params,
			$context
		);

		// Use existing send_event() method
		return $this->send_event( $payload );
	}

	/**
	 * Get or generate client ID
	 *
	 * GA4 client_id format: RANDOM_NUMBER.TIMESTAMP (e.g., 1234567890.1699999999)
	 * This matches the format used by gtag.js in the _ga cookie.
	 *
	 * @param array $context Context data.
	 * @return string Client ID
	 */
	protected function get_client_id( $context = array() ) {
		// Allow override via context
		if ( ! empty( $context['client_id'] ) ) {
			$client_id = $context['client_id'];
			return apply_filters( 'htga4_mp_client_id', $client_id, $context );
		}

		// Try to read from _ga cookie first
		if ( isset( $_COOKIE['_ga'] ) ) {
			$ga_cookie = sanitize_text_field( wp_unslash( $_COOKIE['_ga'] ) );

			// GA4 cookie format: GA1.1.RANDOM.TIMESTAMP or GA1.2.RANDOM.TIMESTAMP
			// The client_id is RANDOM.TIMESTAMP (last two parts)
			if ( preg_match( '/^GA\d\.\d\.(\d+\.\d+)$/', $ga_cookie, $matches ) ) {
				$client_id = $matches[1];
				return apply_filters( 'htga4_mp_client_id', $client_id, $context );
			}

			// Log unexpected format for debugging
			$this->log_error( 'Unexpected _ga cookie format: ' . $ga_cookie );
		}

		// Generate new client ID
		$client_id = $this->generate_client_id();
		return apply_filters( 'htga4_mp_client_id', $client_id, $context );
	}

	/**
	 * Generate new client ID
	 *
	 * @return string Client ID
	 */
	protected function generate_client_id() {
		$random    = wp_rand( 1000000000, 9999999999 );
		$timestamp = time();

		return $random . '.' . $timestamp;
	}

	/**
	 * Get session ID from GA4 cookie
	 *
	 * @param array $context Context data.
	 * @return string|null Session ID or null if not found
	 */
	protected function get_session_id( $context = array() ) {
		// Allow override via context
		if ( ! empty( $context['session_id'] ) ) {
			return $context['session_id'];
		}

		// Get measurement ID to build cookie name
		$measurement_id = $this->measurement_id;
		if ( empty( $measurement_id ) ) {
			return null;
		}

		// Build cookie name: _ga_XXXXXXXXXX (without G- prefix)
		$measurement_id_clean = str_replace( 'G-', '', $measurement_id );
		$cookie_name = '_ga_' . $measurement_id_clean;

		// Try to read from cookie
		if ( ! isset( $_COOKIE[ $cookie_name ] ) ) {
			return null;
		}

		$cookie_value = sanitize_text_field( wp_unslash( $_COOKIE[ $cookie_name ] ) );

		// Try dot-separated format: GS1.1.SESSION_ID.TIMESTAMP.COUNT
		if ( preg_match( '/^GS\d\.\d\.(\d+)\./', $cookie_value, $matches ) ) {
			return $matches[1];
		}

		// Try dollar-separated format: GS1.1$sSESSION_ID...
		if ( preg_match( '/\$s(\d+)/', $cookie_value, $matches ) ) {
			return $matches[1];
		}

		// Log unexpected format for debugging (only if cookie exists but format is unrecognized)
		$this->log_error( 'Could not extract session_id from cookie ' . $cookie_name . ': ' . $cookie_value );

		return null;
	}

	/**
	 * Get user ID if available
	 *
	 * @param array $context Context data.
	 * @return string|null WordPress user ID or null
	 */
	protected function get_user_id( $context = array() ) {
		// Allow override via context
		if ( isset( $context['user_id'] ) ) {
			$user_id = $context['user_id'];
			return apply_filters( 'htga4_mp_user_id', $user_id, $context );
		}

		// Check if user is logged in
		if ( is_user_logged_in() ) {
			$user_id = 'user_' . get_current_user_id();
			return apply_filters( 'htga4_mp_user_id', $user_id, $context );
		}

		// Check if order has customer ID
		if ( ! empty( $context['order'] ) && is_a( $context['order'], 'WC_Order' ) ) {
			$customer_id = $context['order']->get_customer_id();
			if ( $customer_id ) {
				$user_id = 'user_' . $customer_id;
				return apply_filters( 'htga4_mp_user_id', $user_id, $context );
			}
		}

		return apply_filters( 'htga4_mp_user_id', null, $context );
	}

	/**
	 * Get timestamp in microseconds
	 *
	 * @param array $context Context data.
	 * @return string Timestamp in microseconds
	 */
	protected function get_timestamp_micros( $context = array() ) {
		// Use order creation time if available
		if ( ! empty( $context['order'] ) && is_a( $context['order'], 'WC_Order' ) ) {
			$order_date = $context['order']->get_date_created();
			if ( $order_date ) {
				return (string) ( $order_date->getTimestamp() * 1000000 );
			}
		}

		// Use custom timestamp if provided
		if ( ! empty( $context['timestamp'] ) ) {
			// Handle numeric timestamp
			if ( is_numeric( $context['timestamp'] ) ) {
				return (string) ( $context['timestamp'] * 1000000 );
			}

			// Handle WC_DateTime object
			if ( is_a( $context['timestamp'], 'WC_DateTime' ) ) {
				return (string) ( $context['timestamp']->getTimestamp() * 1000000 );
			}
		}

		// Use current time
		return (string) ( microtime( true ) * 1000000 );
	}

	/**
	 * Get API endpoint URL
	 *
	 * @return string Full API URL with query parameters
	 */
	private function get_api_url() {
		$base_url = 'https://www.google-analytics.com/mp/collect';

		// Add query parameters
		$url = add_query_arg(
			array(
				'measurement_id' => $this->measurement_id,
				'api_secret'     => $this->api_secret,
			),
			$base_url
		);

		return $url;
	}

	/**
	 * Log error
	 *
	 * @param string $message Error message.
	 */
	private function log_error( $message ) {
		error_log( '[HT GA4 Server-Side ERROR] ' . $message );
	}
}
