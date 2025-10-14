<?php
/**
 * Status REST API controller for Flux Media plugin.
 *
 * @package FluxMedia
 * @since 0.1.0
 */

namespace FluxMedia\App\Http\Controllers;

use FluxMedia\App\Services\FormatSupportDetector;
use FluxMedia\App\Services\ProcessorDetector;
use FluxMedia\App\Services\ProcessorTypes;
use FluxMedia\App\Services\QuotaManager;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Handles system status REST API endpoints.
 *
 * @since 0.1.0
 */
class StatusController extends BaseController {

	/**
	 * Format support detector instance.
	 *
	 * @since 0.1.0
	 * @var FormatSupportDetector
	 */
	private $format_detector;

	/**
	 * Processor detector instance.
	 *
	 * @since 0.1.0
	 * @var ProcessorDetector
	 */
	private $processor_detector;

	/**
	 * Quota manager instance.
	 *
	 * @since 0.1.0
	 * @var QuotaManager
	 */
	private $quota_manager;

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 * @param FormatSupportDetector $format_detector Format support detector.
	 * @param ProcessorDetector     $processor_detector Processor detector.
	 * @param QuotaManager          $quota_manager Quota manager.
	 */
	public function __construct( FormatSupportDetector $format_detector, ProcessorDetector $processor_detector, QuotaManager $quota_manager ) {
		$this->format_detector = $format_detector;
		$this->processor_detector = $processor_detector;
		$this->quota_manager = $quota_manager;
		parent::__construct( new \FluxMedia\App\Services\Logger() );
	}

	/**
	 * Register REST API routes.
	 *
	 * @since 0.1.0
	 */
	public function register_routes() {
		register_rest_route( 'flux-media/v1', '/status', [
			[
				'methods' => 'GET',
				'callback' => [ $this, 'get_status' ],
				'permission_callback' => [ $this, 'check_permissions' ],
			],
		] );

		register_rest_route( 'flux-media/v1', '/quota', [
			[
				'methods' => 'GET',
				'callback' => [ $this, 'get_quota' ],
				'permission_callback' => [ $this, 'check_permissions' ],
			],
		] );
	}

	/**
	 * Get system status.
	 *
	 * @since 0.1.0
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response Response object.
	 */
	public function get_status( WP_REST_Request $request ) {
		try {
			$status = [
				'imageProcessor' => $this->get_image_processor_status(),
				'videoProcessor' => $this->get_video_processor_status(),
				'phpVersion' => PHP_VERSION,
				'memoryLimit' => ini_get( 'memory_limit' ),
				'maxExecutionTime' => ini_get( 'max_execution_time' ),
				'uploadMaxFilesize' => ini_get( 'upload_max_filesize' ),
				'postMaxSize' => ini_get( 'post_max_size' ),
			];

			return $this->create_success_response( $status, 'System status retrieved successfully' );
		} catch ( \Exception $e ) {
			return $this->create_error_response( 'Failed to retrieve system status: ' . $e->getMessage() );
		}
	}

	/**
	 * Get quota information.
	 *
	 * @since 0.1.0
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response Response object.
	 */
	public function get_quota( WP_REST_Request $request ) {
		try {
			$quota = [
				'current_usage' => $this->quota_manager->get_current_usage(),
				'limits' => $this->quota_manager->get_limits(),
				'progress' => $this->quota_manager->get_quota_progress(),
				'days_until_reset' => $this->quota_manager->get_days_until_reset(),
				'is_free_plan' => $this->quota_manager->is_free_plan(),
			];

			return $this->create_success_response( $quota, 'Quota information retrieved successfully' );
		} catch ( \Exception $e ) {
			return $this->create_error_response( 'Failed to retrieve quota information: ' . $e->getMessage() );
		}
	}

	/**
	 * Get image processor status.
	 *
	 * @since 0.1.0
	 * @return array Image processor status.
	 */
	private function get_image_processor_status() {
		$available_processors = $this->processor_detector->get_available_image_processors();
		
		// Determine the best processor (prefer Imagick over GD)
		$best_processor = null;
		if ( isset( $available_processors[ ProcessorTypes::IMAGE_IMAGICK ] ) ) {
			$best_processor = ProcessorTypes::IMAGE_IMAGICK;
		} elseif ( isset( $available_processors[ ProcessorTypes::IMAGE_GD ] ) ) {
			$best_processor = ProcessorTypes::IMAGE_GD;
		}
		
		return [
			'available' => ! empty( $best_processor ),
			'type' => $best_processor ?: 'none',
			'version' => $this->get_processor_version( $best_processor ),
			'webp_support' => $this->format_detector->supports_webp(),
			'avif_support' => $this->format_detector->supports_avif(),
		];
	}

	/**
	 * Get video processor status.
	 *
	 * @since 0.1.0
	 * @return array Video processor status.
	 */
	private function get_video_processor_status() {
		$available_processors = $this->processor_detector->get_available_video_processors();
		
		// Determine the best processor (FFmpeg is the only video processor we support)
		$best_processor = null;
		if ( isset( $available_processors[ ProcessorTypes::VIDEO_FFMPEG ] ) ) {
			$best_processor = ProcessorTypes::VIDEO_FFMPEG;
		}
		
		return [
			'available' => ! empty( $best_processor ),
			'type' => $best_processor ?: 'none',
			'av1_support' => $this->format_detector->supports_av1(),
			'webm_support' => $this->format_detector->supports_webm(),
		];
	}

	/**
	 * Get processor version information.
	 *
	 * @since 0.1.0
	 * @param string $processor_type Processor type.
	 * @return string Processor version.
	 */
	private function get_processor_version( $processor_type ) {
		switch ( $processor_type ) {
			case 'gd':
				$info = gd_info();
				return $info['GD Version'] ?? 'Unknown';
			case 'imagick':
				if ( class_exists( 'Imagick' ) ) {
					$imagick = new \Imagick();
					return $imagick->getVersion()['versionString'] ?? 'Unknown';
				}
				return 'Not available';
			default:
				return 'Not available';
		}
	}

	/**
	 * Check if user has permission to access status.
	 *
	 * @since 0.1.0
	 * @param WP_REST_Request $request Request object.
	 * @return bool True if user has permission.
	 */
	public function check_permissions( WP_REST_Request $request ) {
		return current_user_can( 'manage_options' );
	}
}
