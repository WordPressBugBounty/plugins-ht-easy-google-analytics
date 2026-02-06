<?php
/**
 * Helper Functions for Google Ads Feature Gating
 *
 * Common functions to check for pro features and handle feature gating
 *
 * @package HT Easy GA4
 * @since 1.8.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Check if HT Easy GA4 Pro is active
 *
 * @return bool
 */
if ( ! function_exists( 'htga4_is_pro' ) ) {
	function htga4_is_pro() {
		return defined( 'HT_EASY_GA4_PRO_VERSION' ) ||
		       is_plugin_active( 'ht-easy-google-analytics-pro/ht-easy-google-analytics-pro.php' );
	}
}

/**
 * Check if a specific Google Ads feature is available
 *
 * @param string $feature Feature name to check
 * @return bool
 */
if ( ! function_exists( 'htga4_google_ads_has_feature' ) ) {
	function htga4_google_ads_has_feature( $feature ) {
		// Free features - always available
		$free_features = array(
			'basic_conversion_tracking',
			'manual_setup',
			'purchase_tracking',
			'basic_add_to_cart',
			'form_tracking',
			'debug_mode',
			'basic_reports',
		);

		// Pro features - require pro version
		$pro_features = array(
			'oauth_authentication',
			'enhanced_conversions',
			'server_side_tracking',
			'consent_mode_v2',
			'advanced_reports',
			'google_ads_api',
			'diagnostic_tool',
			'ai_features',
			'custom_conversions',
			'offline_conversions',
			'call_tracking',
			'dynamic_remarketing',
			'shopping_campaigns',
			'white_label',
		);

		// Check if it's a free feature
		if ( in_array( $feature, $free_features, true ) ) {
			return true;
		}

		// Check if it's a pro feature and pro is active
		if ( in_array( $feature, $pro_features, true ) && htga4_is_pro() ) {
			return true;
		}

		return false;
	}
}

/**
 * Get Google Ads manager instance
 *
 * @return object Manager instance (Pro or Free)
 */
if ( ! function_exists( 'htga4_google_ads' ) ) {
	function htga4_google_ads() {
		if ( htga4_is_pro() && class_exists( '\Ht_Easy_Ga4\GoogleAdsPro\Pro_Manager' ) ) {
			// Return pro manager if available
			return \Ht_Easy_Ga4\GoogleAdsPro\Pro_Manager::instance();
		} elseif ( class_exists( '\Ht_Easy_Ga4\GoogleAds\Manager' ) ) {
			// Return free manager
			return \Ht_Easy_Ga4\GoogleAds\Manager::instance();
		}

		return null;
	}
}

/**
 * Display pro feature notice
 *
 * @param string $feature Feature name
 * @param string $message Custom message
 * @return void
 */
if ( ! function_exists( 'htga4_pro_feature_notice' ) ) {
	function htga4_pro_feature_notice( $feature, $message = '' ) {
		if ( ! $message ) {
			$message = __( 'This feature is available in the Pro version.', 'ht-easy-ga4' );
		}

		$upgrade_url = add_query_arg(
			array(
				'utm_source' => 'plugin',
				'utm_medium' => 'feature_gate',
				'utm_content' => $feature,
			),
			'https://hasthemes.com/plugins/ht-easy-google-analytics-pro/'
		);

		?>
		<div class="htga4-pro-feature-notice">
			<div class="htga4-pro-feature-icon">
				<span class="dashicons dashicons-lock"></span>
			</div>
			<div class="htga4-pro-feature-content">
				<h3><?php esc_html_e( 'Pro Feature', 'ht-easy-ga4' ); ?></h3>
				<p><?php echo esc_html( $message ); ?></p>
				<a href="<?php echo esc_url( $upgrade_url ); ?>" target="_blank" class="button button-primary">
					<?php esc_html_e( 'Upgrade to Pro', 'ht-easy-ga4' ); ?>
				</a>
			</div>
		</div>
		<style>
			.htga4-pro-feature-notice {
				background: #fff;
				border: 1px solid #ccd0d4;
				border-left: 4px solid #ff6900;
				box-shadow: 0 1px 1px rgba(0,0,0,.04);
				margin: 20px 0;
				padding: 15px;
				display: flex;
				align-items: center;
			}
			.htga4-pro-feature-icon {
				font-size: 40px;
				color: #ff6900;
				margin-right: 20px;
			}
			.htga4-pro-feature-content h3 {
				margin-top: 0;
				margin-bottom: 10px;
			}
			.htga4-pro-feature-content p {
				margin-bottom: 15px;
			}
		</style>
		<?php
	}
}

