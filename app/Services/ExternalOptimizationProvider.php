<?php
/**
 * External optimization provider for CDN and remote processing.
 *
 * @package FluxMedia
 * @since 3.0.0
 */

namespace FluxMedia\App\Services;

use FluxMedia\App\Http\Controllers\WebhookController;
use FluxMedia\App\Services\ConversionTracker;

/**
 * Handles external service integration for CDN and remote processing.
 *
 * Images and videos are processed and optimized; all other file types are stored on CDN for delivery.
 *
 * @since 3.0.0
 */
class ExternalOptimizationProvider {

	/**
	 * Logger instance.
	 *
	 * @since 3.0.0
	 * @var LoggerInterface
	 */
	private $logger;

	/**
	 * External API client instance.
	 *
	 * @since 3.0.0
	 * @var ExternalApiClient
	 */
	private $api_client;

	/**
	 * Constructor.
	 *
	 * @since 3.0.0
	 * @param LoggerInterface $logger Logger instance.
	 */
	public function __construct( LoggerInterface $logger ) {
		$this->logger = $logger;
		$this->api_client = new ExternalApiClient( $logger );
	}

	/**
	 * Initialize the provider and register hooks.
	 *
	 * @since 3.0.0
	 * @return void
	 */
	public function init() {
		$this->register_hooks();
	}

	/**
	 * Register WordPress hooks.
	 *
	 * @since 3.0.0
	 * @return void
	 */
	public function register_hooks() {
		// Handle media uploads.
		add_action( 'add_attachment', [ $this, 'handle_media_upload' ] );
		
		// Webhook endpoint is registered via WebhookController in Plugin class.
		
		// Schedule retry cron for failed jobs.
		if ( ! wp_next_scheduled( 'flux_media_optimizer_retry_failed_jobs' ) ) {
			wp_schedule_event( time(), 'hourly', 'flux_media_optimizer_retry_failed_jobs' );
		}
		add_action( 'flux_media_optimizer_retry_failed_jobs', [ $this, 'retry_failed_jobs' ] );
	}

	/**
	 * Handle media upload and submit to external service.
	 *
	 * @since 3.0.0
	 * @param int $attachment_id Attachment ID.
	 * @return void
	 */
	public function handle_media_upload( $attachment_id ) {
		// Check if conversion is disabled for this attachment.
		if ( AttachmentMetaHandler::is_conversion_disabled( $attachment_id ) ) {
			return;
		}

		$file_path = get_attached_file( $attachment_id );
		if ( ! $file_path || ! wp_check_filetype( $file_path )['ext'] ) {
			return;
		}

		// Determine file type.
		$image_converter = new ImageConverter( $this->logger );
		$video_converter = new VideoConverter( $this->logger );

		$is_image = $image_converter->is_supported_image( $file_path );
		$is_video = $video_converter->is_supported_video( $file_path );

		if ( ! $is_image && ! $is_video ) {
			return;
		}

		// Check if auto-conversion is enabled.
		if ( $is_image && ! Settings::is_image_auto_convert_enabled() ) {
			return;
		}
		if ( $is_video && ! Settings::is_video_auto_convert_enabled() ) {
			return;
		}

		// Get formats to process.
		$formats = $is_image ? Settings::get_image_formats() : Settings::get_video_formats();
		
		// Get mimetype.
		$mimetype = get_post_mime_type( $attachment_id );
		if ( ! $mimetype ) {
			$mimetype = wp_check_filetype( $file_path )['type'] ?? '';
		}
		
		// Build operations array.
		$operations = [];
		
		if ( $is_image ) {
			// Get all image sizes with metadata.
			$metadata = wp_get_attachment_metadata( $attachment_id );
			
			// Always include full size operation.
			$full_operation = [
				'formats'  => $formats,
				'key_name' => 'full',
			];
			
			// Add resize dimensions for full size if available.
			if ( isset( $metadata['width'] ) && isset( $metadata['height'] ) ) {
				$full_operation['resize'] = [
					'width'  => (int) $metadata['width'],
					'height' => (int) $metadata['height'],
				];
			}
			
			$operations[] = $full_operation;
			
			// Add operations for each WordPress image size.
			if ( ! empty( $metadata['sizes'] ) && is_array( $metadata['sizes'] ) ) {
				foreach ( $metadata['sizes'] as $size_name => $size_data ) {
					$operation = [
						'formats'  => $formats,
						'key_name' => $size_name,
					];
					
					// Add resize dimensions if available.
					if ( isset( $size_data['width'] ) && isset( $size_data['height'] ) ) {
						$operation['resize'] = [
							'width'  => (int) $size_data['width'],
							'height' => (int) $size_data['height'],
						];
					}
					
					$operations[] = $operation;
				}
			}
		} else {
			// Videos only have full size.
			$operations[] = [
				'formats'  => $formats,
				'key_name' => 'full',
			];
		}
		
		// Submit job to external service.
		$result = $this->api_client->submit_job( $attachment_id, $operations, $mimetype );
		
		// Extract formats and sizes for database storage (backward compatibility).
		$all_formats = [];
		$all_sizes = [];
		foreach ( $operations as $operation ) {
			if ( ! empty( $operation['formats'] ) ) {
				$all_formats = array_merge( $all_formats, $operation['formats'] );
			}
			if ( ! empty( $operation['key_name'] ) ) {
				$all_sizes[] = $operation['key_name'];
			}
		}
		$all_formats = array_unique( $all_formats );
		$all_sizes = array_unique( $all_sizes );

		if ( ! $result['success'] ) {
			$this->logger->error( "Failed to submit job for attachment {$attachment_id}: " . ( $result['error'] ?? 'Unknown error' ) );
			$this->store_job( $attachment_id, 'failed', $all_formats, $all_sizes, 0, $result['error'] ?? 'Unknown error' );
			$this->add_admin_notice( $attachment_id, $result['error'] ?? 'Failed to submit job to external service' );
			return;
		}

		// Store job in database.
		$this->store_job( $attachment_id, $result['status'], $all_formats, $all_sizes );
		$this->logger->debug( "Job submitted successfully for attachment {$attachment_id}" );
	}

