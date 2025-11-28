<?php
/**
 * Options REST API controller for Flux Media Optimizer plugin.
 *
 * @package FluxMedia
 * @since 0.1.0
 */

namespace FluxMedia\App\Http\Controllers;

use FluxMedia\App\Services\Settings;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Handles options/settings REST API endpoints.
 *
 * @since 0.1.0
 */
class OptionsController extends BaseController {

	/**
	 * Settings instance.
	 *
	 * @since 0.1.0
	 * @var Settings
	 */
	private $settings;

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 * @param Settings $settings Settings instance.
	 */
	public function __construct( Settings $settings ) {
		$this->settings = $settings;
		parent::__construct( new \FluxMedia\App\Services\Logger() );
	}

	/**
	 * Register REST API routes.
	 *
	 * @since 0.1.0
	 */
	public function register_routes() {
		register_rest_route( 'flux-media-optimizer/v1', '/options', [
			[
				'methods' => 'GET',
				'callback' => [ $this, 'get_options' ],
				'permission_callback' => [ $this, 'check_permissions' ],
			],
			[
				'methods' => 'POST',
				'callback' => [ $this, 'update_options' ],
				'permission_callback' => [ $this, 'check_permissions' ],
				'args' => [
					'options' => [
						'required' => true,
						'type' => 'object',
						'description' => 'Options to update',
					],
				],
			],
		] );
	}

	/**
	 * Get all options.
	 *
	 * @since 0.1.0
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response Response object.
	 */
	public function get_options( WP_REST_Request $request ) {
		try {
			$options = $this->settings->get_all();
			// Include license_key from site option.
			$options['license_key'] = Settings::get_license_key();
			return $this->create_success_response( $options, 'Options retrieved successfully' );
		} catch ( \Exception $e ) {
			return $this->create_error_response( 'Failed to retrieve options: ' . $e->getMessage() );
		}
	}

	/**
	 * Update options.
	 *
	 * @since 0.1.0
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response Response object.
	 */
	public function update_options( WP_REST_Request $request ) {
		try {
			$options = $request->get_param( 'options' );
			
			if ( ! is_array( $options ) ) {
				return $this->create_error_response( 'Invalid options format', 'invalid_options', 400 );
			}

			// Handle license_key separately (stored in site options, not plugin options).
			if ( isset( $options['license_key'] ) ) {
				Settings::set_license_key( $options['license_key'] );
				unset( $options['license_key'] );
			}

			// Update remaining options at once.
			if ( ! empty( $options ) ) {
				$this->settings->update( $options );
			}

			// Get updated options (includes license_key from site option).
			$updated_options = $this->settings->get_all();
			$updated_options['license_key'] = Settings::get_license_key();
			
			return $this->create_success_response( $updated_options, 'Options updated successfully' );
		} catch ( \Exception $e ) {
			return $this->create_error_response( 'Failed to update options: ' . $e->getMessage() );
		}
	}

	/**
	 * Check if user has permission to access options.
	 *
	 * @since 0.1.0
	 * @param WP_REST_Request $request Request object.
	 * @return bool True if user has permission.
	 */
	public function check_permissions( WP_REST_Request $request ) {
		return current_user_can( 'manage_options' );
	}
}
