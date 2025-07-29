<?php
namespace Ht_Easy_Ga4\Inspector;

if ( ! defined( 'ABSPATH' ) ) {
	exit(); // Exit if accessed directly.
}

/**
 * GA4 Inspector Class
 * Inspects GA4 tracking codes on the website
 */
class Inspector {
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
	 * @return Inspector
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
		// Only load if enabled
		if ( ! $this->is_enabled() ) {
			return;
		}

		// Add frontend inspection
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_inspection_script' ) );
		add_action( 'wp_footer', array( $this, 'output_inspection_ui' ) );

		// Add query parameter handler
		add_action( 'init', array( $this, 'handle_inspection_request' ) );
	}

	/**
	 * Check if GA4 inspector is enabled
	 *
	 * @return boolean
	 */
	public function is_enabled() {
		return htga4_get_option( 'enable_inspector', false );
	}

	/**
	 * Enqueue inspection script
	 */
	public function enqueue_inspection_script() {
		wp_enqueue_script(
			'htga4-detector',
			HT_EASY_GA4_URL . 'includes/inspector/assets/js/ga4-detector.js',
			array(),
			HT_EASY_GA4_VERSION,
			true
		);
	}

	/**
	 * Output inspection UI in footer
	 */
	public function output_inspection_ui() {
		// Only show if query parameter is present
		if ( ! isset( $_GET['htga4_inspector'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}

		?>
		<style>
		/* Theme 1: Light Blue */
		#htga4-inspector-ui {
			position: fixed;
			bottom: 24px;
			right: 24px;
			background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
			border: 1px solid #0ea5e9;
			border-radius: 12px;
			box-shadow: 0 8px 25px -5px rgba(14, 165, 233, 0.15), 0 4px 10px -2px rgba(14, 165, 233, 0.1);
			z-index: 9999;
			width: 380px;
			font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
			animation: htga4FadeIn 0.3s ease-out;
		}
		
		@keyframes htga4FadeIn {
			from {
				opacity: 0;
				transform: translateY(10px);
			}
			to {
				opacity: 1;
				transform: translateY(0);
			}
		}
		
		#htga4-inspector-header {
			background: rgba(14, 165, 233, 0.1);
			padding: 16px 20px;
			border-bottom: 1px solid rgba(14, 165, 233, 0.2);
			display: flex;
			align-items: center;
			justify-content: space-between;
		}
		
		#htga4-inspector-title {
			color: #0c4a6e;
			font-size: 16px;
			font-weight: 700;
			margin: 0;
		}
		
		#htga4-inspector-actions {
			display: flex;
			align-items: center;
			gap: 8px;
		}
		
		#htga4-inspector-close {
			background: none;
			border: none;
			color: #6b7280;
			font-size: 16px;
			cursor: pointer;
			padding: 4px;
			border-radius: 4px;
			transition: all 0.2s ease;
			line-height: 1;
		}
		
		#htga4-inspector-close:hover {
			background: #f3f4f6;
			color: #374151;
		}
		
		#htga4-inspector-content {
			padding: 20px;
		}
		
		.htga4-section-title {
			color: #0c4a6e;
			font-size: 12px;
			font-weight: 600;
			text-transform: uppercase;
			letter-spacing: 0.5px;
			margin: 0 0 12px 0;
		}
		
		.htga4-tracking-id {
			background: rgba(255, 255, 255, 0.8);
			border: 1px solid #0ea5e9;
			border-radius: 8px;
			padding: 10px 12px;
			font-family: 'SF Mono', Monaco, 'Cascadia Code', 'Roboto Mono', Consolas, 'Courier New', monospace;
			font-size: 13px;
			color: #0369a1;
			margin: 6px 0;
			text-align: center;
			font-weight: 500;
			cursor: pointer;
			transition: all 0.2s ease;
		}
		
		.htga4-tracking-id:hover {
			background: rgba(255, 255, 255, 1);
			border-color: #0284c7;
		}
		
		.htga4-divider {
			height: 1px;
			background: #e5e7eb;
			margin: 16px 0;
		}
		
		.htga4-status-item {
			display: flex;
			align-items: center;
			justify-content: space-between;
			padding: 8px 0;
			font-size: 14px;
		}
		
		.htga4-status-label {
			color: #0c4a6e;
			font-weight: 500;
		}
		
		.htga4-status-value {
			color: #0369a1;
			font-weight: 600;
		}
		
		.htga4-loading {
			display: flex;
			align-items: center;
			justify-content: center;
			padding: 32px 20px;
			color: #6b7280;
			font-size: 14px;
		}
		
		.htga4-spinner {
			width: 16px;
			height: 16px;
			border: 2px solid #e5e7eb;
			border-top: 2px solid #6b7280;
			border-radius: 50%;
			animation: htga4Spin 1s linear infinite;
			margin-right: 8px;
		}
		
		@keyframes htga4Spin {
			0% { transform: rotate(0deg); }
			100% { transform: rotate(360deg); }
		}
		</style>

		<div id="htga4-inspector-ui">
			<div id="htga4-inspector-header">
				<h3 id="htga4-inspector-title">GA4 Inspector</h3>
				<div id="htga4-inspector-actions">
					<button id="htga4-inspector-close" onclick="document.getElementById('htga4-inspector-ui').style.display='none'" title="Close">×</button>
				</div>
			</div>
			<div id="htga4-inspector-content">
				<div id="htga4-inspection-results">
					<div class="htga4-loading">
						<div class="htga4-spinner"></div>
						<span>Scanning...</span>
					</div>
				</div>
			</div>
		</div>

		<script>
		document.addEventListener('DOMContentLoaded', function() {
			setTimeout(function() {
				if (typeof window.GA4Detection !== 'undefined') {
					const status = window.GA4Detection.getGA4Status();
					const resultsDiv = document.getElementById('htga4-inspection-results');
					
					let html = '';
					
					// Check for multiple tracking IDs
					const trackingIds = window.GA4Detection.getAllGA4TrackingIds();
					
					// Tracking IDs section
					html += '<h4 class="htga4-section-title">TRACKING IDS (' + trackingIds.length + ' FOUND)</h4>';
					
					if (trackingIds.length === 0) {
						html += '<div class="htga4-tracking-id" style="color: #6b7280;">No tracking IDs found</div>';
					} else {
						trackingIds.forEach(id => {
							html += '<div class="htga4-tracking-id">' + id.replace(/[<>]/g, '') + '</div>';
						});
					}
					
					html += '<div class="htga4-divider"></div>';
					
					// GA4 Status section
					html += '<h4 class="htga4-section-title">GA4 STATUS</h4>';
					
					html += '<div class="htga4-status-item">';
					html += '<span class="htga4-status-label">gtag Function</span>';
					html += '<span class="htga4-status-value">' + (status.hasGtag ? 'Available' : 'Not Found') + '</span>';
					html += '</div>';
					
					html += '<div class="htga4-status-item">';
					html += '<span class="htga4-status-label">GA4 Script</span>';
					html += '<span class="htga4-status-value">' + (status.hasGtagScript ? 'Loaded' : 'Not Loaded') + '</span>';
					html += '</div>';
					
					resultsDiv.innerHTML = html;
				} else {
					document.getElementById('htga4-inspection-results').innerHTML = '<div class="htga4-loading"><span class="htga4-status-error">❌ Inspection script not loaded</span></div>';
				}
			}, 500); // Small delay for better UX
		});
		</script>
		<?php
	}

	/**
	 * Handle inspection request via query parameter
	 */
	public function handle_inspection_request() {
		// Only process if query parameter is present
		if ( ! isset( $_GET['htga4_inspector'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}

		// Add noindex to prevent search engines from indexing this page
		add_action( 'wp_head', array( $this, 'add_noindex_meta' ) );
	}

	/**
	 * Add noindex meta tag
	 */
	public function add_noindex_meta() {
		echo '<meta name="robots" content="noindex, nofollow">' . "\n";
	}
}

Inspector::instance(); 