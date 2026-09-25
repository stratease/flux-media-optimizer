<?php
/**
 * Bulk conversion queue statistics (SSOT for admin visibility).
 *
 * @package FluxMedia\App\Services
 * @since 4.4.0
 */

namespace FluxMedia\App\Services;

/**
 * Derives bulk conversion status from Settings, eligible media, and Action Scheduler.
 *
 * @since 4.4.0
 */
class BulkStatsService {

	/**
	 * State when bulk conversion is disabled.
	 *
	 * @since 4.4.0
	 * @var string
	 */
	public const STATE_OFF = 'off';

	/**
	 * Enabled with backlog but no pending convert actions (awaiting discovery).
	 *
	 * @since 4.4.0
	 * @var string
	 */
	public const STATE_IDLE = 'idle';

	/**
	 * Pending convert actions scheduled in the future.
	 *
	 * @since 4.4.0
	 * @var string
	 */
	public const STATE_QUEUED = 'queued';

	/**
	 * Pending convert actions due or recently due.
	 *
	 * @since 4.4.0
	 * @var string
	 */
	public const STATE_WORKING = 'working';

	/**
	 * Enabled with no eligible media and no pending actions.
	 *
	 * @since 4.4.0
	 * @var string
	 */
	public const STATE_COMPLETE = 'complete';

	/**
	 * Pending convert actions older than the stale threshold.
	 *
	 * @since 4.4.0
	 * @var string
	 */
	public const STATE_STUCK = 'stuck';

	/**
	 * Bulk converter for eligible media counts.
	 *
	 * @since 4.4.0
	 * @var BulkConverter
	 */
	private $bulk_converter;

	/**
	 * Constructor.
	 *
	 * @since 4.4.0
	 * @param BulkConverter $bulk_converter Bulk converter.
	 */
	public function __construct( BulkConverter $bulk_converter ) {
		$this->bulk_converter = $bulk_converter;
	}

	/**
	 * Build the bulk stats payload for REST and admin UI.
	 *
	 * @since 4.4.0
	 * @return array{
	 *     enabled: bool,
	 *     eligible_remaining: int,
	 *     pending_actions: int,
	 *     next_discovery_at: int|null,
	 *     state: string
	 * }
	 */
	public function get_stats(): array {
		$enabled = Settings::is_bulk_conversion_enabled();

		$pending_actions = $this->get_pending_convert_actions();
		$pending_count   = count( $pending_actions );
		$eligible        = $enabled || $pending_count > 0
			? $this->bulk_converter->count_eligible_media()
			: 0;

		$next_discovery = null;
		if ( $enabled && function_exists( 'as_next_scheduled_action' ) ) {
			$next = as_next_scheduled_action(
				'flux_media_optimizer_bulk_discovery',
				[],
				ActionSchedulerGroups::MEDIA_OPTIMIZER
			);
			$next_discovery = false !== $next ? (int) $next : null;
		}

		return [
			'enabled'             => $enabled,
			'eligible_remaining'  => (int) $eligible,
			'pending_actions'     => $pending_count,
			'next_discovery_at'   => $next_discovery,
			'state'               => $this->derive_state( $enabled, (int) $eligible, $pending_actions ),
		];
	}

	/**
	 * Map counts and pending schedule ages into a single state string.
	 *
	 * @since 4.4.0
	 * @param bool  $enabled          Whether bulk is enabled.
	 * @param int   $eligible         Eligible remaining count.
	 * @param array $pending_actions  Pending convert action rows (ARRAY_A).
	 * @return string
	 */
	public function derive_state( bool $enabled, int $eligible, array $pending_actions ): string {
		if ( ! $enabled ) {
			return self::STATE_OFF;
		}

		$pending_count = count( $pending_actions );
		if ( 0 === $pending_count ) {
			return $eligible > 0 ? self::STATE_IDLE : self::STATE_COMPLETE;
		}

		$now       = time();
		$threshold = defined( 'FLUX_MEDIA_OPTIMIZER_STALE_JOB_THRESHOLD' )
			? (int) FLUX_MEDIA_OPTIMIZER_STALE_JOB_THRESHOLD
			: 6 * HOUR_IN_SECONDS;

		$oldest_ts = null;
		$any_due   = false;
		foreach ( $pending_actions as $action ) {
			$ts = isset( $action['timestamp'] ) ? (int) $action['timestamp'] : $now;
			if ( null === $oldest_ts || $ts < $oldest_ts ) {
				$oldest_ts = $ts;
			}
			if ( $ts <= $now ) {
				$any_due = true;
			}
		}

		if ( null !== $oldest_ts && ( $now - $oldest_ts ) >= $threshold ) {
			return self::STATE_STUCK;
		}

		return $any_due ? self::STATE_WORKING : self::STATE_QUEUED;
	}

	/**
	 * Pending convert_attachment Action Scheduler rows.
	 *
	 * @since 4.4.0
	 * @return array<int, array<string, mixed>>
	 */
	private function get_pending_convert_actions(): array {
		if ( ! function_exists( 'as_get_scheduled_actions' ) || ! class_exists( 'ActionScheduler_Store' ) ) {
			return [];
		}

		$actions = as_get_scheduled_actions(
			[
				'hook'   => 'flux_media_optimizer_convert_attachment',
				'status' => \ActionScheduler_Store::STATUS_PENDING,
				'group'  => ActionSchedulerGroups::MEDIA_OPTIMIZER,
			],
			ARRAY_A
		);

		return is_array( $actions ) ? $actions : [];
	}
}
