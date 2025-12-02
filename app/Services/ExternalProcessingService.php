<?php
/**
 * External processing service for media processing operations.
 *
 * @package FluxMedia\App\Services
 * @since 3.0.0
 */

namespace FluxMedia\App\Services;

use FluxMedia\App\Http\Controllers\WebhookController;

/**
 * External processing service implementation.
 *
 * Handles all external media processing operations via ExternalOptimizationProvider.
 *
 * @since 3.0.0
 */
class ExternalProcessingService implements ProcessingServiceInterface {

	/**
	 * External optimization provider instance.
	 *
	 * @since 3.0.0
	 * @var ExternalOptimizationProvider
	 */
	private $external_provider;

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
	 * Image converter instance.
	 *
	 * @since 3.0.0
	 * @var ImageConverter
	 */
	private $image_converter;

	/**
	 * Video converter instance.
	 *
	 * @since 3.0.0
	 * @var VideoConverter
	 */
	private $video_converter;

	/**
	 * Constructor.
	 *
	 * @since 3.0.0
	 * @param ExternalOptimizationProvider $external_provider External optimization provider.
	 */
	public function __construct( ExternalOptimizationProvider $external_provider ) {
		$this->external_provider = $external_provider;
		$this->logger = new Logger();
		$this->api_client = new ExternalApiClient( $this->logger );
		$this->image_converter = new ImageConverter( $this->logger );
		$this->video_converter = new VideoConverter( $this->logger );
	}

	/**
	 * Process media upload.
	 *
	 * @since 3.0.0
	 * @param int $attachment_id Attachment ID.
	 * @return void
	 */
	public function process_media_upload( $attachment_id ) {
		$file_path = get_attached_file( $attachment_id );
		if ( ! $file_path || ! wp_check_filetype( $file_path )['ext'] ) {
			return;
		}

		$this->submit_processing_job( $attachment_id, $file_path );
	}

	/**
	 * Process attachment metadata update.
	 *
	 * @since 3.0.0
	 * @param array $data Attachment metadata.
	 * @param int   $attachment_id Attachment ID.
	 * @return array Modified metadata.
	 */
	public function process_metadata_update( $data, $attachment_id ) {
		$file_path = get_attached_file( $attachment_id );
		if ( ! $file_path ) {
			return $data;
		}

		$this->submit_processing_job( $attachment_id, $file_path );

		return $data;
	}

	/**
	 * Process attached file update.
	 *
	 * @since 3.0.0
	 * @param string $file New file path for the attachment.
	 * @param int    $attachment_id Attachment ID.
	 * @return string File path (unmodified).
	 */
	public function process_file_update( $file, $attachment_id ) {
		if ( ! $file || ! wp_check_filetype( $file )['ext'] ) {
			return $file;
		}

		$this->submit_processing_job( $attachment_id, $file );

		return $file;
	}

	/**
	 * Process image editor file save.
	 *
	 * @since 3.0.0
	 * @param mixed       $override   Override value from other filters (usually null).
	 * @param string      $filename   Saved filename for the edited image.
	 * @param object      $image      Image editor instance.
	 * @param string      $mime_type  MIME type of the saved image.
	 * @param int|false   $post_id    Attachment ID if available, otherwise false.
	 * @return mixed Original $override value.
	 */
	public function process_image_editor_save( $override, $filename, $image, $mime_type, $post_id ) {
		if ( empty( $post_id ) ) {
			return $override;
		}

		if ( ! $filename || ! wp_check_filetype( $filename )['ext'] ) {
			return $override;
		}

		$this->submit_processing_job( (int) $post_id, $filename );

		return $override;
	}

	/**
	 * Process video via cron.
	 *
	 * @since 3.0.0
	 * @param int    $attachment_id Attachment ID.
	 * @param string $file_path Source file path.
	 * @return void
	 */
	public function process_video_cron( $attachment_id, $file_path ) {
		if ( ! $file_path || ! file_exists( $file_path ) ) {
			return;
		}

		$this->submit_processing_job( $attachment_id, $file_path );
	}

	/**
	 * Process bulk conversion via cron.
	 *
	 * @since 3.0.0
	 * @return void
	 */
	public function process_bulk_conversion_cron() {
		// Bulk conversion for external service is handled via the external service's own mechanisms.
		// This method exists for interface compliance.
		return; // TODO
	}

	/**
	 * Submit a processing job to the external service.
	 *
	 * @since 3.0.0
	 * @param int    $attachment_id Attachment ID.
	 * @param string $file_path     File path.
	 * @return void
	 */
	private function submit_processing_job( $attachment_id, $file_path ) {
		// Determine file type.
		$is_image = $this->image_converter->is_supported_image( $file_path );
		$is_video = $this->video_converter->is_supported_video( $file_path );

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
		
		if ( empty( $formats ) ) {
			return;
		}

		// Get webhook URL.
		$webhook_url = WebhookController::get_webhook_url();
		
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
		$result = $this->api_client->submit_job( $attachment_id, $operations, $mimetype, $webhook_url );
		
		if ( ! $result['success'] ) {
			$this->logger->error( "Failed to submit job for attachment {$attachment_id}: " . ( $result['error'] ?? 'Unknown error' ) );
			return;
		}

		$this->logger->debug( "Job submitted successfully for attachment {$attachment_id}: {$result['job_id']}" );
	}
}

