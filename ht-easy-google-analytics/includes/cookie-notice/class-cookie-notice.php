<?php
namespace Ht_Easy_Ga4;
use const Ht_Easy_Ga4\PL_URL;
use const Ht_Easy_Ga4\PL_PATH;

class Cookie_Notice{
	use \Ht_Easy_Ga4\Traits\Config_Trait;

	/**
	 * [$_instance]
	 *
	 * @var null
	 */
	private static $_instance = null;

	/**
	 * [instance] Initializes a singleton instance
	 *
	 * @return Cookie_Notice
	 */
	public static function get_instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	public function __construct() {
		// Centralized config for this class
		$this->set_config( array(
			'cookie_notice_enabled'         => htga4_get_option('cookie_notice_enabled', false),
			'cookie_notice_overlay_enabled' => htga4_get_option('cookie_notice_overlay_enabled', true),

			'cookie_notice_text'            => htga4_get_option('cookie_notice_text', 'This website uses cookies to ensure you get the best experience on our website'),
			'cookie_notice_privacy_url'     => htga4_get_option('cookie_notice_privacy_url', ''),
			'cookie_notice_privacy_text'    => htga4_get_option('cookie_notice_privacy_text', 'Privacy Policy'),
			'cookie_notice_accept_text'     => htga4_get_option('cookie_notice_accept_text', 'Accept'),
			'cookie_notice_decline_text'    => htga4_get_option('cookie_notice_decline_text', 'Decline'),

			'cookie_notice_duration_type'   => htga4_get_option('cookie_notice_duration_type', 'no_expiry'),
			'cookie_notice_duration_value'  => htga4_get_option('cookie_notice_duration_value', '365'),

			'cookie_notice_cookie_key'      => 'htga4_' . htga4_get_option('cookie_notice_cookie_key', 'cookie_consent'),

            // Cookie notice display region
			'cookie_notice_display_region'  => htga4_get_option('cookie_notice_display_region', ''),

			// Colors
			'background_color' => htga4_get_option('cookie_notice_bg_color'),
			'text_color' => htga4_get_option('cookie_notice_text_color'),
			'acceptance_button_color' => htga4_get_option('cookie_notice_accept_bg_color'),
			'acceptance_button_text_color' => htga4_get_option('cookie_notice_accept_text_color'),
			'decline_button_color' => htga4_get_option('cookie_notice_decline_bg_color'),
			'decline_button_text_color' => htga4_get_option('cookie_notice_decline_text_color'),
			'link_color' => htga4_get_option('cookie_notice_privacy_link_color'),
		) );

		// Show on frontend only if cookie notice should be displayed
		if( !$this->is_notice_disabled() ){
			add_action('wp_footer', array($this, 'render_notice_markup'));
			add_action('wp_enqueue_scripts', array($this, 'notice_css_and_js'));
		}
	}

	public function notice_css_and_js() {
		wp_register_style('htga4-wp-colors', PL_URL . '/assets/css/wp-colors.css', array(), null);
		wp_register_style('htga4-style', PL_URL . '/assets/css/style.css', array(), null);

		wp_enqueue_style('htga4-wp-colors');
		wp_enqueue_style('htga4-style');

		wp_add_inline_style('htga4-style', $this->get_style());

		wp_add_inline_script('jquery', $this->notice_consent_logic_js());
	}

	public function render_notice_markup() {
		include __DIR__ . '/html-cookie-notice.php';
	}

	public function get_style() {
		// Define style mappings: selector => [property => config_key]
		$style_map = [
			'.htga4-cookie-notice' => [
				'background-color' => 'background_color',
			],
			'.htga4-cookie-notice__text' => [
				'color' => 'text_color'
			],
			'.htga4-cookie-notice__button.htga4-cookie-accept' => [
				'background-color' => 'acceptance_button_color',
				'color' => 'acceptance_button_text_color'
			],
			'.htga4-cookie-notice__button.htga4-cookie-decline' => [
				'background-color' => 'decline_button_color',
				'color' => 'decline_button_text_color'
			],
			'.htga4-cookie-notice__link' => [
				'color' => 'link_color'
			]
		];

		return $this->generate_css_from_map($style_map);
	}

	/**
	 * Generate CSS from style mapping array
	 * 
	 * @param array $style_map Array of [selector => [property => config_key]]
	 * config_key is the key from where the css values come from styling options
	 * 
	 * @return string Generated CSS or empty string if no valid styles
	 */
	private function generate_css_from_map($style_map) {
		$css_blocks = [];

		foreach ($style_map as $selector => $properties) {
			$css_properties = [];

			foreach ($properties as $property => $config_key) {
				$value = $this->config($config_key);
				
				// Only add property if value exists and is not empty
				if (!empty($value) && $value !== null && $value !== '') {
					$css_properties[] = "\t{$property}: {$value};";
				}
			}

			// Only add selector block if it has properties
			if (!empty($css_properties)) {
				$css_blocks[] = "{$selector} {\n" . implode("\n", $css_properties) . "\n}";
			}
		}

		// Return CSS or empty string if no valid styles
		return !empty($css_blocks) ? implode("\n\n", $css_blocks) : '';
	}

