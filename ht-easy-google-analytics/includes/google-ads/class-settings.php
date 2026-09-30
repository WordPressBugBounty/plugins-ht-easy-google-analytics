<?php
/**
 * Settings Class for Google Ads
 *
 * Settings helpers for Google Ads
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
	 * Check if setup is complete
	 *
	 * Complete = tracking enabled, conversion ID set, and at least one conversion
	 * event enabled with a label (same rule as the Google Ads settings screen).
	 *
	 * @return bool
	 */
	public static function is_setup_complete() {
		$settings = Manager::instance()->get_settings();

		if ( empty( $settings['enabled'] ) || empty( $settings['conversion_id'] ) ) {
			return false;
		}

		$labels = isset( $settings['conversion_labels'] ) && is_array( $settings['conversion_labels'] ) ? $settings['conversion_labels'] : array();

		foreach ( $labels as $config ) {
			if ( ! empty( $config['enabled'] ) && '' !== trim( (string) ( $config['label'] ?? '' ) ) ) {
				return true;
			}
		}

		return false;
	}
}
