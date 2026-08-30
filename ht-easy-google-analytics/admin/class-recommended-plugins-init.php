<?php
namespace Ht_Easy_Ga4\Admin;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Recommended_Plugins_Init{
    public $recommended_plugins_instance = null;

    /**
     * [$_instance]
     *
     * @var null
     */
    private static $_instance = null;

    /**
     * [instance] Initializes a singleton instance
     *
     * @return Recommended_Plugins_Init
     */
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function __construct() {
        $this->recommended_plugins_instance = Recommended_Plugins::instance(
            array(
                'text_domain'       => 'ht-easy-ga4',
                'parent_menu_slug'  => 'ht-easy-ga4-setting-page',
                'menu_capability'   => 'manage_options',
                'menu_page_slug'    => 'ga4-recommendations',
                'priority'          => 24,
                'assets_url'        => HT_EASY_GA4_URL.'admin/assets',
                'hook_suffix'       => 'ht-easy-ga4_page_ga4-recommendations'
            )
        );

        add_filter( 'htga4_recommended_plugins_tab_list', array( $this, 'add_tabs' ) );
    }

    public function add_tabs($tab_list){

        // ShopLentor is shown in "Recommended Plugins" on stores that already run
        // WooCommerce, and in the "WooCommerce" tab on sites that do not.
        $woocommerce_active = class_exists( 'WooCommerce' );

        $shoplentor = array(
            'slug'      => 'woolentor-addons',
            'location'  => 'woolentor_addons_elementor.php',
            'name'      => esc_html__( 'ShopLentor – All-in-One WooCommerce Growth & Store Enhancement Plugin', 'ht-easy-ga4' )
        );

        $tab_list[] = array(

            'title' => esc_html__( 'Recommended Plugins', 'ht-easy-ga4' ),
            'active' => true,
            'plugins' => array_merge(

                $woocommerce_active ? array( $shoplentor ) : array(),

                array(

                    array(
                        'slug'      => 'ht-contactform',
                        'location'  => 'contact-form-widget-elementor.php',
                        'name'      => esc_html__( 'HT Contact Form – Drag & Drop Form Builder for WordPress', 'ht-easy-ga4' )
                    ),

                    array(
                        'slug'      => 'support-genix-lite',
                        'location'  => 'support-genix-lite.php',
                        'name'      => esc_html__( 'Support Genix – Helpdesk, AI Chatbot, Knowledge Base & Customer Support Ticketing System', 'ht-easy-ga4' )
                    ),

                    array(
                        'slug'      => 'kelune-crm',
                        'location'  => 'kelune-crm.php',
                        'name'      => esc_html__( 'Kelune CRM – Contact Management, Email Marketing, Newsletter & Marketing Automation', 'ht-easy-ga4' )
                    ),

                    array(
                        'slug'      => 'hashbar-wp-notification-bar',
                        'location'  => 'init.php',
                        'name'      => esc_html__( 'HashBar – Announcement, Notification Bar & Popup Campaign', 'ht-easy-ga4' )
                    ),

                    array(
                        'slug'      => 'ht-mega-for-elementor',
                        'location'  => 'htmega_addons_elementor.php',
                        'name'      => esc_html__( 'HT Mega Addons for Elementor – Elementor Widgets & Template Builder', 'ht-easy-ga4' )
                    ),

                    array(
                        'slug'      => 'insert-headers-and-footers-script',
                        'location'  => 'init.php',
                        'name'      => esc_html__( 'Insert Headers and Footers Code – HT Script', 'ht-easy-ga4' )
                    ),

                    array(
                        'slug'      => 'wp-plugin-manager',
                        'location'  => 'plugin-main.php',
                        'name'      => esc_html__( 'WP Plugin Manager – Deactivate plugins per page', 'ht-easy-ga4' )
                    ),

                    array(
                        'slug'      => 'cookieray',
                        'location'  => 'cookieray.php',
                        'name'      => esc_html__( 'CookieRay – Cookie Banner for Cookie Consent (GDPR/CCPA Compliant)', 'ht-easy-ga4' )
                    ),

                    array(
                        'slug'      => 'cf7-extensions-pro',
                        'location'  => 'cf7-extensions-pro.php',
                        'name'      => esc_html__( 'Extensions For CF7 Pro', 'ht-easy-ga4' ),
                        'link'      => 'https://hasthemes.com/plugins/cf7-extensions/',
                        'author_link'=> 'https://hasthemes.com/',
                        'description'=> esc_html__( 'Contact Form7 Extensions plugin is a fantastic WordPress plugin that enriches the functionalities of Contact Form 7.This all-in-one WordPress plugin will help you turn any contact page into a well-organized, engaging tool for communicating with your website visitors by providing tons of advanced features like drag and drop file upload, repeater field, trigger error for already submitted forms, popup form response, country flags and dial codes with a telephone input field and acceptance field, etc. in addition to its basic features.', 'ht-easy-ga4' ),
                    ),

                    array(
                        'slug'      => 'htmega-pro',
                        'location'  => 'htmega_pro.php',
                        'name'      => esc_html__( 'HT Mega Pro', 'ht-easy-ga4' ),
                        'link'      => 'https://hasthemes.com/plugins/ht-mega-pro/',
                        'author_link'=> 'https://hasthemes.com/',
                        'description'=> esc_html__( 'HTMega is an absolute addon for elementor that includes 80+ elements & 360 Blocks with unlimited variations. HT Mega brings limitless possibilities. Embellish your site with the elements of HT Mega.', 'ht-easy-ga4' ),
                    ),

                )
            )
        );

        $tab_list[] = array(

            'title' => esc_html__( 'WooCommerce', 'ht-easy-ga4' ),
            'plugins' => array_merge(

                $woocommerce_active ? array() : array( $shoplentor ),

                array(

                    array(
                        'slug'      => 'recurio',
                        'location'  => 'recurio.php',
                        'name'      => esc_html__( 'Recurio – Ultimate Subscription for WooCommerce', 'ht-easy-ga4' )
                    ),

                    array(
                        'slug'      => 'whols-pro',
                        'location'  => 'whols-pro.php',
                        'name'      => esc_html__( 'Whols Pro', 'ht-easy-ga4' ),
                        'link'      => 'https://hasthemes.com/plugins/whols-woocommerce-wholesale-prices/',
                        'author_link'=> 'https://hasthemes.com/',
                        'description'=> esc_html__( 'Whols is an outstanding WordPress plugin for WooCommerce that allows store owners to set wholesale prices for the products of their online stores. This plugin enables you to show special wholesale prices to the wholesaler. Users can easily request to become a wholesale customer by filling out a simple online registration form. Once the registration is complete, the owner of the store will be able to review the request and approve the request either manually or automatically.', 'ht-easy-ga4' ),
                    ),

                )
            )
        );

        $tab_list[] = array(

            'title' => esc_html__( 'Popular', 'ht-easy-ga4' ),
            'plugins' => array(

                array(
                    'slug'      => 'wp-plugin-manager',
                    'location'  => 'plugin-main.php',
                    'name'      => esc_html__( 'WP Plugin Manager – Deactivate plugins per page', 'ht-easy-ga4' )
                ),

                array(
                    'slug'      => 'cookieray',
                    'location'  => 'cookieray.php',
                    'name'      => esc_html__( 'CookieRay – Cookie Banner for Cookie Consent (GDPR/CCPA Compliant)', 'ht-easy-ga4' )
                ),

                array(
                    'slug'      => 'insert-headers-and-footers-script',
                    'location'  => 'init.php',
                    'name'      => esc_html__( 'Insert Headers and Footers Code – HT Script', 'ht-easy-ga4' )
                ),

                array(
                    'slug'      => 'pixelavo',
                    'location'  => 'pixelavo.php',
                    'name'      => esc_html__( 'Pixelavo – Server Side Tracking & Pixel + AI Ads Tools', 'ht-easy-ga4' )
                ),

                array(
                    'slug'      => 'courseglade-lms',
                    'location'  => 'courseglade-lms.php',
                    'name'      => esc_html__( 'CourseGlade LMS – Online Course & eLearning Platform', 'ht-easy-ga4' )
                ),

            )
        );

        return $tab_list;
    }
}

Recommended_Plugins_Init::instance();
