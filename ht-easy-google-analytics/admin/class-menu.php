<?php
namespace Ht_Easy_Ga4\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit(); // Exit if accessed directly.
}

/**
 * Menu class for handling all menu related functionality
 */
class Menu {
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
	 * @return Menu
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * Constructor
	 */
	public function __construct() {
		// Register plugin menus
		add_action( 'admin_menu', array( $this, 'register_plugin_menus' ) );

		// Add submenu for upgrade to pro
		add_action( 'admin_menu', array( $this, 'upgrade_submenu' ), 99999 );

		// Set active class using js
        add_action('admin_footer', [ $this, 'menu_item_active_js' ]);
	}

	/**
	 * Register plugin menus
	 *
	 * @return void
	 */
	public function register_plugin_menus() {
		global $submenu;

		add_menu_page(
			__( 'HT Easy GA4', 'ht-easy-ga4' ),
			__( 'HT Easy GA4', 'ht-easy-ga4' ),
			'manage_options',
			'ht-easy-ga4-setting-page',
			array( $this, 'render_plugin_page' ),
			HT_EASY_GA4_URL . 'admin/assets/images/logo.png',
			66
		);

		add_submenu_page(
			'ht-easy-ga4-setting-page',
			__( 'Settings', 'ht-easy-ga4' ),
			__( 'Settings', 'ht-easy-ga4' ),
			'manage_options',
			'ht-easy-ga4-setting-page#/settings/general',
			'__return_null()'
		);

		add_submenu_page(
			'ht-easy-ga4-setting-page',
			__( 'Reports', 'ht-easy-ga4' ),
			__( 'Reports', 'ht-easy-ga4' ),
			'manage_options',
			'ht-easy-ga4-setting-page#/reports/standard',
			'__return_null()'
		);

		if ( isset( $submenu['ht-easy-ga4-setting-page'][0][0] ) ) {
			// Remove the default first default submenu item comes with add_menu_page
			unset($submenu['ht-easy-ga4-setting-page'][0]); 
		}
	}

	/**
	 * Add upgrade submenu
	 *
	 * @return void
	 */
	public function upgrade_submenu() {
		if ( $this->is_pro_plugin_installed() ) { // Already installed pro plugin.
			return;
		}

		add_submenu_page(
			'ht-easy-ga4-setting-page',
			__( 'Upgrade to Pro', 'ht-easy-ga4' ),
			__( 'Upgrade to Pro', 'ht-easy-ga4' ),
			'manage_options',
			'https://hasthemes.com/plugins/google-analytics-plugin-for-wordpress/?utm_source=admin&utm_medium=mainmenu&utm_campaign=free#pricing'
		);
	}

	/**
	 * Render plugin page
	 * 
	 * @return void
	 */
	public function render_plugin_page() {
        ?>
        <div class="wrap">
            <div id="htga4-vue-settings-app"></div>
        </div>
        <?php
	}

    public function menu_item_active_js(){
        $submenu_items = 'li.toplevel_page_ht-easy-ga4-setting-page ul.wp-submenu li:nth-child(2), li.toplevel_page_ht-easy-ga4-setting-page ul.wp-submenu li:nth-child(3)';
        ?>
        <script>
            jQuery(document).ready(function($) {
                const $subMenuItems = $('<?php echo $submenu_items; ?>');
            
                // Function to handle menu activation
                const activateMenuItem = (hash) => {
                    // Remove active class from all menu items first
                    $subMenuItems.removeClass('current active');
                    
                    // Find and activate the matching menu item
                    $subMenuItems.each(function() {
                        const subMenuLink = $(this).find('a').attr('href');
                        if (hash && subMenuLink.indexOf(hash) > -1) {
                            $(this).addClass('current active');
                        }
                    });
                };
                
                // Initialize for page load
                activateMenuItem(window.location.hash);
                
                // Add click event handler to menu items
                $subMenuItems.on('click', function() {
                    const clickedItemHref = $(this).find('a').attr('href');
                    const hashPart = clickedItemHref.split('#')[1];
                    
                    if (hashPart) {
                        // Use timeout to ensure this runs after the default navigation
                        setTimeout(() => {
                            activateMenuItem('#' + hashPart);
                        }, 50);
                    }
                });
                
                // Listen for hash changes to handle browser navigation
                $(window).on('hashchange', function() {
                    activateMenuItem(window.location.hash);
                });
            });
        </script>
        <?php
    }
}

Menu::instance();