/**
 * Get pro feature badge HTML
 *
 * @return string
 */
if ( ! function_exists( 'htga4_pro_badge' ) ) {
	function htga4_pro_badge() {
		return '<span class="htga4-pro-badge">' . esc_html__( 'PRO', 'ht-easy-ga4' ) . '</span>';
	}
}

/**
 * Check if should show upgrade notices
 *
 * @return bool
 */
if ( ! function_exists( 'htga4_show_upgrade_notices' ) ) {
	function htga4_show_upgrade_notices() {
		// Don't show if pro is active
		if ( htga4_is_pro() ) {
			return false;
		}

		// Check if user has capability
		if ( ! current_user_can( 'manage_options' ) ) {
			return false;
		}

		// Check if notices are disabled
		$disabled = get_option( 'htga4_disable_upgrade_notices', false );

		return ! $disabled;
	}
}

/**
 * Get Google Ads settings
 *
 * @param string $key Specific setting key
 * @return mixed
 */
if ( ! function_exists( 'htga4_google_ads_setting' ) ) {
	function htga4_google_ads_setting( $key = null ) {
		$manager = htga4_google_ads();

		if ( ! $manager ) {
			return $key ? null : array();
		}

		$settings = $manager->get_settings();

		if ( $key ) {
			return isset( $settings[ $key ] ) ? $settings[ $key ] : null;
		}

		return $settings;
	}
}

/**
 * Check if Google Ads tracking is enabled
 *
 * @return bool
 */
if ( ! function_exists( 'htga4_google_ads_enabled' ) ) {
	function htga4_google_ads_enabled() {
		return (bool) htga4_google_ads_setting( 'enabled' );
	}
}

/**
 * Get upgrade URL
 *
 * @param string $source Source/context for tracking
 * @return string
 */
