<?php
/**
 * Local processing service for media processing operations.
 *
 * @package FluxMedia\App\Services
 * @since 3.0.0
 */

namespace FluxMedia\App\Services;

use FluxMedia\App\Services\ImageConverter;
use FluxMedia\App\Services\VideoConverter;
use FluxMedia\App\Services\ConversionTracker;
use FluxMedia\App\Services\BulkConverter;
use FluxMedia\App\Services\AttachmentMetaHandler;

/**
 * Local processing service implementation.
 *
 * Handles all local media processing operations using ImageConverter and VideoConverter.
 *
 * @since 3.0.0
 */
class LocalProcessingService implements ProcessingServiceInterface {

	/**
	 * Logger instance.
	 *
	 * @since 3.0.0
	 * @var Logger
	 */
	private $logger;

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
	 * Conversion tracker instance.
	 *
	 * @since 3.0.0
	 * @var ConversionTracker
	 */
	private $conversion_tracker;

	/**
	 * Bulk converter instance.
	 *
	 * @since 3.0.0
	 * @var BulkConverter
	 */
	private $bulk_converter;

	/**
	 * WordPress provider instance (for accessing private methods).
	 *
	 * @since 3.0.0
	 * @var WordPressProvider
	 */
	private $wordpress_provider;

	/**
	 * Constructor.
	 *
	 * @since 3.0.0
	 * @param ImageConverter    $image_converter Image converter service.
	 * @param VideoConverter    $video_converter Video converter service.
	 * @param ConversionTracker $conversion_tracker Conversion tracker service.
	 * @param BulkConverter      $bulk_converter Bulk converter service.
	 * @param Logger             $logger Logger instance.
	 * @param WordPressProvider  $wordpress_provider WordPress provider instance.
	 */
	public function __construct(
		ImageConverter $image_converter,
		VideoConverter $video_converter,
		ConversionTracker $conversion_tracker,
		BulkConverter $bulk_converter,
		Logger $logger,
		WordPressProvider $wordpress_provider
	) {
		$this->image_converter = $image_converter;
		$this->video_converter = $video_converter;
		$this->conversion_tracker = $conversion_tracker;
		$this->bulk_converter = $bulk_converter;
		$this->logger = $logger;
		$this->wordpress_provider = $wordpress_provider;
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
		// Use unified process() method
		$this->process( $attachment_id );

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

		// Use unified process() method with file path
		// Pass file path directly since we have it and it may not be in meta yet
		$this->process( $attachment_id, $file );

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
		if ( empty( $post_id ) || ! $filename || ! wp_check_filetype( $filename )['ext'] ) {
			return $override;
		}

		// Use unified process() method with file path
		// Pass file path directly since we have it and it may not be in meta yet
		$this->process( (int) $post_id, $filename );

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
		// Verify attachment still exists
		if ( ! get_post( $attachment_id ) ) {
			$this->logger->warning( "Video processing cron skipped: attachment {$attachment_id} no longer exists" );
			return;
		}

		// Verify file still exists
		if ( ! file_exists( $file_path ) ) {
			$this->logger->warning( "Video processing cron skipped: file not found for attachment {$attachment_id}: {$file_path}" );
			return;
		}

		// Verify it's still a supported video
		if ( ! $this->video_converter->is_supported_video( $file_path ) ) {
			$this->logger->warning( "Video processing cron skipped: unsupported video format for attachment {$attachment_id}" );
			return;
		}

		// Process the video conversion
		$this->wordpress_provider->process_video_conversion( $attachment_id, $file_path );
	}

	/**
	 * Process bulk conversion via cron.
	 *
	 * @since 3.0.0
	 * @return void
	 */
	public function process_bulk_conversion_cron() {
		// Check if bulk conversion is enabled
		if ( ! Settings::is_bulk_conversion_enabled() ) {
			return;
		}

		// Check if auto-conversion is enabled
		if ( ! Settings::is_image_auto_convert_enabled() && ! Settings::is_video_auto_convert_enabled() ) {
			return;
		}

		// Process bulk conversion with small batch size for cron
		$results = $this->bulk_converter->process_bulk_conversion( 5 );

		$this->logger->info( 'Bulk conversion cron completed. Processed: ' . $results['processed'] . ', Converted: ' . $results['converted'] . ', Errors: ' . $results['errors'] );
	}

