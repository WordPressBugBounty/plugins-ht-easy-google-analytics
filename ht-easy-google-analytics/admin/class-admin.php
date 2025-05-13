<?php
namespace Ht_Easy_Ga4\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit(); // Exit if accessed directly.
}

class Admin {
	use \Ht_Easy_Ga4\Helper_Trait;

	/**
	 * Singleton instance
	 *
	 * @var Admin
	 */
	private static $instance;

	/**
	 * Singleton instance
	 *
	 * @return Admin
	 */
	public static function instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructor
	 */
	public function __construct() {
		// Clean transiets data if url has email parameter & value match with database email.
		$email = isset( $_GET['email'] ) ? sanitize_text_field( wp_unslash( $_GET['email'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( $email ) {
			$this->clear_transients();
		}
	}



	public function is_ga4_admin_screen() {
		$screen = get_current_screen();

		if ( ! empty( $screen->id ) && $screen->id === 'toplevel_page_ht-easy-ga4-setting-page' ) {
			return true;
		}

		return false;
	}



	public function save_message() {
		if ( isset( $_GET['settings-updated'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			?>
			<div class="updated notice is-dismissible"> 
				<p><strong><?php echo esc_html__( 'Successfully Settings Saved.', 'ht-easy-ga4' ); ?></strong></p>
			</div>
			<?php
		}
	}

	public function get_current_admin_url() {
		$uri = isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
		$uri = preg_replace( '|^.*/wp-admin/|i', '', $uri );

		if ( ! $uri ) {
			return '';
		}

		return remove_query_arg( array( '_wpnonce', '_wc_notice_nonce', 'wc_db_update', 'wc_db_update_nonce', 'wc-hide-notice' ), admin_url( $uri ) );
	}


}

Admin::instance();
?>
