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
			'measurement_id' => htga4_get_measurement_id(),
			'custom_events' => htga4_get_option('custom_events', [])
        ));

		add_action( 'wp_head', array( $this, 'inject_gtag_base_script' ) );
		add_action( 'wp_footer', array( $this, 'render_consent_logic' ) );
		add_action( 'wp_footer', array( $this, 'render_custom_events' ) );
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
				
				// Dispatch custom event for consent changes
				if (consent === 'yes') {
					window.dispatchEvent(new CustomEvent('htga4_consent_granted'));
				}
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

	public function render_custom_events() {
		if( $this->should_skip_tracking() ){
			return;
		}

		$custom_events = $this->config('custom_events');
		if ( empty( $custom_events ) || ! is_array( $custom_events ) ) {
			return;
		}

		// Filter only active events and convert to indexed array
		$active_events = array_values(array_filter( $custom_events, function( $event ) {
			return ! empty( $event['active'] ) && ! empty( $event['event_name'] ) && ! empty( $event['trigger_value'] );
		}));

		if ( empty( $active_events ) ) {
			return;
		}

		?>
		<script>
			(function() {
				// Localized parameters for AJAX
				const htga4_params = {
					ajax_url: '<?php echo esc_js( admin_url( 'admin-ajax.php' ) ); ?>',
					nonce: '<?php echo esc_js( wp_create_nonce( 'htga4_nonce' ) ); ?>',
					server_side_tracking: <?php echo htga4_get_option( 'server_side_tracking', false ) ? 'true' : 'false'; ?>
				};

				const DEBUG = window.HTGA4 && window.HTGA4.debug;

				function log(...args) {
					if (DEBUG) console.log('[HTGA4 Custom Events]', ...args);
				}

				function getDynamicValue(element, valueType, paramValue) {
					// Handle cases where element is null (e.g., page_view events)
					if (!element) {
						switch (valueType) {
							case 'dynamic_page_url':
								return window.location.href;
							case 'static_text':
								return paramValue || '';
							default:
								return '';
						}
					}
					
					switch (valueType) {
						case 'dynamic_click_text':
							return element.innerText || element.textContent || '';
						case 'dynamic_href_filename':
							const href = element.href || '';
							return href.split('/').pop().split('?')[0] || '';
						case 'dynamic_page_url':
							return window.location.href;
						case 'dynamic_form_id':
							return element.id || element.getAttribute('name') || '';
						case 'dynamic_data_attribute':
							return element.getAttribute(paramValue) || '';
						case 'dynamic_closest_section':
							const section = element.closest('section') || element.closest('[class*="section"]');
							return section ? section.className : '';
						default:
							return paramValue || '';
					}
				}

				function buildEventParameters(event, element) {
					const params = {};
					
					// Handle simple mode
					if (event.event_category) params.event_category = event.event_category;
					if (event.event_label) params.event_label = event.event_label;
					if (event.event_value) params.event_value = parseInt(event.event_value) || 1;
					
					// Handle advanced mode parameters only when parameter_mode is 'advanced'
					if (event.parameter_mode === 'advanced' && event.parameters && Array.isArray(event.parameters)) {
						event.parameters.forEach(param => {
							if (param.param_key && param.param_value_type) {
								params[param.param_key] = getDynamicValue(element, param.param_value_type, param.param_value);
							}
						});
					}
					
					return params;
				}

				function fireCustomEvent(event, element = null) {
					if (typeof gtag === 'undefined') {
						log('gtag not available');
						return;
					}

					const params = buildEventParameters(event, element);

					// Check if server-side tracking is enabled
					if (htga4_params.server_side_tracking) {
						// Use AJAX to send event to server
						if (typeof jQuery !== 'undefined') {
							jQuery.ajax({
								url: htga4_params.ajax_url,
								type: 'POST',
								data: {
									action: 'htga4_custom_event_ajax_action',
									event_name: event.event_name,
									event_params: JSON.stringify(params),
									nonce: htga4_params.nonce
								},
								success: function(response) {
									if (response.success && response?.data?.tracked === 'client' && response?.data?.data) {
										log('Firing custom event:', event.event_name, response.data.data);
										gtag('event', event.event_name, response.data.data);
									} else if (response.success && response?.data?.tracked === 'server') {
										log('Event tracked server-side:', event.event_name);
									}
								},
								error: function(error) {
									log('AJAX error:', error);
								}
							});
						} else {
							log('jQuery not available, falling back to client-side tracking');
							gtag('event', event.event_name, params);
						}
					} else {
						// Client-side tracking
						log('Firing custom event:', event.event_name, params);
						gtag('event', event.event_name, params);
					}
				}

				function attachEventListeners() {
					const events = <?php echo json_encode( $active_events ); ?>;
					
					// Ensure events is an array before processing
					if (!events || !Array.isArray(events)) {
						log('No active events to process or invalid events data');
						return;
					}
					
					events.forEach(event => {
						log('Setting up event:', event.name, 'Trigger:', event.trigger_type, 'Target:', event.trigger_value);
						
						switch (event.trigger_type) {
							case 'click':
								// Use only event delegation for better compatibility and to avoid duplicates
								document.addEventListener('click', function(e) {
									// Check if the clicked element matches our selector
									if (e.target.matches && e.target.matches(event.trigger_value)) {
										// Small delay to ensure other handlers run first
										setTimeout(() => {
											fireCustomEvent(event, e.target);
										}, 10);
									}
								}, true); // Use capture phase to ensure we catch the event
								
								// Log the number of elements found for reference
								const elements = document.querySelectorAll(event.trigger_value);
								log('Found', elements.length, 'elements matching selector:', event.trigger_value);
								break;
								
							case 'form_submit':
								// Use only event delegation for forms to avoid duplicates
								document.addEventListener('submit', function(e) {
									if (e.target.matches && e.target.matches(event.trigger_value)) {
										setTimeout(() => {
											fireCustomEvent(event, e.target);
										}, 10);
									}
								}, true);
								
								// Log the number of forms found for reference
								const forms = document.querySelectorAll(event.trigger_value);
								log('Found', forms.length, 'forms matching selector:', event.trigger_value);
								break;
								
							case 'page_view':
								// Fire immediately for page_view events
								fireCustomEvent(event);
								log('Fired page_view event');
								break;
						}
					});
				}

				// Helper function to get cookie value
				function getCookie(name) {
					const value = `; ${document.cookie}`;
					const parts = value.split(`; ${name}=`);
					if (parts.length === 2) return parts.pop().split(';').shift();
					return null;
				}

				// Wait for consent and DOM ready
				function initCustomEvents() {
					// Check if consent is granted
					const consentCookie = getCookie(window.HTGA4?.cookie_notice_cookie_key);
					const shouldAutoConsent = window.HTGA4?.should_auto_consent;
					
					if (consentCookie === 'yes' || shouldAutoConsent) {
						log('Consent granted, initializing custom events');
						attachEventListeners();
					} else if (consentCookie === 'no') {
						log('Consent denied, skipping custom events');
					} else {
						log('Waiting for consent...');
						// Listen for consent updates
						window.addEventListener('htga4_consent_granted', function() {
							log('Consent granted via event, initializing custom events');
							attachEventListeners();
						});
					}
				}

				// Initialize when DOM is ready
				if (document.readyState === 'loading') {
					document.addEventListener('DOMContentLoaded', initCustomEvents);
				} else {
					initCustomEvents();
				}
			})();
		</script>
		<?php
	}
}

GA4_Tracker::get_instance();