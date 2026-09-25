<?php
/**
 * Bulk conversion REST API controller.
 *
 * @package FluxMedia\App\Http\Controllers
 * @since 4.4.0
 */

namespace FluxMedia\App\Http\Controllers;

use FluxMedia\App\Services\BulkStatsService;
use FluxMedia\FluxPlugins\Common\Logger\Logger;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Exposes bulk conversion queue statistics for the admin UI.
 *
 * @since 4.4.0
 */
class BulkController extends BaseController {

	/**
	 * Bulk stats service.
	 *
	 * @since 4.4.0
	 * @var BulkStatsService
	 */
	private $bulk_stats_service;

	/**
	 * Constructor.
	 *
	 * @since 4.4.0
	 * @param BulkStatsService $bulk_stats_service Bulk stats service.
	 */
	public function __construct( BulkStatsService $bulk_stats_service ) {
		$this->bulk_stats_service = $bulk_stats_service;
		parent::__construct( Logger::get_instance() );
	}

	/**
	 * Register REST API routes.
	 *
	 * @since 4.4.0
	 * @return void
	 */
	public function register_routes() {
		register_rest_route(
			'flux-media-optimizer/v1',
			'/bulk/stats',
			[
				[
					'methods'             => 'GET',
					'callback'            => [ $this, 'get_bulk_stats' ],
					'permission_callback' => [ $this, 'check_permissions' ],
				],
			]
		);
	}

	/**
	 * Return bulk conversion statistics.
	 *
	 * @since 4.4.0
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function get_bulk_stats( WP_REST_Request $request ) {
		try {
			$stats = $this->bulk_stats_service->get_stats();
			return $this->create_success_response( $stats, 'Bulk statistics retrieved successfully' );
		} catch ( \Exception $e ) {
			return $this->create_error_response_from_exception(
				$e,
				__( 'Failed to retrieve bulk statistics.', 'flux-media-optimizer' ),
				'bulk_stats_failed'
			);
		}
	}

	/**
	 * Require manage_options for bulk stats.
	 *
	 * @since 4.4.0
	 * @param WP_REST_Request $request Request object.
	 * @return bool
	 */
	public function check_permissions( WP_REST_Request $request ) {
		return current_user_can( 'manage_options' );
	}
}
