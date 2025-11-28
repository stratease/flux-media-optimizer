<?php
/**
 * Webhook REST API controller for Flux Media Optimizer plugin.
 *
 * @package FluxMedia
 * @since 3.0.0
 */

namespace FluxMedia\App\Http\Controllers;

use FluxMedia\App\Services\ExternalOptimizationProvider;
use FluxMedia\App\Services\Logger;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Handles webhook endpoints for external service callbacks.
 *
 * @since 3.0.0
 */
class WebhookController extends BaseController {

	/**
	 * External optimization provider instance.
	 *
	 * @since 3.0.0
	 * @var ExternalOptimizationProvider
	 */
	private $external_provider;

	/**
	 * Constructor.
	 *
	 * @since 3.0.0
	 * @param ExternalOptimizationProvider $external_provider External provider instance.
	 */
	public function __construct( ExternalOptimizationProvider $external_provider ) {
		$this->external_provider = $external_provider;
		parent::__construct( new Logger() );
	}

	/**
	 * Register REST API routes.
	 *
	 * @since 3.0.0
	 */
	public function register_routes() {
		register_rest_route( 'flux-media-optimizer/v1', '/webhook', [
			'methods' => 'POST',
			'callback' => [ $this, 'handle_webhook' ],
			'permission_callback' => [ $this, 'verify_webhook' ],
		] );
	}

	/**
	 * Verify webhook request.
	 *
	 * @since 3.0.0
	 * @param WP_REST_Request $request Request object.
	 * @return bool True if request is valid.
	 */
	public function verify_webhook( WP_REST_Request $request ) {
		// Basic verification - can be enhanced with signature checking.
		return true;
	}

	/**
	 * Handle webhook callback.
	 *
	 * @since 3.0.0
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response Response object.
	 */
	public function handle_webhook( WP_REST_Request $request ) {
		return $this->external_provider->handle_webhook_callback( $request );
	}

	/**
	 * Get webhook URL for external service.
	 *
	 * @since 3.0.0
	 * @return string Webhook URL.
	 */
	public static function get_webhook_url() {
		return rest_url( 'flux-media-optimizer/v1/webhook' );
	}
}

