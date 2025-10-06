<?php
/**
 * REST API endpoints for Flux Media.
 *
 * @package FluxMedia
 * @since 1.0.0
 */

namespace FluxMedia\Api;

use FluxMedia\Core\Container;
use FluxMedia\Services\ImageConverter;
use FluxMedia\Services\VideoConverter;
use FluxMedia\Services\ConversionTracker;
use FluxMedia\Services\QuotaManager;
use FluxMedia\Utils\Logger;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

/**
 * REST API handler for Flux Media plugin.
 *
 * @since 1.0.0
 */
class RestApi {

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
	 * Initialize REST API.
	 *
	 * @since 1.0.0
	 */
	public function init() {
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
		
		// Also register routes immediately if REST API is already initialized
		if ( did_action( 'rest_api_init' ) ) {
			$this->register_routes();
		}
	}

	/**
	 * Register REST API routes.
	 *
	 * @since 1.0.0
	 */
	public function register_routes() {
		$namespace = 'flux-media/v1';

		// Test endpoint to verify API is working.
		register_rest_route( $namespace, '/test', [
			'methods' => 'GET',
			'callback' => [ $this, 'test_endpoint' ],
			'permission_callback' => '__return_true',
		] );

		// System status endpoint.
		register_rest_route( $namespace, '/system/status', [
			'methods' => 'GET',
			'callback' => [ $this, 'get_system_status' ],
			'permission_callback' => [ $this, 'check_admin_permission' ],
		] );

		// Conversion statistics endpoints.
		register_rest_route( $namespace, '/conversions/stats', [
			'methods' => 'GET',
			'callback' => [ $this, 'get_conversion_stats' ],
			'permission_callback' => [ $this, 'check_admin_permission' ],
		] );

		register_rest_route( $namespace, '/conversions/recent', [
			'methods' => 'GET',
			'callback' => [ $this, 'get_recent_conversions' ],
			'permission_callback' => [ $this, 'check_admin_permission' ],
		] );

		// Quota endpoints.
		register_rest_route( $namespace, '/quota/progress', [
			'methods' => 'GET',
			'callback' => [ $this, 'get_quota_progress' ],
			'permission_callback' => [ $this, 'check_admin_permission' ],
		] );

		register_rest_route( $namespace, '/quota/plan', [
			'methods' => 'GET',
			'callback' => [ $this, 'get_plan_info' ],
			'permission_callback' => [ $this, 'check_admin_permission' ],
		] );

		// Plugin options endpoints.
		register_rest_route( $namespace, '/options', [
			'methods' => 'GET',
			'callback' => [ $this, 'get_options' ],
			'permission_callback' => [ $this, 'check_admin_permission' ],
		] );

		register_rest_route( $namespace, '/options', [
			'methods' => 'POST',
			'callback' => [ $this, 'update_options' ],
			'permission_callback' => [ $this, 'check_admin_permission' ],
		] );

		// Conversion job endpoints.
		register_rest_route( $namespace, '/conversions/start', [
			'methods' => 'POST',
			'callback' => [ $this, 'start_conversion' ],
			'permission_callback' => [ $this, 'check_admin_permission' ],
		] );

		register_rest_route( $namespace, '/conversions/cancel/(?P<job_id>[a-zA-Z0-9-]+)', [
			'methods' => 'POST',
			'callback' => [ $this, 'cancel_conversion' ],
			'permission_callback' => [ $this, 'check_admin_permission' ],
		] );

		// Bulk operations.
		register_rest_route( $namespace, '/conversions/bulk', [
			'methods' => 'POST',
			'callback' => [ $this, 'bulk_convert' ],
			'permission_callback' => [ $this, 'check_admin_permission' ],
		] );

		// File operations.
		register_rest_route( $namespace, '/files/delete/(?P<attachment_id>\d+)/(?P<format>[a-zA-Z0-9]+)', [
			'methods' => 'DELETE',
			'callback' => [ $this, 'delete_converted_file' ],
			'permission_callback' => [ $this, 'check_admin_permission' ],
		] );

		// Logs endpoint.
		register_rest_route( $namespace, '/logs', [
			'methods' => 'GET',
			'callback' => [ $this, 'get_logs' ],
			'permission_callback' => [ $this, 'check_admin_permission' ],
		] );

		// Cleanup endpoints.
		register_rest_route( $namespace, '/cleanup/temp-files', [
			'methods' => 'POST',
			'callback' => [ $this, 'cleanup_temp_files' ],
			'permission_callback' => [ $this, 'check_admin_permission' ],
		] );

		register_rest_route( $namespace, '/cleanup/old-records', [
			'methods' => 'POST',
			'callback' => [ $this, 'cleanup_old_records' ],
			'permission_callback' => [ $this, 'check_admin_permission' ],
		] );
	}

