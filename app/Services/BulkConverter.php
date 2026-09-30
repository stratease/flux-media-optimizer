<?php
/**
 * Bulk conversion service for processing existing media.
 *
 * @package FluxMedia
 * @since 0.1.0
 */

namespace FluxMedia\App\Services;

use FluxMedia\FluxPlugins\Common\Logger\Logger;
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
	 * Shared conversion orchestrator.
	 *
	 * @since 4.3.0
	 * @var ConversionOrchestrator|null
	 */
	private $conversion_orchestrator;

	/**
	 * Eligible-count cache lifetime in seconds.
	 *
	 * Longer than the 30s admin poll so a sitting Settings page does not COUNT every tick.
	 *
	 * @since 4.4.1
	 * @var int
	 */
	public const ELIGIBLE_COUNT_TTL = 120;

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 * @since 4.0.0 Updated to use service locator instead of individual converters.
	 * @param Logger      $logger Logger instance.
	 * @param MediaProcessingServiceLocator $service_locator Service locator instance.
	 * @param ConversionTracker             $conversion_tracker Conversion tracker service.
	 */
	public function __construct( Logger $logger, MediaProcessingServiceLocator $service_locator, ConversionTracker $conversion_tracker ) {
		$this->logger                   = $logger;
		$this->service_locator          = $service_locator;
		$this->conversion_tracker       = $conversion_tracker;
		$this->conversion_orchestrator  = null;
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
		$batch_size              = 50;
		$unconverted_attachments = $this->get_unconverted_media( $batch_size );

		if ( empty( $unconverted_attachments ) ) {
			$this->logger->debug( 'Bulk conversion discovery: No unconverted attachments found' );
			return;
		}

		// First convert is at least 10s out so discovery does not run inside the request that enabled bulk.
		$scheduled_count = 0;
		$now             = time();

		foreach ( $unconverted_attachments as $index => $attachment_id ) {
			$schedule_time = self::discovery_schedule_time( (int) $index, $now );
			$action_id     = call_user_func( $schedule_callback, $attachment_id, $schedule_time );

			if ( $action_id ) {
				$scheduled_count++;
			}
		}

		$this->logger->debug( "Bulk conversion discovery: Scheduled {$scheduled_count} attachment conversion actions with incremental delays" );
	}

	/**
	 * Unix time for a discovery batch slot. Index 0 is at least 10 seconds ahead.
	 *
	 * @since 4.4.1
	 * @param int $index Zero-based position in the discovery batch.
	 * @param int $now   Current Unix time.
	 * @return int
	 */
	public static function discovery_schedule_time( int $index, int $now ): int {
		$delay = 10;
		return $now + $delay + ( $index * $delay );
	}

	/**
	 * Transient key for the eligible count. Local and cloud SQL are cached separately.
	 *
	 * @since 4.4.1
	 * @param bool $cdn_all Whether cloud bulk includes every attachment.
	 * @return string
	 */
	public static function eligible_count_transient_key( bool $cdn_all ): string {
		return $cdn_all
			? 'flux_media_optimizer_eligible_count_cdn'
			: 'flux_media_optimizer_eligible_count_local';
	}

	/**
	 * Drop both eligible-count caches after terminal meta writes.
	 *
	 * @since 4.4.1
	 * @return void
	 */
	public static function invalidate_eligible_count_cache(): void {
		foreach ( [ false, true ] as $cdn_all ) {
			$key = self::eligible_count_transient_key( $cdn_all );
			if ( function_exists( 'delete_transient' ) ) {
				delete_transient( $key );
			}
			if ( function_exists( 'wp_cache_delete' ) ) {
				wp_cache_delete( $key, 'flux_media_optimizer' );
			}
		}
	}

	/**
	 * Count eligible unconverted attachments for bulk processing.
	 *
	 * Local sites count optimize MIME types only. Cloud sites count every attachment.
	 *
	 * @since 4.4.0
	 * @since 4.4.1 Processor-aware SQL, NOT EXISTS filters, and a 2-minute transient.
	 * @return int
	 */
	public function count_eligible_media() {
		$cdn_all = Settings::is_external_processing_active();
		$key     = self::eligible_count_transient_key( $cdn_all );

		if ( function_exists( 'get_transient' ) ) {
			$cached = get_transient( $key );
			if ( false !== $cached && is_numeric( $cached ) ) {
				return (int) $cached;
			}
		}

		global $wpdb;

		$sql  = 'SELECT COUNT(p.ID) FROM ' . $this->posts_table() . ' p WHERE ' . $this->get_eligible_attachments_sql( $cdn_all );
		$args = $this->get_eligible_query_args( $cdn_all );
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- count is cached in a transient.
		$count = (int) $wpdb->get_var( $wpdb->prepare( $sql, ...$args ) );

		if ( function_exists( 'set_transient' ) ) {
			set_transient( $key, $count, self::ELIGIBLE_COUNT_TTL );
		}
		if ( function_exists( 'wp_cache_set' ) ) {
			wp_cache_set( $key, $count, 'flux_media_optimizer', self::ELIGIBLE_COUNT_TTL );
		}

		return $count;
	}

	/**
	 * Get unconverted media files.
	 *
	 * Retrieves image/video attachments that haven't been converted and aren't disabled.
	 * Failed attachments are excluded — they use ConversionRetryService instead.
	 *
	 * @since 0.1.0
	 * @since 3.0.0 Made public for use by Action Scheduler service.
	 * @since 4.0.0 Updated to handle all media types, not just images and videos.
	 * @since 4.3.0 Excludes failed job state so permanent failures cannot bypass retry limits.
	 * @since 4.4.0 Restricts to image/ and video/ MIME types so non-media attachments are not queued.
	 * @since 4.4.1 Local uses optimize MIME IN list; cloud includes every attachment type.
	 * @param int $limit Maximum number of files to return.
	 * @return array Array of attachment IDs.
	 */
	public function get_unconverted_media( $limit = 10 ) {
		global $wpdb;

		$cdn_all = Settings::is_external_processing_active();
		$sql     = 'SELECT p.ID FROM ' . $this->posts_table() . ' p WHERE ' . $this->get_eligible_attachments_sql( $cdn_all )
			. ' ORDER BY p.post_date DESC LIMIT %d';
		$args    = $this->get_eligible_query_args( $cdn_all );
		$args[]  = (int) $limit;

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- discovery batch query.
		$attachments = $wpdb->get_col( $wpdb->prepare( $sql, ...$args ) );

		return is_array( $attachments ) ? $attachments : [];
	}

	/**
	 * WHERE clause for eligible attachments. Placeholders are filled by get_eligible_query_args().
	 *
	 * @since 4.4.1
	 * @param bool $cdn_all True when cloud bulk should include every attachment MIME.
	 * @return string
	 */
	public function get_eligible_attachments_sql( bool $cdn_all ): string {
		$meta = $this->postmeta_table();

		$sql = "p.post_type = 'attachment'
			AND p.post_status IN ('inherit', 'private')
			AND NOT EXISTS (
				SELECT 1 FROM {$meta} pm_conv
				WHERE pm_conv.post_id = p.ID
				AND pm_conv.meta_key = %s
				AND pm_conv.meta_value IS NOT NULL
				AND pm_conv.meta_value != ''
			)
			AND NOT EXISTS (
				SELECT 1 FROM {$meta} pm_dis
				WHERE pm_dis.post_id = p.ID
				AND pm_dis.meta_key = %s
				AND pm_dis.meta_value IS NOT NULL
				AND pm_dis.meta_value != ''
			)
			AND NOT EXISTS (
				SELECT 1 FROM {$meta} pm_fail
				WHERE pm_fail.post_id = p.ID
				AND pm_fail.meta_key = %s
				AND pm_fail.meta_value = %s
			)
			AND NOT EXISTS (
				SELECT 1 FROM {$meta} pm_inel
				WHERE pm_inel.post_id = p.ID
				AND pm_inel.meta_key = %s
				AND pm_inel.meta_value = %s
			)";

		if ( ! $cdn_all ) {
			$mimes        = ( new EligibleMimeRegistry() )->get_optimizable_mime_types();
			$placeholders = implode( ', ', array_fill( 0, count( $mimes ), '%s' ) );
			$sql         .= " AND p.post_mime_type IN ({$placeholders})";
		}

		return $sql;
	}

	/**
	 * Bound values for get_eligible_attachments_sql().
	 *
	 * @since 4.4.1
	 * @param bool $cdn_all True when no MIME predicate is included.
	 * @return array<int, string>
	 */
	public function get_eligible_query_args( bool $cdn_all ): array {
		$args = [
			AttachmentMetaHandler::META_KEY_CONVERTED_FORMATS,
			AttachmentMetaHandler::META_KEY_CONVERSION_DISABLED,
			AttachmentMetaHandler::META_KEY_EXTERNAL_JOB_STATE,
			'failed',
			AttachmentMetaHandler::META_KEY_INELIGIBLE,
			'1',
		];

		if ( ! $cdn_all ) {
			$args = array_merge( $args, ( new EligibleMimeRegistry() )->get_optimizable_mime_types() );
		}

		return $args;
	}

	/**
	 * Posts table name, with a stable fallback for SQL-shape tests.
	 *
	 * @since 4.4.1
	 * @return string
	 */
	private function posts_table() {
		global $wpdb;

		return ( isset( $wpdb->posts ) && is_string( $wpdb->posts ) && '' !== $wpdb->posts ) ? $wpdb->posts : 'wp_posts';
	}

	/**
	 * Postmeta table name, with a stable fallback for SQL-shape tests.
	 *
	 * @since 4.4.1
	 * @return string
	 */
	private function postmeta_table() {
		global $wpdb;

		return ( isset( $wpdb->postmeta ) && is_string( $wpdb->postmeta ) && '' !== $wpdb->postmeta ) ? $wpdb->postmeta : 'wp_postmeta';
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

	/**
	 * Set the shared conversion orchestrator.
	 *
	 * @since 4.3.0
	 * @param ConversionOrchestrator $conversion_orchestrator Orchestrator.
	 * @return void
	 */
	public function set_conversion_orchestrator( ConversionOrchestrator $conversion_orchestrator ) {
		$this->conversion_orchestrator = $conversion_orchestrator;
	}

	public function process_attachment( $attachment_id ) {
		if ( AttachmentMetaHandler::is_conversion_disabled( $attachment_id ) ) {
			$this->logger->info( "Attachment conversion skipped: Conversion disabled for attachment {$attachment_id}" );
			return false;
		}

		if ( $this->conversion_orchestrator ) {
			$result = $this->conversion_orchestrator->dispatch(
				new ConversionRequest( (int) $attachment_id, ConversionRequest::TRIGGER_BULK )
			);
			return $result->is_completed() || $result->is_in_flight();
		}

		$processor = $this->service_locator->get_processor();
		if ( ! $processor ) {
			$this->logger->error( "Processor not available for attachment conversion (attachment: {$attachment_id})" );
			return false;
		}

		return $processor->process( $attachment_id );
	}


}
