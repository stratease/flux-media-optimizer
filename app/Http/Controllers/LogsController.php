<?php
/**
 * Logs REST API controller for Flux Media plugin.
 *
 * @package FluxMedia
 * @since 0.1.0
 */

namespace FluxMedia\App\Http\Controllers;

use FluxMedia\App\Services\LogsService;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Handles logs REST API endpoints.
 *
 * @since 0.1.0
 */
class LogsController extends BaseController {

	/**
	 * Logs service instance.
	 *
	 * @since 0.1.0
	 * @var LogsService
	 */
	private $logs_service;

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 * @param LogsService $logs_service Logs service instance.
	 */
	public function __construct( LogsService $logs_service ) {
		$this->logs_service = $logs_service;
		parent::__construct( new \FluxMedia\App\Services\Logger() );
	}

	/**
	 * Register REST API routes.
	 *
	 * @since 0.1.0
	 */
	public function register_routes() {
		register_rest_route( 'flux-media/v1', '/logs', [
			[
				'methods' => 'GET',
				'callback' => [ $this, 'get_logs' ],
				'permission_callback' => [ $this, 'check_permissions' ],
			],
		] );
	}

	/**
	 * Get logs.
	 *
	 * @since 0.1.0
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response Response object.
	 */
	public function get_logs( WP_REST_Request $request ) {
		try {
			$args = [
				'page' => $request->get_param( 'page' ) ?: 1,
				'per_page' => $request->get_param( 'per_page' ) ?: 20,
				'level' => $request->get_param( 'level' ),
				'search' => $request->get_param( 'search' ),
			];
			
			$logs = $this->logs_service->get_logs( $args );

			return $this->create_success_response( $logs, 'Logs retrieved successfully' );
		} catch ( \Exception $e ) {
			return $this->create_error_response( 'Failed to retrieve logs: ' . $e->getMessage() );
		}
	}

	/**
	 * Check if user has permission to access logs.
	 *
	 * @since 0.1.0
	 * @param WP_REST_Request $request Request object.
	 * @return bool True if user has permission.
	 */
	public function check_permissions( WP_REST_Request $request ) {
		return current_user_can( 'manage_options' );
	}
}
