<?php
/**
 * Events Tracking Manager Class
 *
 * Manages events tracking (Purchase event only)
 *
 * @package Ht_Easy_Ga4\EventsTracking
 * @since 1.8.0
 */

namespace Ht_Easy_Ga4\EventsTracking;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Events Tracking Manager class
 */
class Manager {

	/**
	 * Instance
	 *
	 * @var Manager
	 */
	private static $instance = null;

	/**
	 * Event Tracker
	 *
	 * @var Event_Tracker
	 */
	private $event_tracker;

	/**
	 * Get instance
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
	private function __construct() {
		$this->init();
	}

	/**
	 * Initialize
	 */
	private function init() {
		// Only initialize if WooCommerce is active
		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}

		// Initialize event tracker for purchase event (always, even if pro is active)
		// This plugin handles purchase event, pro plugin handles other events
		$this->event_tracker = Event_Tracker::instance();
	}
}
