<?php
/**
 * Main plugin class.
 *
 * @package FluxMedia
 * @since 1.0.0
 */

namespace FluxMedia\Core;

use FluxMedia\Admin\Admin;
use FluxMedia\Api\RestApi;
use FluxMedia\Services\ImageConverter;
use FluxMedia\Services\VideoConverter;
use FluxMedia\Services\ConversionTracker;
use FluxMedia\Utils\Logger;
use Psr\Container\ContainerInterface;

/**
 * Main plugin class that initializes all components.
 *
 * @since 1.0.0
 */
class Plugin {

	/**
	 * Container instance.
	 *
	 * @since 1.0.0
	 * @var ContainerInterface
	 */
	private $container;

	/**
	 * Plugin constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->container = new Container();
	}

	/**
	 * Initialize the plugin.
	 *
	 * @since 1.0.0
	 */
	public function init() {
		// Initialize services.
		$this->init_services();

		// Initialize admin interface.
		if ( is_admin() ) {
			$this->init_admin();
		}

		// Initialize REST API.
		$this->init_rest_api();

		// Initialize hooks.
		$this->init_hooks();
	}

	/**
	 * Initialize services.
	 *
	 * @since 1.0.0
	 */
	private function init_services() {
		// Register services in container.
		$this->container->set( 'logger', new Logger() );
		$this->container->set( 'image_converter', new ImageConverter( $this->container->get( 'logger' ) ) );
		$this->container->set( 'video_converter', new VideoConverter( $this->container->get( 'logger' ) ) );
		$this->container->set( 'conversion_tracker', new ConversionTracker() );
	}

	/**
	 * Initialize admin interface.
	 *
	 * @since 1.0.0
	 */
	private function init_admin() {
		$admin = new Admin( $this->container );
		$admin->init();
	}

	/**
	 * Initialize REST API.
	 *
	 * @since 1.0.0
	 */
	private function init_rest_api() {
		$rest_api = new RestApi( $this->container );
		$rest_api->init();
	}

	/**
	 * Initialize WordPress hooks.
	 *
	 * @since 1.0.0
	 */
	private function init_hooks() {
		// Hook into media uploads.
		add_action( 'add_attachment', [ $this, 'handle_media_upload' ] );
		add_action( 'edit_attachment', [ $this, 'handle_media_upload' ] );

		// Hook into async processing.
		add_action( 'wp_ajax_flux_media_convert_media', [ $this, 'handle_async_conversion' ] );
		add_action( 'wp_ajax_nopriv_flux_media_convert_media', [ $this, 'handle_async_conversion' ] );

		// Cleanup hook.
		add_action( 'flux_media_cleanup', [ $this, 'cleanup_old_files' ] );
	}

	/**
	 * Handle media upload for conversion.
	 *
	 * @since 1.0.0
	 * @param int $attachment_id The attachment ID.
	 */
	public function handle_media_upload( $attachment_id ) {
		$attachment = get_post( $attachment_id );
		if ( ! $attachment || 'attachment' !== $attachment->post_type ) {
			return;
		}

		$mime_type = get_post_mime_type( $attachment_id );
		if ( ! $mime_type ) {
			return;
		}

		// Schedule async conversion.
		wp_schedule_single_event( time() + 30, 'flux_media_convert_attachment', [ $attachment_id ] );
	}

	/**
	 * Handle async conversion.
	 *
	 * @since 1.0.0
	 */
	public function handle_async_conversion() {
		// This will be implemented to handle background conversions.
		// TODO: Implement async conversion handling.
	}

	/**
	 * Cleanup old temporary files.
	 *
	 * @since 1.0.0
	 */
	public function cleanup_old_files() {
		// TODO: Implement cleanup of old temporary files.
	}
}
