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

		$pending       = $this->get_pending_convert_snapshot();
		$pending_count = $pending['count'];
		$eligible      = $enabled || $pending_count > 0
			? $this->bulk_converter->count_eligible_media()
			: 0;

		$next_discovery = null;
		if ( $enabled && function_exists( 'as_next_scheduled_action' ) ) {
			$next           = as_next_scheduled_action(
				'flux_media_optimizer_bulk_discovery',
				[],
				ActionSchedulerGroups::MEDIA_OPTIMIZER
			);
			$next_discovery = false !== $next ? (int) $next : null;
		}

		return [
			'enabled'            => $enabled,
			'eligible_remaining' => (int) $eligible,
			'pending_actions'    => $pending_count,
			'next_discovery_at'  => $next_discovery,
			'state'              => $this->derive_state( $enabled, (int) $eligible, $pending_count, $pending['oldest'] ),
		];
	}

	/**
	 * Map counts and pending schedule ages into a single state string.
	 *
	 * @since 4.4.0
	 * @since 4.4.1 Pending count and oldest due time replace ARRAY_A action rows.
	 * @param bool     $enabled         Whether bulk is enabled.
	 * @param int      $eligible        Eligible remaining count.
	 * @param int      $pending_count   Real pending convert action count.
	 * @param int|null $oldest_due_at   Oldest pending schedule Unix time, or null.
	 * @return string
	 */
	public function derive_state( bool $enabled, int $eligible, int $pending_count, ?int $oldest_due_at = null ): string {
		if ( ! $enabled ) {
			return self::STATE_OFF;
		}

		if ( 0 === $pending_count ) {
			return $eligible > 0 ? self::STATE_IDLE : self::STATE_COMPLETE;
		}

		$now       = time();
		$oldest_ts = null === $oldest_due_at ? $now : $oldest_due_at;
		$threshold = defined( 'FLUX_MEDIA_OPTIMIZER_STALE_JOB_THRESHOLD' )
			? (int) FLUX_MEDIA_OPTIMIZER_STALE_JOB_THRESHOLD
			: 6 * HOUR_IN_SECONDS;

		if ( ( $now - $oldest_ts ) >= $threshold ) {
			return self::STATE_STUCK;
		}

		return $oldest_ts <= $now ? self::STATE_WORKING : self::STATE_QUEUED;
	}

	/**
	 * Real pending convert count and oldest schedule time.
	 *
	 * Uses Action Scheduler query_actions count when the store is loaded so the
	 * default per_page of 5 cannot cap the number. Unit tests fall back to the
	 * stub list, which also returns every matching row.
	 *
	 * @since 4.4.1
	 * @return array{count: int, oldest: int|null}
	 */
	private function get_pending_convert_snapshot(): array {
		if ( ! class_exists( 'ActionScheduler_Store' ) ) {
			return [
				'count'  => 0,
				'oldest' => null,
			];
		}

		$query = [
			'hook'     => 'flux_media_optimizer_convert_attachment',
			'status'   => \ActionScheduler_Store::STATUS_PENDING,
			'group'    => ActionSchedulerGroups::MEDIA_OPTIMIZER,
			'per_page' => 1,
			'orderby'  => 'date',
			'order'    => 'ASC',
		];

		if ( class_exists( 'ActionScheduler' ) && is_callable( [ 'ActionScheduler', 'store' ] ) ) {
			$store = \ActionScheduler::store();
			if ( is_object( $store ) && method_exists( $store, 'query_actions' ) ) {
				$count  = (int) $store->query_actions( $query, 'count' );
				$oldest = null;
				if ( $count > 0 && method_exists( $store, 'fetch_action' ) ) {
					$ids = $store->query_actions( $query );
					if ( is_array( $ids ) && isset( $ids[0] ) ) {
						$action = $store->fetch_action( $ids[0] );
						if ( is_object( $action ) && method_exists( $action, 'get_schedule' ) ) {
							$schedule = $action->get_schedule();
							if ( is_object( $schedule ) && method_exists( $schedule, 'get_date' ) ) {
								$date = $schedule->get_date();
								if ( $date instanceof \DateTimeInterface ) {
									$oldest = $date->getTimestamp();
								}
							}
						}
					}
				}

				return [
					'count'  => $count,
					'oldest' => $oldest,
				];
			}
		}

		if ( ! function_exists( 'as_get_scheduled_actions' ) || ! class_exists( 'ActionScheduler_Store' ) ) {
			return [
				'count'  => 0,
				'oldest' => null,
			];
		}

		$actions = as_get_scheduled_actions(
			[
				'hook'     => 'flux_media_optimizer_convert_attachment',
				'status'   => \ActionScheduler_Store::STATUS_PENDING,
				'group'    => ActionSchedulerGroups::MEDIA_OPTIMIZER,
				'per_page' => 0,
			],
			ARRAY_A
		);

		if ( ! is_array( $actions ) || [] === $actions ) {
			return [
				'count'  => 0,
				'oldest' => null,
			];
		}

		$oldest = null;
		foreach ( $actions as $action ) {
			if ( ! is_array( $action ) || ! isset( $action['timestamp'] ) ) {
				continue;
			}
			$ts = (int) $action['timestamp'];
			if ( null === $oldest || $ts < $oldest ) {
				$oldest = $ts;
			}
		}

		return [
			'count'  => count( $actions ),
			'oldest' => $oldest,
		];
	}
}