	/**
	 * Test endpoint to verify API is working.
	 *
	 * @since 1.0.0
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response Response object.
	 */
	public function test_endpoint( WP_REST_Request $request ) {
		return $this->create_response( [
			'message' => 'Flux Media API is working!',
			'timestamp' => current_time( 'mysql' ),
			'user_id' => get_current_user_id(),
		], 'Test endpoint successful' );
	}

	/**
	 * Check admin permission.
	 *
	 * @since 1.0.0
	 * @param WP_REST_Request $request Request object.
	 * @return bool True if user has admin permission, false otherwise.
	 */
	public function check_admin_permission( $request ) {
		// Check if user can manage options
		if ( ! current_user_can( 'manage_options' ) ) {
			return false;
		}

		// Verify nonce for authenticated requests
		$nonce = $request->get_header( 'X-WP-Nonce' );
		if ( $nonce && ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Create a consistent API response.
	 *
	 * @since 1.0.0
	 * @param mixed  $data Response data.
	 * @param string $message Response message.
	 * @param int    $status HTTP status code.
	 * @return WP_REST_Response Response object.
	 */
	private function create_response( $data, $message = 'Success', $status = 200 ) {
		$response = [
			'success' => $status >= 200 && $status < 300,
			'data' => $data,
			'message' => $message,
			'timestamp' => current_time( 'mysql' ),
		];

		return new WP_REST_Response( $response, $status );
	}

	/**
	 * Create an error response.
	 *
	 * @since 1.0.0
	 * @param string $message Error message.
	 * @param string $code Error code.
	 * @param int    $status HTTP status code.
	 * @return WP_REST_Response Response object.
	 */
	private function create_error_response( $message, $code = 'error', $status = 400 ) {
		$response = [
			'success' => false,
			'data' => null,
			'message' => $message,
			'code' => $code,
			'timestamp' => current_time( 'mysql' ),
		];

		return new WP_REST_Response( $response, $status );
	}

	/**
	 * Get system status.
	 *
	 * @since 1.0.0
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response object.
	 */
	public function get_system_status( WP_REST_Request $request ) {
		try {
			$image_converter = $this->container->get( 'image_converter' );
			$video_converter = $this->container->get( 'video_converter' );

			$status = [
				'imageProcessor' => $image_converter->get_processor_info(),
				'videoProcessor' => $video_converter->get_processor_info(),
				'phpVersion' => PHP_VERSION,
				'memoryLimit' => ini_get( 'memory_limit' ),
				'maxExecutionTime' => (int) ini_get( 'max_execution_time' ),
				'uploadMaxFilesize' => ini_get( 'upload_max_filesize' ),
				'postMaxSize' => ini_get( 'post_max_size' ),
			];

			return $this->create_response( $status, 'System status retrieved successfully' );
		} catch ( Exception $e ) {
			return $this->create_error_response( 
				'Failed to retrieve system status: ' . $e->getMessage(),
				'system_status_error',
				500
			);
		}
	}

	/**
	 * Get conversion statistics.
	 *
	 * @since 1.0.0
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response object.
	 */
	public function get_conversion_stats( WP_REST_Request $request ) {
		$conversion_tracker = $this->container->get( 'conversion_tracker' );

		$filters = [
			'format' => $request->get_param( 'format' ),
			'status' => $request->get_param( 'status' ),
			'dateFrom' => $request->get_param( 'dateFrom' ),
			'dateTo' => $request->get_param( 'dateTo' ),
		];

		// Remove empty filters.
		$filters = array_filter( $filters );

		$stats = $conversion_tracker->get_statistics( $filters );

		return new WP_REST_Response( $stats, 200 );
	}

	/**
	 * Get recent conversions.
	 *
	 * @since 1.0.0
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response object.
	 */
	public function get_recent_conversions( WP_REST_Request $request ) {
		$conversion_tracker = $this->container->get( 'conversion_tracker' );
		$limit = (int) $request->get_param( 'limit' ) ?: 10;

		$conversions = $conversion_tracker->get_recent_conversions( $limit );

		// Convert to array format for JSON response.
		$conversions_array = array_map( function( $conversion ) {
			return [
				'id' => $conversion->id,
				'attachmentId' => $conversion->attachment_id,
				'originalPath' => $conversion->original_path,
				'convertedPath' => $conversion->converted_path,
				'format' => $conversion->format,
				'status' => $conversion->status,
				'sizeReduction' => $conversion->size_reduction,
				'processingTime' => $conversion->processing_time,
				'errorMessage' => $conversion->error_message,
				'createdAt' => $conversion->created_at,
			];
		}, $conversions );

		return new WP_REST_Response( $conversions_array, 200 );
	}

	/**
	 * Get quota progress.
	 *
	 * @since 1.0.0
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response object.
	 */
	public function get_quota_progress( WP_REST_Request $request ) {
		$quota_manager = new QuotaManager();
		$progress = $quota_manager->get_quota_progress();

		return new WP_REST_Response( $progress, 200 );
	}

	/**
	 * Get plan information.
	 *
	 * @since 1.0.0
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response object.
	 */
	public function get_plan_info( WP_REST_Request $request ) {
		$quota_manager = new QuotaManager();
		$plan_info = $quota_manager->get_plan_info();

		return new WP_REST_Response( $plan_info, 200 );
	}

	/**
	 * Get plugin options.
	 *
	 * @since 1.0.0
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response object.
	 */
	public function get_options( WP_REST_Request $request ) {
		$options = \FluxMedia\Core\Options::get_all();

		return new WP_REST_Response( $options, 200 );
	}

	/**
	 * Update plugin options.
	 *
	 * @since 1.0.0
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response object.
	 */
	public function update_options( WP_REST_Request $request ) {
		try {
			$options = $request->get_json_params();

			// Log the received options for debugging
			error_log( 'Flux Media: Received options: ' . print_r( $options, true ) );

			if ( empty( $options ) ) {
				return $this->create_error_response( 'No options provided', 'invalid_options', 400 );
			}

			// Validate and sanitize options
			$sanitized_options = $this->sanitize_options( $options );

			$result = \FluxMedia\Core\Options::update( $sanitized_options );

			if ( $result ) {
				return $this->create_response( [ 'success' => true ], 'Options updated successfully' );
			} else {
				return $this->create_error_response( 'Failed to update options', 'update_failed', 500 );
			}
		} catch ( Exception $e ) {
			error_log( 'Flux Media: Error updating options: ' . $e->getMessage() );
			return $this->create_error_response( 
				'Failed to update options: ' . $e->getMessage(),
				'update_failed',
				500
			);
		}
	}

	/**
	 * Start conversion job.
	 *
	 * @since 1.0.0
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response object.
	 */
	public function start_conversion( WP_REST_Request $request ) {
		$params = $request->get_json_params();
		$attachment_id = (int) $params['attachmentId'];
		$format = sanitize_text_field( $params['format'] );

		// Check quota before starting conversion.
		$quota_manager = new QuotaManager();
		$media_type = in_array( $format, [ 'webp', 'avif' ], true ) ? 'image' : 'video';

		if ( ! $quota_manager->can_convert( $media_type ) ) {
			return new WP_Error( 'quota_exceeded', 'Monthly quota exceeded for ' . $media_type . ' conversions', [ 'status' => 429 ] );
		}

		// TODO: Implement actual conversion job creation.
		$job = [
			'id' => wp_generate_uuid4(),
			'attachmentId' => $attachment_id,
			'format' => $format,
			'status' => 'pending',
			'progress' => 0,
			'createdAt' => current_time( 'mysql' ),
		];

		return new WP_REST_Response( $job, 200 );
	}

	/**
	 * Cancel conversion job.
	 *
	 * @since 1.0.0
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response object.
	 */
	public function cancel_conversion( WP_REST_Request $request ) {
		$job_id = $request->get_param( 'job_id' );

		// TODO: Implement actual job cancellation.
		return new WP_REST_Response( [ 'success' => true ], 200 );
	}

	/**
	 * Bulk convert media.
	 *
	 * @since 1.0.0
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response object.
	 */
	public function bulk_convert( WP_REST_Request $request ) {
		$params = $request->get_json_params();
		$formats = $params['formats'] ?? [];

		// TODO: Implement bulk conversion.
		$job_id = wp_generate_uuid4();

		return new WP_REST_Response( [ 'jobId' => $job_id ], 200 );
	}

	/**
	 * Delete converted file.
	 *
	 * @since 1.0.0
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response object.
	 */
	public function delete_converted_file( WP_REST_Request $request ) {
		$attachment_id = (int) $request->get_param( 'attachment_id' );
		$format = sanitize_text_field( $request->get_param( 'format' ) );

		// TODO: Implement file deletion.
		return new WP_REST_Response( [ 'success' => true ], 200 );
	}

	/**
	 * Get logs.
	 *
	 * @since 1.0.0
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response object.
	 */
	public function get_logs( WP_REST_Request $request ) {
		$level = $request->get_param( 'level' );
		$limit = (int) $request->get_param( 'limit' ) ?: 100;

		// TODO: Implement log retrieval.
		$logs = [];

		return new WP_REST_Response( $logs, 200 );
	}

	/**
	 * Cleanup temporary files.
	 *
	 * @since 1.0.0
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response object.
	 */
	public function cleanup_temp_files( WP_REST_Request $request ) {
		// TODO: Implement temp file cleanup.
		$deleted_count = 0;

		return new WP_REST_Response( [ 'deletedCount' => $deleted_count ], 200 );
	}

	/**
	 * Cleanup old records.
	 *
	 * @since 1.0.0
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response object.
	 */
	public function cleanup_old_records( WP_REST_Request $request ) {
		$params = $request->get_json_params();
		$days = (int) ( $params['days'] ?? 30 );

		$conversion_tracker = $this->container->get( 'conversion_tracker' );
		$deleted_count = $conversion_tracker->cleanup_old_records( $days );

		return new WP_REST_Response( [ 'deletedCount' => $deleted_count ], 200 );
	}

	/**
	 * Sanitize options data.
	 *
	 * @since 1.0.0
	 * @param array $options Raw options data.
	 * @return array Sanitized options data.
	 */
	private function sanitize_options( $options ) {
		$sanitized = [];

		// Define allowed options and their sanitization methods
		$allowed_options = [
			'autoConvert' => 'boolval',
			'quality' => 'intval',
			'webpEnabled' => 'boolval',
			'avifEnabled' => 'boolval',
			'hybridApproach' => 'boolval',
			'av1Enabled' => 'boolval',
			'webmEnabled' => 'boolval',
			'licenseKey' => 'sanitize_text_field',
			'image_webp_quality' => 'intval',
			'image_webp_lossless' => 'boolval',
			'image_avif_quality' => 'intval',
			'image_avif_speed' => 'intval',
			'image_auto_convert' => 'boolval',
			'image_formats' => 'array',
			'hybrid_approach' => 'boolval',
			'video_av1_crf' => 'intval',
			'video_av1_preset' => 'sanitize_text_field',
			'video_webm_crf' => 'intval',
			'video_webm_preset' => 'sanitize_text_field',
			'video_auto_convert' => 'boolval',
			'video_formats' => 'array',
			'async_processing' => 'boolval',
			'cleanup_temp_files' => 'boolval',
			'log_level' => 'sanitize_text_field',
			'max_file_size' => 'intval',
			'conversion_timeout' => 'intval',
			'license_key' => 'sanitize_text_field',
			'license_status' => 'sanitize_text_field',
			'cdn_enabled' => 'boolval',
			'cdn_provider' => 'sanitize_text_field',
			'cdn_api_key' => 'sanitize_text_field',
			'cdn_endpoint' => 'sanitize_text_field',
			'external_conversion_enabled' => 'boolval',
			'external_conversion_provider' => 'sanitize_text_field',
			'external_conversion_api_key' => 'sanitize_text_field',
			'external_conversion_endpoint' => 'sanitize_text_field',
		];

		foreach ( $options as $key => $value ) {
			if ( ! isset( $allowed_options[ $key ] ) ) {
				continue; // Skip unknown options
			}

			$sanitizer = $allowed_options[ $key ];

			switch ( $sanitizer ) {
				case 'boolval':
					$sanitized[ $key ] = (bool) $value;
					break;
				case 'intval':
					$sanitized[ $key ] = (int) $value;
					break;
				case 'array':
					$sanitized[ $key ] = is_array( $value ) ? $value : [];
					break;
				case 'sanitize_text_field':
					$sanitized[ $key ] = sanitize_text_field( $value );
					break;
				default:
					$sanitized[ $key ] = $value;
					break;
			}
		}

		return $sanitized;
	}
}
