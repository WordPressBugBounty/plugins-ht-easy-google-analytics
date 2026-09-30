<?php
/**
 * Upgrade Notice Class for Google Ads
 *
 * Handles upgrade prompts and notices for pro features
 *
 * @package Ht_Easy_Ga4\GoogleAds
 * @since 1.8.0
 */

namespace Ht_Easy_Ga4\GoogleAds;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Upgrade Notice class for Google Ads
 */
class Upgrade_Notice {

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->init_hooks();
	}

	/**
	 * Initialize hooks
	 */
	private function init_hooks() {
		// Admin notices
		add_action( 'admin_notices', array( $this, 'display_admin_notices' ) );

		// AJAX dismiss handler
		add_action( 'wp_ajax_htga4_dismiss_upgrade_notice', array( $this, 'ajax_dismiss_notice' ) );

		// Add upgrade prompts to settings page
		add_action( 'admin_footer', array( $this, 'add_settings_upgrade_prompts' ) );

		// Dismiss handler script on all admin pages
		add_action( 'admin_footer', array( $this, 'add_dismiss_script' ) );
	}

	/**
	 * Display admin notices
	 */
	public function display_admin_notices() {
		// Only admins can act on Google Ads setup / upgrades.
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Show setup incomplete notice
		if ( $this->should_show_setup_notice() ) {
			$this->display_setup_notice();
		}

		// Show feature discovery notice (once per month) — it's a Pro upsell, so not for Pro users
		if ( ! htga4_is_pro() && $this->should_show_feature_notice() ) {
			$this->display_feature_notice();
		}
	}

	/**
	 * Display setup incomplete notice
	 */
	private function display_setup_notice() {
		$settings = Manager::instance()->get_settings();

		if ( ! empty( $settings['enabled'] ) ) {
			return;
		}

		?>
		<div class="notice notice-info is-dismissible htga4-upgrade-notice" data-notice-id="setup-incomplete">
			<h3><?php esc_html_e( 'Complete Your Google Ads Setup', 'ht-easy-ga4' ); ?></h3>
			<p><?php esc_html_e( 'You haven\'t set up Google Ads conversion tracking yet. Start tracking your ROI today!', 'ht-easy-ga4' ); ?></p>
			<p>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=ht-easy-ga4-setting-page#/settings/google-ads' ) ); ?>" class="button button-primary">
					<?php esc_html_e( 'Configure Now', 'ht-easy-ga4' ); ?>
				</a>
			</p>
		</div>
		<?php
	}

	/**
	 * Display feature discovery notice
	 */
	private function display_feature_notice() {
		$features = array(
			array(
				'title' => __( 'More Conversion Events in Pro', 'ht-easy-ga4' ),
				'message' => __( 'Send Add to Cart, Checkout, Product View and Category View conversions to Google Ads.', 'ht-easy-ga4' ),
				'icon' => '🛒',
			),
			array(
				'title' => __( 'Ecommerce Reports in Pro', 'ht-easy-ga4' ),
				'message' => __( 'See revenue, top products and sales by source inside your WordPress dashboard.', 'ht-easy-ga4' ),
				'icon' => '📊',
			),
			array(
				'title' => __( 'Unlimited Custom Events in Pro', 'ht-easy-ga4' ),
				'message' => __( 'Track any button click, form submission or page view as a GA4 event, with no limit.', 'ht-easy-ga4' ),
				'icon' => '⚡',
			),
		);

		// Randomly select a feature to highlight
		$feature = $features[ array_rand( $features ) ];

		?>
		<div class="notice notice-info is-dismissible htga4-upgrade-notice" data-notice-id="feature-discovery">
			<h3><?php echo $feature['icon'] . ' ' . esc_html( $feature['title'] ); ?></h3>
			<p><?php echo esc_html( $feature['message'] ); ?></p>
			<p>
				<a href="<?php echo esc_url( $this->get_upgrade_url( 'feature_discovery' ) ); ?>" class="button button-primary" target="_blank">
					<?php esc_html_e( 'Learn More', 'ht-easy-ga4' ); ?>
				</a>
				<button type="button" class="button button-link htga4-dismiss-notice">
					<?php esc_html_e( 'Dismiss', 'ht-easy-ga4' ); ?>
				</button>
			</p>
		</div>
		<?php
	}

	/**
	 * Add dismiss handler script for upgrade notices on all admin pages
	 */
	public function add_dismiss_script() {
		?>
		<script>
			jQuery(function($) {
				$(document).on('click', '.htga4-upgrade-notice .notice-dismiss, .htga4-upgrade-notice .htga4-dismiss-notice', function(e) {
					var $notice = $(this).closest('.htga4-upgrade-notice');
					var noticeId = $notice.data('notice-id');

					if ( ! noticeId ) {
						return;
					}

					$.post(ajaxurl, {
						action: 'htga4_dismiss_upgrade_notice',
						notice_id: noticeId,
						nonce: '<?php echo wp_create_nonce( 'htga4_dismiss_notice' ); ?>'
					});
				});
			});
		</script>
		<?php
	}

	/**
	 * Add upgrade prompts to settings page
	 */
	public function add_settings_upgrade_prompts() {
		$screen = get_current_screen();

		if ( ! $screen || strpos( $screen->id, 'ht-easy-ga4-setting-page' ) === false ) {
			return;
		}

		?>
		<script>
			// Upgrade notice handlers
			jQuery(function($) {
				// Dismiss notice handler
				$(document).on('click', '.htga4-dismiss-notice', function(e) {
					e.preventDefault();
					var $notice = $(this).closest('.htga4-upgrade-notice');
					var noticeId = $notice.data('notice-id');

					$notice.fadeOut();

					// Save dismissal
					$.post(ajaxurl, {
						action: 'htga4_dismiss_upgrade_notice',
						notice_id: noticeId,
						nonce: '<?php echo wp_create_nonce( 'htga4_dismiss_notice' ); ?>'
					});
				});

				// Show upgrade modal function
				window.htga4ShowUpgradeModal = function(feature, message) {
					// Implementation for showing upgrade modal
					if (typeof Swal !== 'undefined') {
						Swal.fire({
							title: '🚀 Pro Feature',
							html: message + '<br><br><strong>Upgrade to Pro to unlock this feature!</strong>',
							icon: 'info',
							showCancelButton: true,
							confirmButtonText: 'View Pro Features',
							cancelButtonText: 'Maybe Later',
							confirmButtonColor: '#3085d6',
						}).then((result) => {
							if (result.isConfirmed) {
								window.open('<?php echo esc_url( $this->get_upgrade_url( 'modal' ) ); ?>', '_blank');
							}
						});
					} else {
						if (confirm(message + '\n\nUpgrade to Pro to unlock this feature!')) {
							window.open('<?php echo esc_url( $this->get_upgrade_url( 'modal' ) ); ?>', '_blank');
						}
					}
				};
			});
		</script>

		<style>
			.htga4-upgrade-notice {
				border-left-color: #00a0d2;
			}
			.htga4-upgrade-notice h3 {
				margin-top: 10px;
			}
			.htga4-inline-upgrade-notice {
				background: #f0f8ff;
				border-left: 4px solid #0073aa;
				padding: 12px;
				margin: 20px 0;
			}
			.htga4-pro-badge {
				display: inline-block;
				background: #ff6900;
				color: #fff;
				padding: 2px 6px;
				border-radius: 3px;
				font-size: 11px;
				font-weight: bold;
				margin-left: 5px;
				vertical-align: middle;
			}
			.htga4-pro-feature-locked {
				position: relative;
				opacity: 0.7;
				pointer-events: none;
			}
			.htga4-pro-feature-locked::after {
				content: '🔒 PRO';
				position: absolute;
				top: 50%;
				left: 50%;
				transform: translate(-50%, -50%);
				background: rgba(0, 0, 0, 0.8);
				color: #fff;
				padding: 5px 10px;
				border-radius: 3px;
				font-weight: bold;
			}
		</style>
		<?php
	}

	/**
	 * AJAX handler for dismissing notices
	 */
	public function ajax_dismiss_notice() {
		check_ajax_referer( 'htga4_dismiss_notice', 'nonce' );

		$notice_id = isset( $_POST['notice_id'] ) ? sanitize_text_field( $_POST['notice_id'] ) : '';

		if ( $notice_id ) {
			$dismissed = get_user_meta( get_current_user_id(), 'htga4_dismissed_notices', true );

			if ( ! is_array( $dismissed ) ) {
				$dismissed = array();
			}

			$dismissed[ $notice_id ] = time();
			update_user_meta( get_current_user_id(), 'htga4_dismissed_notices', $dismissed );
		}

		wp_send_json_success();
	}

	/**
	 * Check if should show setup notice
	 *
	 * @return bool
	 */
	private function should_show_setup_notice() {
		// Only on dashboard and HT Easy GA4 pages
		$screen = get_current_screen();

		if ( ! $screen || ( $screen->id !== 'dashboard' && strpos( $screen->id, 'ht-easy-ga4' ) === false ) ) {
			return false;
		}

		// Check if dismissed
		$dismissed = get_user_meta( get_current_user_id(), 'htga4_dismissed_notices', true );

		if ( isset( $dismissed['setup-incomplete'] ) ) {
			return false;
		}

		// Check if setup is complete
		return ! Settings::is_setup_complete();
	}

	/**
	 * Check if should show feature notice
	 *
	 * @return bool
	 */
	private function should_show_feature_notice() {
		$dismissed = get_user_meta( get_current_user_id(), 'htga4_dismissed_notices', true );

		if ( isset( $dismissed['feature-discovery'] ) ) {
			// Show once per month
			if ( time() - $dismissed['feature-discovery'] < MONTH_IN_SECONDS ) {
				return false;
			}
		}

		// Only show if setup is complete
		if ( ! Settings::is_setup_complete() ) {
			return false;
		}

		// Only on HT Easy GA4 pages
		$screen = get_current_screen();

		return $screen && strpos( $screen->id, 'ht-easy-ga4' ) !== false;
	}

	/**
	 * Get upgrade URL with tracking parameters
	 *
	 * @param string $source Source of the upgrade link
	 * @return string
	 */
	private function get_upgrade_url( $source = 'notice' ) {
		return htga4_upgrade_url( 'gads-' . $source );
	}
}