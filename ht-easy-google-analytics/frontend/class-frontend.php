<?php
namespace Ht_Easy_Ga4\Frontend;

if ( ! defined( 'ABSPATH' ) ) {
	exit(); // Exit if accessed directly.
}

class Frontend {
	use \Ht_Easy_Ga4\Helper_Trait;

	/**
	 * [$_instance]
	 *
	 * @var null
	 */
	private static $_instance = null;

	/**
	 * [instance] Initializes a singleton instance
	 *
	 * @return Frontend
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	public function __construct() {
		if ( $this->get_measurement_id2() ) {
			// Compatibility with WooCommerce redirect to cart after added to cart feature.
			if ( htga4()->get_option( 'add_to_cart_event' ) ) {
				// works both ajax non ajax.
				add_action( 'woocommerce_add_to_cart', array( $this, 'woocommerce_add_to_cart_cb' ), 10, 6 );

				// Detect added to cart proudct after redirect in the cart page.
				add_action( 'template_redirect', array( $this, 'detect_added_to_cart_after_redirect_in_cart_page' ), 10, 1 );
			}
		}
	}

	/**
	 * Add to cart action
	 */
	public function woocommerce_add_to_cart_cb( $cart_id, $product_id, $request_quantity, $variation_id, $variation, $cart_item_data ) {
		$item_id  = $variation_id ? $variation_id : $product_id;
		$item_arr = array(
			'item_id'  => $item_id,
			'quantity' => $request_quantity,
		);

		// redirect to cart should be enabled.
		if ( 'yes' == get_option( 'woocommerce_cart_redirect_after_add' ) ) {
			WC()->session->set( 'whols_last_added_item', $item_arr );
		}
	}

	/**
	 * Detect added to cart proudct after redirect in the cart page
	 */
	public function detect_added_to_cart_after_redirect_in_cart_page() {
		if ( ! function_exists( 'WC' ) ) {
			return;
		}

		// redirect to cart should be enabled.
		if ( 'yes' != get_option( 'woocommerce_cart_redirect_after_add' ) ) {
			return;
		}

		// Notices are somehow removed from the session from the inner hooks, so get the notices using init hook first.
		$last_added_item = WC()->session->get( 'whols_last_added_item' );

		// Last added item is not in the session.
		if ( ! $last_added_item ) {
			return;
		}

		add_action(
			'wp_footer',
			function() use ( $last_added_item ) {
				// should be the cart page.
				if ( is_cart() ) {
					$item_id  = $last_added_item['item_id'];
					$quantity = $last_added_item['quantity'];
					?>
					<script>
						;( function ( $ ) {
							$.ajax({
								url: htga4_params.ajax_url,
								type: 'POST',
								data: {
									'action': 'htga4_add_to_cart_event_ajax_action',
									'p_id': <?php echo esc_html( $item_id ); ?>,
									'quantity': <?php echo esc_html( $quantity ); ?>,
									'nonce' : htga4_params.nonce
								},

								success:function(response) {
									if( response.success && typeof gtag === 'function' ){
										gtag("event", "add_to_cart", response.data);
									}
								},

								error: function(errorThrown){
									alert(errorThrown);
								}
							});
						} )( jQuery );
					   
					</script>
					<?php

					WC()->session->__unset( 'whols_last_added_item' ); // Our job is done, clear the session data.
				}
			}
		);
	}
}

Frontend::instance();
