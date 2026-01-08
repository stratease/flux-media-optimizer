<?php
/**
 * Bulk conversion service for processing existing media.
 *
 * @package FluxMedia
 * @since 0.1.0
 */

namespace FluxMedia\App\Services;

use FluxMedia\App\Services\Logger;
use FluxMedia\App\Services\Settings;
use FluxMedia\App\Services\AttachmentMetaHandler;

/**
 * Service for bulk conversion of existing media files.
 *
 * @since 0.1.0
 */
class BulkConverter {

	/**
	 * Logger instance.
	 *
	 * @since 0.1.0
	 * @var Logger
	 */
	private $logger;

	/**
	 * Service locator instance.
	 *
	 * @since 4.0.0
	 * @var MediaProcessingServiceLocator
	 */
	private $service_locator;

	/**
	 * Conversion tracker instance.
	 *
	 * @since 0.1.0
	 * @var ConversionTracker
	 */
	private $conversion_tracker;

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 * @since 4.0.0 Updated to use service locator instead of individual converters.
	 * @param Logger                        $logger Logger instance.
	 * @param MediaProcessingServiceLocator $service_locator Service locator instance.
	 * @param ConversionTracker             $conversion_tracker Conversion tracker service.
	 */
	public function __construct( Logger $logger, MediaProcessingServiceLocator $service_locator, ConversionTracker $conversion_tracker ) {
		$this->logger = $logger;
		$this->service_locator = $service_locator;
		$this->conversion_tracker = $conversion_tracker;
	}

	/**
	 * Process bulk conversion of existing media.
	 *
	 * @since 0.1.0
	 * @since 4.0.0 Updated to use processor service instead of individual converters.
	 * @param int $batch_size Number of files to process per batch.
	 * @return array Processing results.
	 */
	public function process_bulk_conversion( $batch_size = 10 ) {
		$results = [
			'processed' => 0,
			'converted' => 0,
			'errors' => 0,
		];

		// Get unconverted media files
		$unconverted_files = $this->get_unconverted_media( $batch_size );

		if ( empty( $unconverted_files ) ) {
			return $results;
		}

		// Get processor service
		$processor = $this->service_locator->get_processor();
		if ( ! $processor ) {
			$this->logger->error( 'Processor not available for bulk conversion' );
			return $results;
		}

		foreach ( $unconverted_files as $attachment_id ) {
			$results['processed']++;

			try {
				// Check if conversion is disabled for this attachment
				if ( AttachmentMetaHandler::is_conversion_disabled( $attachment_id ) ) {
					continue;
				}

				// Let the processor determine if this attachment should be processed
				$success = $processor->process( $attachment_id );

				if ( $success ) {
					$results['converted']++;
				} else {
					$results['errors']++;
					$this->logger->debug( "Bulk conversion skipped for attachment {$attachment_id} (processor determined no action needed)" );
				}

			} catch ( \Exception $e ) {
				$results['errors']++;
				$this->logger->error( "Bulk conversion exception for attachment {$attachment_id}: " . $e->getMessage() );
			}
		}

		return $results;
	}

	/**
	 * Handle bulk discovery action.
	 *
	 * Checks for pending conversion actions and schedules new ones if needed.
	 * Runs every 20 minutes to discover unconverted attachments and schedule
	 * them for processing with incremental delays to spread out server load.
	 *
	 * @since 4.0.0
	 * @param callable $schedule_callback Callback function to schedule attachment conversion.
	 *                                    Should accept (attachment_id, time) and return action ID or false.
	 * @return void
	 */
	public function handle_bulk_discovery( $schedule_callback ) {
		// Check if bulk conversion is enabled.
		if ( ! Settings::is_bulk_conversion_enabled() ) {
			$this->logger->debug( 'Bulk conversion discovery: Bulk conversion is disabled, skipping' );
			return;
		}

		$pending_actions = as_get_scheduled_actions(
			[
				'hook'   => 'flux_media_optimizer_convert_attachment',
				'status' => \ActionScheduler_Store::STATUS_PENDING,
			],
			'ids'
		);

		if ( ! empty( $pending_actions ) ) {
			// Actions already queued, skip discovery.
			$this->logger->debug( 'Bulk conversion discovery: Conversion actions already queued, skipping' );
			return;
		}

		// Get unconverted media (batch size of 50).
		$batch_size = 50;
		$unconverted_attachments = $this->get_unconverted_media( $batch_size );

		if ( empty( $unconverted_attachments ) ) {
			$this->logger->debug( 'Bulk conversion discovery: No unconverted attachments found' );
			return;
		}

		// Schedule individual conversion actions with incremental delays (10 seconds apart).
		$base_time = time();
		$delay_increment = 10; // 10 seconds between each action to spread out server load.
		$scheduled_count = 0;

		foreach ( $unconverted_attachments as $index => $attachment_id ) {
			$schedule_time = $base_time + ( $index * $delay_increment );
			$action_id = call_user_func( $schedule_callback, $attachment_id, $schedule_time );

			if ( $action_id ) {
				$scheduled_count++;
			}
		}

		$this->logger->debug( "Bulk conversion discovery: Scheduled {$scheduled_count} attachment conversion actions with incremental delays" );
	}

	/**
	 * Get unconverted media files.
	 *
	 * Retrieves all attachment types that haven't been converted and aren't disabled.
	 * The processor will determine if each attachment should be processed.
	 *
	 * @since 0.1.0
	 * @since 3.0.0 Made public for use by Action Scheduler service.
	 * @since 4.0.0 Updated to handle all media types, not just images and videos.
	 * @param int $limit Maximum number of files to return.
	 * @return array Array of attachment IDs.
	 */
	public function get_unconverted_media( $limit = 10 ) {
		global $wpdb;

		// Get all attachments that haven't been converted and aren't disabled.
		// The processor will determine if each attachment should be processed.
		$attachments = $wpdb->get_col( $wpdb->prepare(
			"SELECT p.ID 
			 FROM {$wpdb->posts} p 
			 LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_flux_media_optimizer_converted_formats'
			 LEFT JOIN {$wpdb->postmeta} pm_disabled ON p.ID = pm_disabled.post_id AND pm_disabled.meta_key = '_flux_media_optimizer_conversion_disabled'
			 WHERE p.post_type = 'attachment' 
			 AND (pm.meta_value IS NULL OR pm.meta_value = '')
			 AND (pm_disabled.meta_value IS NULL OR pm_disabled.meta_value = '')
			 ORDER BY p.post_date DESC
			 LIMIT %d",
			$limit
		) );

		return $attachments;
	}

	/**
	 * Process single attachment conversion.
	 *
	 * Processes a single attachment using the processor service.
	 * The processor determines if the attachment should be processed.
	 *
	 * @since 4.0.0
	 * @param int $attachment_id Attachment ID.
	 * @return bool True if conversion was initiated successfully, false otherwise.
	 */
	public function process_attachment( $attachment_id ) {
		// Check if conversion is disabled for this attachment
		if ( AttachmentMetaHandler::is_conversion_disabled( $attachment_id ) ) {
			$this->logger->info( "Attachment conversion skipped: Conversion disabled for attachment {$attachment_id}" );
			return false;
		}

		// Get processor service
		$processor = $this->service_locator->get_processor();
		if ( ! $processor ) {
			$this->logger->error( "Processor not available for attachment conversion (attachment: {$attachment_id})" );
			return false;
		}

		// Process the attachment - processor will determine if it should be processed
		return $processor->process( $attachment_id );
	}


}
