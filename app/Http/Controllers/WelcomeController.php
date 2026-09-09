<?php
/**
 * Welcome modal REST controller.
 *
 * @package FluxMedia
 * @since 4.3.1
 */

namespace FluxMedia\App\Http\Controllers;

use FluxMedia\App\Services\WelcomeService;
use FluxMedia\FluxPlugins\Common\Logger\Logger;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Marks the first-activation welcome modal as viewed.
 *
 * @since 4.3.1
 */
class WelcomeController extends BaseController {

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
			'/welcome/viewed',
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
	 * Clear the once-ever welcome option.
	 *
	 * @since 4.3.1
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function mark_viewed( WP_REST_Request $request ) {
		WelcomeService::mark_viewed();

		return $this->create_success_response(
			[ 'viewed' => true ],
			__( 'Welcome marked as viewed.', 'flux-media-optimizer' )
		);
	}

	/**
	 * Require manage_options for welcome updates.
	 *
	 * @since 4.3.1
	 * @param WP_REST_Request $request Request object.
	 * @return bool
	 */
	public function check_permissions( WP_REST_Request $request ) {
		return current_user_can( 'manage_options' );
	}
}