if ( ! function_exists( 'htga4_get_upgrade_url' ) ) {
	function htga4_get_upgrade_url( $source = 'general' ) {
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
}

/**
 * Display feature comparison table
 *
 * @return void
 */
if ( ! function_exists( 'htga4_feature_comparison_table' ) ) {
	function htga4_feature_comparison_table() {
		$features = array(
			array(
				'name' => __( 'Basic Conversion Tracking', 'ht-easy-ga4' ),
				'free' => true,
				'pro' => true,
			),
			array(
				'name' => __( 'Manual Setup', 'ht-easy-ga4' ),
				'free' => true,
				'pro' => true,
			),
			array(
				'name' => __( 'One-Click OAuth Setup', 'ht-easy-ga4' ),
				'free' => false,
				'pro' => true,
			),
			array(
				'name' => __( 'Enhanced Conversions', 'ht-easy-ga4' ),
				'free' => false,
				'pro' => true,
			),
			array(
				'name' => __( 'Server-Side Tracking', 'ht-easy-ga4' ),
				'free' => false,
				'pro' => true,
			),
			array(
				'name' => __( 'Consent Mode v2', 'ht-easy-ga4' ),
				'free' => false,
				'pro' => true,
			),
			array(
				'name' => __( 'Advanced Analytics & ROAS', 'ht-easy-ga4' ),
				'free' => false,
				'pro' => true,
			),
			array(
				'name' => __( 'AI-Powered Features', 'ht-easy-ga4' ),
				'free' => false,
				'pro' => true,
			),
			array(
				'name' => __( 'Diagnostic Tools', 'ht-easy-ga4' ),
				'free' => false,
				'pro' => true,
			),
			array(
				'name' => __( 'Priority Support', 'ht-easy-ga4' ),
				'free' => false,
				'pro' => true,
			),
		);

		?>
		<table class="htga4-feature-comparison">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Feature', 'ht-easy-ga4' ); ?></th>
					<th><?php esc_html_e( 'Free', 'ht-easy-ga4' ); ?></th>
					<th><?php esc_html_e( 'Pro', 'ht-easy-ga4' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $features as $feature ) : ?>
				<tr>
					<td><?php echo esc_html( $feature['name'] ); ?></td>
					<td class="text-center">
						<?php if ( $feature['free'] ) : ?>
							<span class="dashicons dashicons-yes" style="color: #46b450;"></span>
						<?php else : ?>
							<span class="dashicons dashicons-no-alt" style="color: #dc3232;"></span>
						<?php endif; ?>
					</td>
					<td class="text-center">
						<?php if ( $feature['pro'] ) : ?>
							<span class="dashicons dashicons-yes" style="color: #46b450;"></span>
						<?php else : ?>
							<span class="dashicons dashicons-no-alt" style="color: #dc3232;"></span>
						<?php endif; ?>
					</td>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<style>
			.htga4-feature-comparison {
				width: 100%;
				border-collapse: collapse;
				margin: 20px 0;
			}
			.htga4-feature-comparison th,
			.htga4-feature-comparison td {
				padding: 10px;
				border: 1px solid #ddd;
			}
			.htga4-feature-comparison th {
				background: #f5f5f5;
				font-weight: 600;
			}
			.htga4-feature-comparison .text-center {
				text-align: center;
			}
		</style>
		<?php
	}
}

/**
 * Get product data in GA4 format
 * Shared helper for all event types
 *
 * @param int $product_id Product or variation ID
 * @return array Product data
 */
if ( ! function_exists( 'htga4_get_product_data' ) ) {
	function htga4_get_product_data( $product_id ) {
		// Check if WooCommerce is active
		if ( ! class_exists( 'WooCommerce' ) ) {
			return array();
		}

		$product = wc_get_product( $product_id );

		if ( ! $product ) {
			return array();
		}

		// Get product category
		$item_category = '';
		$category_ids = $product->get_category_ids();
		if ( ! empty( $category_ids ) ) {
			$category = get_term_by( 'id', $category_ids[0], 'product_cat' );
			if ( $category ) {
				$item_category = $category->name;
			}
		}

		// If on product category page, use current category
		if ( is_product_category() ) {
			$current_category = get_queried_object();
			$item_category = $current_category->name;
		}

		// Build product data
		$product_data = array(
			'item_id' => $product->get_id(),
			'item_name' => $product->get_name(),
			'item_category' => $item_category,
		);

		// Add price (don't show for grouped products)
		if ( $product->is_type( 'variable' ) ) {
			$product_data['price'] = (float) $product->get_variation_regular_price( 'min' );
		} elseif ( ! $product->is_type( 'grouped' ) ) {
			$product_data['price'] = (float) $product->get_regular_price();
		}

		// Add discount if on sale
		if ( $product->get_sale_price() ) {
			$product_data['discount'] = (float) $product->get_regular_price() - (float) $product->get_sale_price();
		}

		// Add variant for variable products
		if ( $product->get_type() === 'variation' ) {
			$product_data['item_variant'] = implode( ', ', $product->get_variation_attributes() );

			// Use parent product category for variation
			$parent_product = wc_get_product( $product->get_parent_id() );
			if ( $parent_product ) {
				$parent_category_ids = $parent_product->get_category_ids();
				if ( ! empty( $parent_category_ids ) ) {
					$parent_category = get_term_by( 'id', $parent_category_ids[0], 'product_cat' );
					if ( $parent_category ) {
						$product_data['item_category'] = $parent_category->name;
					}
				}
			}
		}

		return $product_data;
	}
}

/**
 * Get rich purchase data with items for tracking
 * Shared between GA4 and Google Ads conversion tracking
 *
 * @param int $order_id WooCommerce order ID
 * @return array|false Purchase data or false if invalid
 */
if ( ! function_exists( 'htga4_get_purchase_data' ) ) {
	function htga4_get_purchase_data( $order_id ) {
		if ( ! $order_id || ! class_exists( 'WooCommerce' ) ) {
			return false;
		}

		$order = wc_get_order( $order_id );

		if ( ! $order || ! is_a( $order, 'WC_Order' ) ) {
			return false;
		}

		// Get order items with detailed information
		$items = array();
		foreach ( $order->get_items() as $item ) {
			$product = $item->get_product();

			if ( ! $product ) {
				continue;
			}

			$item_data = $item->get_data();
			$p_id = ! empty( $item_data['variation_id'] ) ? $item_data['variation_id'] : $item_data['product_id'];

			// Get product data using shared helper
			$product_info = htga4_get_product_data( $p_id );

			if ( empty( $product_info ) ) {
				continue; // Skip this item if data is invalid
			}

			// Add quantity
			$product_info['quantity'] = $item->get_quantity();

			$items[] = $product_info;
		}

		// Build complete purchase data
		$purchase_data = array(
			'transaction_id' => $order->get_order_number(),
			'value' => (float) $order->get_total(),
			'tax' => (float) $order->get_cart_tax(), // Product tax only (shipping tax included in shipping cost)
			'shipping' => (float) ( $order->get_shipping_total() + $order->get_shipping_tax() ), // Total shipping cost including tax
			'currency' => $order->get_currency(),
			'coupon' => implode( ', ', ( version_compare( WC()->version, '3.7', '>=' ) ? $order->get_coupon_codes() : $order->get_used_coupons() ) ),
			'affiliation' => get_bloginfo( 'name' ),
			'payment_type' => $order->get_payment_method_title(),
			'items' => $items,
		);

		return $purchase_data;
	}
}

/**
 * Output inline CSS for pro badges and notices
 */
add_action( 'admin_head', function() {
	if ( ! htga4_is_pro() ) {
		?>
		<style>
			.htga4-pro-badge {
				display: inline-block;
				background: #ff6900;
				color: #fff;
				padding: 2px 6px;
				border-radius: 3px;
				font-size: 10px;
				font-weight: bold;
				text-transform: uppercase;
				margin-left: 5px;
				vertical-align: middle;
			}

			.htga4-pro-lock {
				color: #999;
				margin-left: 5px;
			}

			.htga4-pro-feature-disabled {
				opacity: 0.6;
				position: relative;
			}

			.htga4-pro-feature-disabled::after {
				content: 'PRO';
				position: absolute;
				top: 50%;
				left: 50%;
				transform: translate(-50%, -50%);
				background: rgba(255, 105, 0, 0.9);
				color: #fff;
				padding: 5px 15px;
				border-radius: 3px;
				font-weight: bold;
				pointer-events: none;
			}

			.htga4-pro-tooltip {
				position: relative;
				display: inline-block;
				cursor: help;
			}

			.htga4-pro-tooltip:hover::after {
				content: attr(data-tooltip);
				position: absolute;
				bottom: 100%;
				left: 50%;
				transform: translateX(-50%);
				background: #333;
				color: #fff;
				padding: 5px 10px;
				border-radius: 3px;
				white-space: nowrap;
				font-size: 12px;
				margin-bottom: 5px;
				z-index: 1000;
			}
		</style>
		<?php
	}
});