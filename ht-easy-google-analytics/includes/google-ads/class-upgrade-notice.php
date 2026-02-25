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

		// Check for conversion milestones
		add_action( 'admin_init', array( $this, 'check_conversion_milestones' ) );
	}

	/**
	 * Display admin notices
	 */
	public function display_admin_notices() {
		// Check if we should show conversion milestone notice
		$milestone = get_transient( 'htga4_show_conversion_upgrade_notice' );

		if ( $milestone ) {
			$this->display_milestone_notice( $milestone );
			delete_transient( 'htga4_show_conversion_upgrade_notice' );
		}

		// Show setup incomplete notice
		if ( $this->should_show_setup_notice() ) {
			$this->display_setup_notice();
		}

		// Show feature discovery notice (once per month)
		if ( $this->should_show_feature_notice() ) {
			$this->display_feature_notice();
		}
	}

	/**
	 * Display milestone achievement notice
	 *
	 * @param int $milestone Number of conversions reached
	 */
	private function display_milestone_notice( $milestone ) {
		$messages = array(
			10 => array(
				'title' => __( 'Congratulations! 10 Conversions Tracked', 'ht-easy-ga4' ),
				'message' => __( 'You\'ve successfully tracked 10 conversions! Upgrade to Pro to unlock Enhanced Conversions and get 30% better match rates.', 'ht-easy-ga4' ),
				'cta' => __( 'Boost Your Conversions', 'ht-easy-ga4' ),
			),
			25 => array(
				'title' => __( 'Great Progress! 25 Conversions Tracked', 'ht-easy-ga4' ),
				'message' => __( 'Your Google Ads tracking is working great! Pro features like Server-Side Tracking can help bypass ad blockers and improve accuracy.', 'ht-easy-ga4' ),
				'cta' => __( 'Improve Tracking Accuracy', 'ht-easy-ga4' ),
			),
			50 => array(
				'title' => __( 'Milestone Reached! 50 Conversions', 'ht-easy-ga4' ),
				'message' => __( 'You\'re getting serious results! Pro\'s advanced analytics and ROAS tracking can help optimize your campaigns even further.', 'ht-easy-ga4' ),
				'cta' => __( 'Unlock Advanced Analytics', 'ht-easy-ga4' ),
			),
			100 => array(
				'title' => __( 'Amazing! 100 Conversions Tracked', 'ht-easy-ga4' ),
				'message' => __( 'You\'re a power user! Upgrade to Pro for AI-powered optimization, Consent Mode v2, and unlimited conversion history.', 'ht-easy-ga4' ),
				'cta' => __( 'Go Pro Now', 'ht-easy-ga4' ),
			),
		);

		if ( ! isset( $messages[ $milestone ] ) ) {
			return;
		}

		$notice = $messages[ $milestone ];
		?>
		<div class="notice notice-success is-dismissible htga4-upgrade-notice" data-notice-id="milestone-<?php echo $milestone; ?>">
			<h3><?php echo esc_html( $notice['title'] ); ?></h3>
			<p><?php echo esc_html( $notice['message'] ); ?></p>
			<p>
				<a href="<?php echo esc_url( $this->get_upgrade_url( 'milestone_' . $milestone ) ); ?>" class="button button-primary" target="_blank">
					<?php echo esc_html( $notice['cta'] ); ?>
				</a>
				<button type="button" class="button button-secondary htga4-dismiss-notice">
					<?php esc_html_e( 'Maybe Later', 'ht-easy-ga4' ); ?>
				</button>
			</p>
		</div>
		<?php
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
				'title' => __( 'Enhanced Conversions Available in Pro', 'ht-easy-ga4' ),
				'message' => __( 'Get 30% better conversion match rates with privacy-safe Enhanced Conversions.', 'ht-easy-ga4' ),
				'icon' => '🔐',
			),
			array(
				'title' => __( 'Server-Side Tracking in Pro', 'ht-easy-ga4' ),
				'message' => __( 'Bypass ad blockers and improve tracking accuracy with server-side conversion tracking.', 'ht-easy-ga4' ),
				'icon' => '⚡',
			),
			array(
				'title' => __( 'AI-Powered Features in Pro', 'ht-easy-ga4' ),
				'message' => __( 'Generate ad copy and get optimization tips with AI-powered features.', 'ht-easy-ga4' ),
				'icon' => '🤖',
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

				// Show upgrade notice function
				window.htga4ShowUpgradeNotice = function(feature, message) {
					var $notice = $('<div class="notice notice-info htga4-inline-upgrade-notice">' +
						'<p><strong>🎯 ' + message + '</strong></p>' +
						'<p><a href="<?php echo esc_url( $this->get_upgrade_url( 'inline' ) ); ?>" target="_blank" class="button button-primary">Upgrade to Pro</a></p>' +
						'</div>');

					$('.htga4-settings-content').prepend($notice);

					setTimeout(function() {
						$notice.slideUp();
					}, 10000);
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
	 * Check conversion milestones
	 */
	public function check_conversion_milestones() {
		// Check once per day
		$last_check = get_transient( 'htga4_milestone_check' );

		if ( $last_check ) {
			return;
		}

		global $wpdb;
		$table_name = $wpdb->prefix . 'htga4_conversion_log';

		// Check if table exists
		if ( $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) !== $table_name ) {
			return;
		}

		$count = $wpdb->get_var( "SELECT COUNT(*) FROM $table_name" );
		$count = intval( $count );

		$milestones = array( 10, 25, 50, 100, 250, 500 );
		$shown_milestones = get_option( 'htga4_shown_milestones', array() );

		foreach ( $milestones as $milestone ) {
			if ( $count >= $milestone && ! in_array( $milestone, $shown_milestones, true ) ) {
				set_transient( 'htga4_show_conversion_upgrade_notice', $milestone, DAY_IN_SECONDS );
				$shown_milestones[] = $milestone;
				update_option( 'htga4_shown_milestones', $shown_milestones );
				break;
			}
		}

		set_transient( 'htga4_milestone_check', true, DAY_IN_SECONDS );
	}

	/**
	 * Get upgrade URL with tracking parameters
	 *
	 * @param string $source Source of the upgrade link
	 * @return string
	 */
	private function get_upgrade_url( $source = 'notice' ) {
		return add_query_arg(
			array(
				'utm_source' => 'plugin',
				'utm_medium' => 'google_ads',
				'utm_campaign' => 'free_to_pro',
				'utm_content' => $source,
			),
			'https://hasthemes.com/plugins/ht-easy-google-analytics-pro/'
		);
	}

	/**
	 * Get inline upgrade prompt HTML
	 *
	 * @param string $feature Feature name
	 * @param string $message Upgrade message
	 * @return string
	 */
	public static function get_inline_prompt( $feature, $message ) {
		$url = add_query_arg(
			array(
				'utm_source' => 'plugin',
				'utm_medium' => 'inline_prompt',
				'utm_content' => $feature,
			),
			'https://hasthemes.com/plugins/ht-easy-google-analytics-pro/'
		);

		return sprintf(
			'<div class="htga4-pro-prompt">
				<span class="htga4-pro-badge">PRO</span>
				<span class="htga4-pro-message">%s</span>
				<a href="%s" target="_blank" class="htga4-pro-link">%s</a>
			</div>',
			esc_html( $message ),
			esc_url( $url ),
			esc_html__( 'Upgrade Now', 'ht-easy-ga4' )
		);
	}
}