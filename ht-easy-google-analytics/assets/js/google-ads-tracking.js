/**
 * Google Ads Tracking Script
 *
 * Handles client-side conversion tracking for Google Ads
 *
 * @package HT Easy GA4
 * @since 1.8.0
 */

(function($) {
    'use strict';

    // Google Ads Tracking Module
    window.HTEasyGA4GoogleAds = {

        // Settings from localization
        settings: {},

        /**
         * Initialize tracking
         */
        init: function() {
            this.settings = window.htga4_google_ads || {};

            // Bind events
            this.bindEvents();

            // Initialize WooCommerce tracking if available
            if (this.settings.is_woocommerce) {
                this.initWooCommerceTracking();
            }

            // Debug mode
            if (this.settings.settings && this.settings.settings.debug_mode) {
                this.debug('Google Ads Tracking Initialized', this.settings);
            }
        },

        /**
         * Bind general events
         */
        bindEvents: function() {
            var self = this;

            // Track custom conversions via data attributes
            $(document).on('click', '[data-htga4-conversion]', function(e) {
                var conversionType = $(this).data('htga4-conversion');
                var conversionValue = $(this).data('htga4-value') || 0;
                var conversionLabel = $(this).data('htga4-label') || '';

                self.trackConversion(conversionType, conversionValue, conversionLabel);
            });

            // Listen for custom conversion events
            $(document).on('htga4:track-conversion', function(e, data) {
                self.trackConversion(data.type, data.value, data.label);
            });
        },

        /**
         * Initialize WooCommerce tracking
         */
        initWooCommerceTracking: function() {
            var self = this;

            // Track add to cart on product pages
            if (this.settings.settings && this.settings.settings.track_add_to_cart) {

                // Single product add to cart
                $(document).on('click', '.single_add_to_cart_button', function(e) {
                    var $button = $(this);
                    var $form = $button.closest('form.cart');

                    if ($form.length) {
                        var quantity = $form.find('input[name="quantity"]').val() || 1;
                        var productId = $form.find('[name="add-to-cart"]').val() || $form.find('[name="product_id"]').val();

                        if (productId) {
                            self.trackAddToCart(productId, quantity);
                        }
                    }
                });

                // Archive/shop page add to cart
                $(document).on('click', '.add_to_cart_button:not(.product_type_variable)', function(e) {
                    var $button = $(this);
                    var productId = $button.data('product_id');
                    var quantity = $button.data('quantity') || 1;

                    if (productId) {
                        self.trackAddToCart(productId, quantity);
                    }
                });

                // AJAX add to cart
                $(document).on('added_to_cart', function(e, fragments, cart_hash, $button) {
                    if ($button && $button.length) {
                        var productId = $button.data('product_id');
                        var quantity = $button.data('quantity') || 1;

                        if (productId) {
                            self.trackAddToCart(productId, quantity);
                        }
                    }
                });
            }
        },

        /**
         * Track add to cart event
         */
        trackAddToCart: function(productId, quantity) {
            var self = this;

            // Get product data if available
            var productData = {
                id: productId,
                quantity: parseInt(quantity) || 1
            };

            // Try to get product price from data attributes
            var $product = $('[data-product_id="' + productId + '"]');
            if ($product.length) {
                productData.price = parseFloat($product.data('product_price')) || 0;
                productData.name = $product.data('product_name') || '';
            }

            // Log conversion (only for tracking stats, not GA4 event)
            this.logConversion('add_to_cart', productData.price * productData.quantity);

            // Show upgrade prompt after 5 add to carts
            this.checkUpgradePrompt('add_to_cart', 5,
                'Upgrade to Pro for advanced cart abandonment tracking and recovery!');
        },

        /**
         * Track general conversion (ONLY fires if conversion label is provided)
         */
        trackConversion: function(type, value, label) {
            // Must have conversion ID configured
            if (!this.settings.settings || !this.settings.settings.conversion_id) {
                this.debug('Conversion tracking not configured');
                return;
            }

            // Must have conversion label - do NOT fire without label
            if (!label || label === '') {
                this.debug('Conversion label missing - conversion NOT tracked');
                return;
            }

            var conversionId = 'AW-' + this.settings.settings.conversion_id;
            var sendTo = conversionId + '/' + label;

            // Track with gtag (conversion only, no GA4 events)
            if (typeof gtag !== 'undefined') {
                gtag('event', 'conversion', {
                    'send_to': sendTo,
                    'value': parseFloat(value) || 0,
                    'currency': this.settings.currency
                });

                this.debug('Conversion tracked', {
                    type: type,
                    value: value,
                    send_to: sendTo
                });
            }

            // Log conversion
            this.logConversion(type, value);
        },

        /**
         * Log conversion to server
         */
        logConversion: function(type, value) {
            $.ajax({
                url: this.settings.ajax_url,
                type: 'POST',
                data: {
                    action: 'htga4_track_conversion',
                    type: type,
                    value: value || 0,
                    currency: this.settings.currency,
                    nonce: this.settings.nonce
                },
                success: function(response) {
                    if (response.success) {
                        HTEasyGA4GoogleAds.debug('Conversion logged', response.data);
                    }
                }
            });
        },

        /**
         * Get cart total from page
         */
        getCartTotal: function() {
            var total = 0;

            // Try different selectors
            var $total = $('.order-total .amount, .cart-subtotal .amount').first();
            if ($total.length) {
                var text = $total.text().replace(/[^0-9.,]/g, '');
                total = parseFloat(text.replace(',', '.')) || 0;
            }

            return total;
        },

        /**
         * Check if should show upgrade prompt
         */
        checkUpgradePrompt: function(event, threshold, message) {
            var storageKey = 'htga4_' + event + '_count';
            var count = parseInt(localStorage.getItem(storageKey) || 0) + 1;

            localStorage.setItem(storageKey, count);

            // Show upgrade prompt at threshold
            if (count === threshold) {
                this.showUpgradePrompt(message);
            }
        },

        /**
         * Show upgrade prompt
         */
        showUpgradePrompt: function(message) {
            // Check if we have a modal function
            if (typeof htga4ShowUpgradeModal === 'function') {
                htga4ShowUpgradeModal('tracking', message);
            } else {
                // Fallback to console message
                console.log('🚀 ' + message);
            }
        },

        /**
         * Debug logging
         */
        debug: function(message, data) {
            if (this.settings.settings && this.settings.settings.debug_mode) {
                console.log('HT Easy GA4 - Google Ads:', message, data || '');
            }
        }
    };

    // Initialize when DOM is ready
    $(document).ready(function() {
        HTEasyGA4GoogleAds.init();
    });

    // Also initialize on AJAX complete for dynamic content
    $(document).ajaxComplete(function() {
        // Re-bind events for dynamically loaded content
        HTEasyGA4GoogleAds.bindEvents();
    });

})(jQuery);