	public function notice_consent_logic_js() {
		return <<<JS
		// Check if HTGA4 config is available and execute if available
		if (typeof window.HTGA4 !== 'undefined') {
		
		jQuery(document).ready(function($) {
			var notice = $('#htga4-cookie-notice');
			var acceptBtn = $('#htga4-cookie-accept');
			var declineBtn = $('#htga4-cookie-decline');
	
			function setCookie(name, value, days) {
				var expires = "";
				if (window.HTGA4.cookie_notice_duration_type === 'custom' && days) {
					var date = new Date();
					date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
					expires = "; expires=" + date.toUTCString();
				} else if (window.HTGA4.cookie_notice_duration_type === 'no_expiry') {
					var date = new Date();
					date.setTime(date.getTime() + (window.HTGA4.one_year_seconds * 1000));
					expires = "; expires=" + date.toUTCString();
				}
				document.cookie = name + "=" + value + expires + "; path=/";
			}
	
			notice.fadeIn();
	
			if (Number(window.HTGA4.cookie_notice_overlay_enabled) !== 1) {
				var height = notice.outerHeight();
				$('body').css('margin-bottom', height + 'px');
			}
	
			acceptBtn.on('click', function() {
				setCookie(window.HTGA4.cookie_notice_cookie_key, 'yes', window.HTGA4.cookie_notice_duration_value);
				notice.fadeOut();

				if (!window.HTGA4.cookie_notice_overlay_enabled) {
					$('body').css('margin-bottom', '');
				}

				// Update consent for GA4
				if (typeof window.htga4_update_consent === 'function') {
					window.htga4_update_consent('yes');
				}
				
				// Reload page to apply tracking
				// window.location.reload();
			});

			declineBtn.on('click', function() {
				setCookie(window.HTGA4.cookie_notice_cookie_key, 'no', window.HTGA4.cookie_notice_duration_value);
				notice.fadeOut();

				if (!window.HTGA4.cookie_notice_overlay_enabled) {
					$('body').css('margin-bottom', '');
				}
				
				// Update consent for GA4
				if (typeof window.htga4_update_consent === 'function') {
					window.htga4_update_consent('no');
				}
			});
		});
		
		} // End of HTGA4 config check
	JS;
	}

	/**
	 * Check if notice should be disabled or not.
	 */
	public function is_notice_disabled() {
		// Disable notice if cookie notice is disabled
		if (!$this->config('cookie_notice_enabled')) {
			return true;
		}

		// Disable notice if user has already consented
		if ($this->has_user_consent() !== null) {
			return true;
		}

		// Disable notice if region is set to EU only and user is not in EU region
		if ($this->config('cookie_notice_display_region') === 'eu' && !$this->is_eu_user()) {
			return true;
		}

		return false;
	}

	/**
	 * Check if user should get automatic consent (when notice is disabled for their region)
	 */
	public function should_auto_consent() {
		// Auto consent if cookie notice is disabled globally
		if (!$this->config('cookie_notice_enabled')) {
			return true;
		}

		// Auto consent if region is set to EU only and user is not in EU region
		if ($this->config('cookie_notice_display_region') === 'eu' && !$this->is_eu_user()) {
			return true;
		}

		return false;
	}

	/**
	 * Get user consent status from cookie
	 */
	public function has_user_consent() {
		$cookie_key = $this->config('cookie_notice_cookie_key');

		if (isset($_COOKIE[$cookie_key])) {
			$cookie_value = $_COOKIE[$cookie_key];
			
			// Simple format - cookie expiry handles duration
			return in_array($cookie_value, ['yes', 'no']) ? $cookie_value : null;
		}

		return null;
	}

	/**
	 * Detect if user is from EU region
	 */
	private function is_eu_user() {
		$eu_countries = [
			'AT', 'BE', 'BG', 'HR', 'CY', 'CZ', 'DK', 'EE', 'FI', 'FR',
			'DE', 'GR', 'HU', 'IE', 'IT', 'LV', 'LT', 'LU', 'MT', 'NL',
			'PL', 'PT', 'RO', 'SK', 'SI', 'ES', 'SE'
		];

		$user_country = $this->get_user_country();
		return in_array($user_country, $eu_countries);
	}

	/**
	 * Get user's country code
	 */
	private function get_user_country() {
		$ip = $this->get_user_ip();

		if (function_exists('wp_remote_get')) {
			$response = wp_remote_get("http://ip-api.com/json/{$ip}?fields=countryCode");

			if (!is_wp_error($response)) {
				$body = wp_remote_retrieve_body($response);
				$data = json_decode($body, true);

				if (isset($data['countryCode'])) {
					return $data['countryCode'];
				}
			}
		}

		return 'US';
	}

	/**
	 * Get user's IP address
	 */
	private function get_user_ip() {
		$ip_keys = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR'];

		foreach ($ip_keys as $key) {
			if (!empty($_SERVER[$key])) {
				$ip = $_SERVER[$key];
				if (strpos($ip, ',') !== false) {
					$ip = explode(',', $ip)[0];
				}
				return trim($ip);
			}
		}

		return '127.0.0.1';
	}
}

Cookie_Notice::get_instance();