	/**
	 * Store job in database.
	 *
	 * @since 3.0.0
	 * @param int         $attachment_id Attachment ID.
	 * @param string      $status        Job status.
	 * @param array       $formats       Formats array.
	 * @param array       $sizes         Sizes array.
	 * @param int         $retry_count   Retry count.
	 * @param string|null $last_error    Last error message.
	 * @return void
	 */
	private function store_job( $attachment_id, $status, $formats = [], $sizes = [], $retry_count = 0, $last_error = null ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'flux_media_optimizer_external_jobs';

		$wpdb->insert(
			$table_name,
			[
				'attachment_id' => $attachment_id,
				'job_id' => '',
				'status' => $status,
				'base_url' => null,
				'formats' => wp_json_encode( $formats ),
				'sizes' => wp_json_encode( $sizes ),
				'retry_count' => $retry_count,
				'last_error' => $last_error,
			],
			[ '%d', '%s', '%s', '%s', '%s', '%s', '%d', '%s' ]
		);
	}

	/**
	 * Get CDN URL for an attachment.
	 *
	 * @since 3.0.0
	 * @param int    $attachment_id Attachment ID.
	 * @param string $format        Format (webp, avif, av1, webm).
	 * @param string $size          Size name.
	 * @return string|null CDN URL or null if not available.
	 */
	public function get_cdn_url( $attachment_id, $format, $size = 'full' ) {
		return AttachmentMetaHandler::get_converted_file_url( $attachment_id, $format, $size );
	}