	/**
	 * Process attachment conversion.
	 *
	 * Unified method for processing attachment conversion. Handles both images and videos.
	 * Can be used for manual conversions, Action Scheduler tasks, or internal processing.
	 *
	 * @since 3.0.0
	 * @param int         $attachment_id Attachment ID.
	 * @param string|null $file_path     Optional file path. If null, will be retrieved from attachment meta.
	 *                                   This parameter is useful when processing is triggered before the file path
	 *                                   is stored in the attachment meta (e.g., during initial upload).
	 * @return bool True if conversion was initiated successfully, false otherwise.
	 */
	public function process( $attachment_id, $file_path = null ) {
		// Get file path if not provided
		// Note: We retrieve from meta here because sometimes processing is triggered before
		// the file path is stored in the attachment meta (e.g., during initial upload).
		// When file_path is provided (e.g., from process_file_update), we use it directly.
		if ( empty( $file_path ) ) {
			$file_path = get_attached_file( $attachment_id );
		}
		
		// Validate file path
		if ( empty( $file_path ) || ! file_exists( $file_path ) ) {
			$this->logger->error( "Attachment conversion failed: File not found for attachment {$attachment_id}" );
			return false;
		}

		// Validate file type
		if ( ! wp_check_filetype( $file_path )['ext'] ) {
			$this->logger->warning( "Attachment conversion skipped: Invalid file type for attachment {$attachment_id}" );
			return false;
		}

		// Check if conversion is disabled for this attachment
		if ( AttachmentMetaHandler::is_conversion_disabled( $attachment_id ) ) {
			$this->logger->info( "Attachment conversion skipped: Conversion disabled for attachment {$attachment_id}" );
			return false;
		}

		// Process images
		if ( $this->image_converter->is_supported_image( $file_path ) ) {
			return $this->process_image( $attachment_id, $file_path );
		}

		// Process videos
		if ( $this->video_converter->is_supported_video( $file_path ) ) {
			return $this->process_video( $attachment_id, $file_path );
		}

		// Unsupported file type
		$this->logger->warning( "Attachment conversion skipped: Unsupported file type for attachment {$attachment_id}" );
		return false;
	}

	/**
	 * Process image conversion.
	 *
	 * @since 3.0.0
	 * @param int    $attachment_id Attachment ID.
	 * @param string $file_path     File path.
	 * @return bool True if conversion was initiated successfully, false otherwise.
	 */
	private function process_image( $attachment_id, $file_path ) {
		if ( ! Settings::is_image_auto_convert_enabled() ) {
			return false;
		}

		$this->wordpress_provider->process_image_conversion( $attachment_id, $file_path );
		return true;
	}

	/**
	 * Process video conversion.
	 *
	 * @since 3.0.0
	 * @param int    $attachment_id Attachment ID.
	 * @param string $file_path     File path.
	 * @return bool True if conversion was initiated successfully, false otherwise.
	 */
	private function process_video( $attachment_id, $file_path ) {
		if ( ! Settings::is_video_auto_convert_enabled() ) {
			return false;
		}

		return $this->wordpress_provider->process_video_conversion( $attachment_id, $file_path );
	}

	/**
	 * Delete attachment from local service.
	 *
	 * Handles deletion of local converted files and clears all conversion-related meta data.
	 *
	 * @since 3.0.0
	 * @param int $attachment_id Attachment ID.
	 * @return bool True if deletion was successful or not needed, false on error.
	 */
	public function delete_attachment( $attachment_id ) {
		// Get converted files by size
		$converted_files_by_size = AttachmentMetaHandler::get_converted_files_grouped_by_size( $attachment_id );
		
		if ( empty( $converted_files_by_size ) ) {
			// No converted files, nothing to delete
			$this->logger->debug( "No converted files found for attachment {$attachment_id}, skipping local deletion" );
			// Still clear meta in case there's stale data
			AttachmentMetaHandler::clear_all_attachment_meta( $attachment_id );
			return true;
		}

		// Initialize WordPress filesystem
		if ( ! function_exists( 'WP_Filesystem' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}
		WP_Filesystem();
		
		global $wp_filesystem;
		
		$deleted_count = 0;
		$total_count = 0;

		// Delete local files from size-specific structure
		$upload_dir = wp_upload_dir();
		$base_url = $upload_dir['baseurl'];
		$base_dir = $upload_dir['basedir'];
		
		foreach ( $converted_files_by_size as $size_name => $size_formats ) {
			if ( ! is_array( $size_formats ) ) {
				continue;
			}
			foreach ( $size_formats as $format => $data ) {
				// Extract URL/path from unified structure
				$url_or_path = null;
				if ( is_array( $data ) && isset( $data['url'] ) ) {
					$url_or_path = $data['url'];
				} elseif ( is_string( $data ) ) {
					$url_or_path = $data;
				}
				
				// Skip if invalid
				if ( ! is_string( $url_or_path ) || empty( $url_or_path ) ) {
					continue;
				}
				
				// Convert URL to file path if it's a local WordPress upload URL
				$file_path = $url_or_path;
				if ( AttachmentMetaHandler::is_file_url( $url_or_path ) ) {
					// Check if it's a local WordPress upload URL
					if ( strpos( $url_or_path, $base_url ) === 0 ) {
						// Convert URL to file path
						$relative_path = str_replace( $base_url, '', $url_or_path );
						$relative_path = ltrim( $relative_path, '/' );
						$file_path = $base_dir . '/' . $relative_path;
					} else {
						// It's a CDN/external URL, skip deletion (only remove from meta)
						continue;
					}
				}
				
				// Validate file path exists before attempting deletion
				if ( empty( $file_path ) || ! is_string( $file_path ) ) {
					continue;
				}
				
				$total_count++;
				if ( $wp_filesystem && $wp_filesystem->exists( $file_path ) && $wp_filesystem->delete( $file_path ) ) {
					$deleted_count++;
					$this->logger->info( "Deleted converted file: {$file_path} (size: {$size_name}, format: {$format})" );
				} else {
					$this->logger->warning( "Failed to delete converted file: {$file_path} (size: {$size_name}, format: {$format})" );
				}
			}
		}

		// Clear all meta data (includes conversion tracking)
		AttachmentMetaHandler::clear_all_attachment_meta( $attachment_id );

		$this->logger->info( "Deleted {$deleted_count}/{$total_count} converted files for attachment {$attachment_id}" );
		return true;
	}
}

