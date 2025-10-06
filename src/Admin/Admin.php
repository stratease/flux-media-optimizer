<?php
/**
 * Admin interface class.
 *
 * @package FluxMedia
 * @since 1.0.0
 */

namespace FluxMedia\Admin;

use FluxMedia\Core\Container;
use FluxMedia\Services\ImageConverter;
use FluxMedia\Services\VideoConverter;
use FluxMedia\Services\ConversionTracker;

/**
 * Admin interface for Flux Media plugin.
 *
 * @since 1.0.0
 */
class Admin {

	/**
	 * Container instance.
	 *
	 * @since 1.0.0
	 * @var Container
	 */
	private $container;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 * @param Container $container Container instance.
	 */
	public function __construct( Container $container ) {
		$this->container = $container;
	}

	/**
	 * Initialize admin interface.
	 *
	 * @since 1.0.0
	 */
	public function init() {
		add_action( 'admin_menu', [ $this, 'add_admin_menu' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_scripts' ] );
		add_action( 'admin_init', [ $this, 'register_settings' ] );
	}

	/**
	 * Add admin menu pages.
	 *
	 * @since 1.0.0
	 */
	public function add_admin_menu() {
		// Main menu page - Overview.
		add_menu_page(
			__( 'Flux Media', 'flux-media' ),
			__( 'Flux Media', 'flux-media' ),
			'manage_options',
			'flux-media',
			[ $this, 'render_main_page' ],
			'dashicons-format-video',
			30
		);

		// TODO: Re-add submenu pages with proper redirect functionality
		// The redirect approach was causing issues, need to implement a better solution
		// for integrating React Router hash navigation with WordPress admin menu URLs
		// 
		// Previous submenu pages that were removed:
		// - Overview (flux-media-overview)
		// - Settings (flux-media-settings) 
		// - Bulk Operations (flux-media-bulk)
		// - Logs (flux-media-logs)
	}

	/**
	 * Enqueue admin scripts and styles.
	 *
	 * @since 1.0.0
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_admin_scripts( $hook ) {
		// Only load on Flux Media pages.
		if ( strpos( $hook, 'flux-media' ) === false ) {
			return;
		}

		// Enqueue React app.
		wp_enqueue_script(
			'flux-media-admin',
			FLUX_MEDIA_PLUGIN_URL . 'assets/js/dist/admin.bundle.js',
			[],
			FLUX_MEDIA_VERSION,
			true
		);

		// Localize script with WordPress data.
		wp_localize_script( 'flux-media-admin', 'fluxMediaAdmin', [
			'apiUrl' => rest_url(),
			'nonce' => wp_create_nonce( 'wp_rest' ),
			'strings' => [
				'loading' => __( 'Loading...', 'flux-media' ),
				'error' => __( 'An error occurred', 'flux-media' ),
				'success' => __( 'Operation completed successfully', 'flux-media' ),
			],
		] );

		// Add admin styles.
		wp_add_inline_style( 'wp-admin', $this->get_admin_styles() );
	}

	/**
	 * Register plugin settings.
	 *
	 * @since 1.0.0
	 */
	public function register_settings() {
		register_setting( 'flux_media_settings', 'flux_media_options' );
	}


	/**
	 * Render main page with React Router.
	 *
	 * @since 1.0.0
	 */
	public function render_main_page() {
		?>
		<div class="wrap">
			<div id="flux-media-app"></div>
		</div>
		<?php
	}


	/**
	 * Get admin styles.
	 *
	 * @since 1.0.0
	 * @return string Admin styles.
	 */
	private function get_admin_styles() {
		return '
			#flux-media-overview,
			#flux-media-settings,
			#flux-media-bulk,
			#flux-media-logs {
				margin-top: 20px;
			}
			
			.flux-media-loading {
				text-align: center;
				padding: 40px;
			}
			
			.flux-media-error {
				background: #f8d7da;
				color: #721c24;
				padding: 15px;
				border-radius: 4px;
				margin: 20px 0;
			}
		';
	}
}