	/**
	 * Check if a job is currently processing.
	 *
	 * @since 3.0.0
	 * @param int $attachment_id Attachment ID.
	 * @return bool True if job is queued or processing.
	 */
	public function is_job_processing( $attachment_id ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'flux_media_optimizer_external_jobs';
		
		// Check if table exists before querying (prevents fatal errors if table hasn't been created yet).
		if ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table_name ) ) !== $table_name ) {
			return false;
		}
		
		$status = $wpdb->get_var( $wpdb->prepare(
			"SELECT status FROM {$table_name} WHERE attachment_id = %d ORDER BY id DESC LIMIT 1",
			$attachment_id
		) );

		return in_array( $status, [ 'queued', 'processing' ], true );
	}

	/**
	 * Get job status for an attachment.
	 *
	 * @since 3.0.0
	 * @param int $attachment_id Attachment ID.
	 * @return array|null Job data or null if not found.
	 */
	public function get_job_status( $attachment_id ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'flux_media_optimizer_external_jobs';
		
		// Check if table exists before querying (prevents fatal errors if table hasn't been created yet).
		if ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table_name ) ) !== $table_name ) {
			return null;
		}
		
		$job = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$table_name} WHERE attachment_id = %d ORDER BY id DESC LIMIT 1",
			$attachment_id
		), ARRAY_A );

		if ( $job ) {
			$job['formats'] = json_decode( $job['formats'], true ) ?: [];
			$job['sizes'] = json_decode( $job['sizes'], true ) ?: [];
		}

		return $job;
	}

	/**
	 * Retry a failed job.
	 *
	 * @since 3.0.0
	 * @param int $attachment_id Attachment ID.
	 * @return bool True on success, false on failure.
	 */
	public function retry_failed_job( $attachment_id ) {
		$job = $this->get_job_status( $attachment_id );
		if ( ! $job || $job['status'] !== 'failed' ) {
			return false;
		}

		if ( $job['retry_count'] >= 3 ) {
			$this->logger->warning( "Max retries reached for attachment {$attachment_id}" );
			return false;
		}

		// Rebuild operations array from stored formats and sizes.
		$file_path = get_attached_file( $attachment_id );
		if ( ! $file_path ) {
			return false;
		}

		// Determine file type.
		$image_converter = new ImageConverter( $this->logger );
		$video_converter = new VideoConverter( $this->logger );

		$is_image = $image_converter->is_supported_image( $file_path );
		$is_video = $video_converter->is_supported_video( $file_path );

		if ( ! $is_image && ! $is_video ) {
			return false;
		}

		// Get webhook URL and mimetype.
		$webhook_url = WebhookController::get_webhook_url();
		$mimetype = get_post_mime_type( $attachment_id );
		if ( ! $mimetype ) {
			$mimetype = wp_check_filetype( $file_path )['type'] ?? '';
		}

		// Rebuild operations array.
		$operations = [];
		$formats = $job['formats'] ?? [];

		if ( $is_image ) {
			$metadata = wp_get_attachment_metadata( $attachment_id );
			
			// Always include full size operation.
			$full_operation = [
				'formats'  => $formats,
				'key_name' => 'full',
			];
			
			// Add resize dimensions for full size if available.
			if ( isset( $metadata['width'] ) && isset( $metadata['height'] ) ) {
				$full_operation['resize'] = [
					'width'  => (int) $metadata['width'],
					'height' => (int) $metadata['height'],
				];
			}
			
			$operations[] = $full_operation;
			
			// Add operations for each stored size.
			$sizes = $job['sizes'] ?? [];
			if ( ! empty( $metadata['sizes'] ) && is_array( $metadata['sizes'] ) ) {
				foreach ( $sizes as $size_name ) {
					if ( 'full' === $size_name ) {
						continue; // Already added.
					}
					
					$operation = [
						'formats'  => $formats,
						'key_name' => $size_name,
					];
					
					// Add resize dimensions if available.
					if ( isset( $metadata['sizes'][ $size_name ]['width'] ) && isset( $metadata['sizes'][ $size_name ]['height'] ) ) {
						$operation['resize'] = [
							'width'  => (int) $metadata['sizes'][ $size_name ]['width'],
							'height' => (int) $metadata['sizes'][ $size_name ]['height'],
						];
					}
					
					$operations[] = $operation;
				}
			}
		} else {
			// Videos only have full size.
			$operations[] = [
				'formats'  => $formats,
				'key_name' => 'full',
			];
		}

		// Submit job again.
		$result = $this->api_client->submit_job( $attachment_id, $operations, $mimetype );

		if ( ! $result['success'] ) {
			// Update retry count.
			global $wpdb;
			$table_name = $wpdb->prefix . 'flux_media_optimizer_external_jobs';
			$wpdb->update(
				$table_name,
				[
					'retry_count' => $job['retry_count'] + 1,
					'last_error' => $result['error'] ?? 'Unknown error',
					'updated_at' => current_time( 'mysql' ),
				],
				[ 'id' => $job['id'] ],
				[ '%d', '%s', '%s' ],
				[ '%d' ]
			);
			return false;
		}

		// Update job status and reset retry count.
		global $wpdb;
		$table_name = $wpdb->prefix . 'flux_media_optimizer_external_jobs';
		$wpdb->update(
			$table_name,
			[
				'status' => $result['status'],
				'retry_count' => $job['retry_count'] + 1,
				'last_error' => null,
				'updated_at' => current_time( 'mysql' ),
			],
			[ 'id' => $job['id'] ],
			[ '%s', '%d', '%s', '%s' ],
			[ '%d' ]
		);

		return true;
	}

	/**
	 * Retry all failed jobs (called by cron).
	 *
	 * @since 3.0.0
	 * @return void
	 */
	public function retry_failed_jobs() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'flux_media_optimizer_external_jobs';
		
		// Get failed jobs with retry_count < 3.
		$failed_jobs = $wpdb->get_results(
			"SELECT DISTINCT attachment_id FROM {$table_name} WHERE status = 'failed' AND retry_count < 3",
			ARRAY_A
		);

		foreach ( $failed_jobs as $job ) {
			$this->retry_failed_job( $job['attachment_id'] );
		}
	}

	/**
	 * Add admin notice for job status.
	 *
	 * @since 3.0.0
	 * @param int    $attachment_id Attachment ID.
	 * @param string $message       Notice message.
	 * @return void
	 */
	private function add_admin_notice( $attachment_id, $message ) {
		// Store notice in transient for display on attachment screen.
		$notices = get_transient( 'flux_media_optimizer_notices' ) ?: [];
		$notices[ $attachment_id ] = [
			'message' => $message,
			'time' => time(),
		];
		set_transient( 'flux_media_optimizer_notices', $notices, 3600 );
	}
}

