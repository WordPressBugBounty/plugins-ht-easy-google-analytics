<?php
namespace Ht_Easy_Ga4\Frontend;

if ( ! defined( 'ABSPATH' ) ) {
	exit(); // Exit if accessed directly.
}

class GA4_Tracker {
	use \Ht_Easy_Ga4\Helper_Trait;
	use \Ht_Easy_Ga4\Traits\Config_Trait;
	
	/**
	 * [$_instance]
	 *
	 * @var null
	 */
	private static $_instance = null;

	/**
	 * [instance] Initializes a singleton instance
	 */
	public static function get_instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	public function __construct() {
		$this->set_config(array(
			'cookie_notice_enabled' => htga4_get_option('cookie_notice_enabled'),
            'exclude_roles' => htga4_get_option('exclude_roles', []),
			'measurement_id' => htga4_get_measurement_id()
        ));

		add_action( 'wp_head', array( $this, 'inject_gtag_base_script' ) );
		add_action( 'wp_footer', array( $this, 'render_consent_logic' ) );
	}

	/**
	 * Check if the current user is of the excluded user roles.
	 *
	 * @return bool
	 */
	public function should_skip_tracking(){
		$return_value = false;
		
		// If measurement ID is empty, skip tracking
		if ( empty( $this->config('measurement_id') ) ) {
			return true;
		}

		$excluded_roles = is_array($this->config('exclude_roles')) ? $this->config('exclude_roles') : explode(',', $this->config('exclude_roles')); // Needed to convert string to array because when only one role is selected it is stored as string

		if ( is_user_logged_in() ) {
			$current_user_id    = get_current_user_id();
			$current_user       = get_userdata( $current_user_id );
			$current_user_roles = $current_user->roles;

			if ( ! empty( $excluded_roles ) && array_intersect( $excluded_roles, $current_user_roles ) ) {
				$return_value = true;
			}
		}

		return $return_value;
	}

	public function inject_gtag_base_script() {
		if( $this->should_skip_tracking() ){
			return;
		}

	?>
		<!-- Global site tag (gtag.js) - added by HT Easy Ga4 -->
		<script>
			window.dataLayer = window.dataLayer || [];
			function gtag() { dataLayer.push(arguments); }

			// Default: deny all tracking
			gtag('consent', 'default', {
				'ad_storage': 'denied',
				'analytics_storage': 'denied',
				'ad_user_data': 'denied',
				'ad_personalization': 'denied'
			});

			// Load gtag script early; update permissions after consent
			(function() {
				const script = document.createElement('script');
				script.async = true;
				script.src = `https://www.googletagmanager.com/gtag/js?id=<?php echo esc_js( $this->config('measurement_id') ); ?>`;
				document.head.appendChild(script);
			})();

			gtag('js', new Date());
			gtag('config', <?php echo "'" . esc_js( $this->config('measurement_id') ) . "'"; ?>);
		</script>
	<?php
	}

	public function render_consent_logic() {
		if( $this->should_skip_tracking() ){
			return;
		}
		?>
		<script>
			const DEBUG = window.HTGA4 && window.HTGA4.debug;
			
			window.htga4_update_consent = function(consent) {
				gtag('consent', 'update', {
					'ad_storage': consent === 'yes' ? 'granted' : 'denied',
					'analytics_storage': consent === 'yes' ? 'granted' : 'denied',
					'ad_user_data': consent === 'yes' ? 'granted' : 'denied',
					'ad_personalization': consent === 'yes' ? 'granted' : 'denied'
				});
			};

			// Helper function to get cookie value
			function getCookie(name) {
				const value = `; ${document.cookie}`;
				const parts = value.split(`; ${name}=`);
				if (parts.length === 2) return parts.pop().split(';').shift();
				return null;
			}

			function log(...args) {
				if (DEBUG) console.log(...args);
			}

			function initConsentFlow() {
				log("Starting consent flow");

				// Check if HTGA4 config is available
				if (typeof window.HTGA4 === 'undefined') {
					log("HTGA4 config not available");
					return;
				}

				// If user should get automatic consent (notice disabled or non-EU user with EU-only setting)
				if (window.HTGA4.should_auto_consent) {
					log("Auto consent granted → always track");
					if (typeof window.htga4_update_consent === 'function') {
						window.htga4_update_consent('yes');
					}
					return;
				}

				// Check if user has already given consent
				const storedConsent = getCookie(window.HTGA4.cookie_notice_cookie_key);
				if (storedConsent === 'yes' || storedConsent === 'no') {
					log("Using stored consent:", storedConsent);
					if (typeof window.htga4_update_consent === 'function') {
						window.htga4_update_consent(storedConsent);
					}
				} else {
					log("No stored consent found");
					// Cookie notice will handle showing the consent request
					// PHP side determines if notice should be shown based on region/settings
				}
			}

			initConsentFlow();
		</script>
		<?php
	}
}

GA4_Tracker::get_instance();