<?php
/**
 * Review prompt REST controller.
 *
 * @package FluxMedia
 * @since 4.3.1
 */

namespace FluxMedia\App\Http\Controllers;

use FluxMedia\App\Services\ReviewPromptService;
use FluxMedia\FluxPlugins\Common\Logger\Logger;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Marks the once-ever review prompt as viewed/consumed.
 *
 * @since 4.3.1
 */
class ReviewPromptController extends BaseController {

	/**
	 * Constructor.
	 *
	 * @since 4.3.1
	 */
	public function __construct() {
		parent::__construct( Logger::get_instance() );
	}

	/**
	 * Register REST API routes.
	 *
	 * @since 4.3.1
	 * @return void
	 */
	public function register_routes() {
		register_rest_route(
			'flux-media-optimizer/v1',
			'/review-prompt/viewed',
			[
				[
					'methods'             => 'POST',
					'callback'            => [ $this, 'mark_viewed' ],
					'permission_callback' => [ $this, 'check_permissions' ],
				],
			]
		);
	}

	/**
	 * Persist forever-consumed review prompt flag.
	 *
	 * @since 4.3.1
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function mark_viewed( WP_REST_Request $request ) {
		ReviewPromptService::mark_viewed();

		return $this->create_success_response(
			[ 'viewed' => true ],
			__( 'Review prompt marked as viewed.', 'flux-media-optimizer' )
		);
	}

	/**
	 * Require manage_options for review prompt updates.
	 *
	 * @since 4.3.1
	 * @param WP_REST_Request $request Request object.
	 * @return bool
	 */
	public function check_permissions( WP_REST_Request $request ) {
		return current_user_can( 'manage_options' );
	}
}
