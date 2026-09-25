<?php
/**
 * Unit tests for BulkStatsService state derivation.
 *
 * @package FluxMedia\Tests\Unit
 * @since 4.4.0
 */

namespace FluxMedia\Tests\Unit;

use FluxMedia\App\Services\BulkConverter;
use FluxMedia\App\Services\BulkStatsService;
use FluxMedia\App\Services\Settings;
use PHPUnit\Framework\TestCase;

/**
 * BulkStatsService unit tests.
 *
 * @since 4.4.0
 */
class BulkStatsServiceTest extends TestCase {

	/**
	 * Reset stub stores.
	 *
	 * @since 4.4.0
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['fmo_test_options']              = [];
		$GLOBALS['fmo_test_as_scheduled_actions'] = [];
		$GLOBALS['fmo_test_as_next_action']       = [];
		$GLOBALS['fmo_test_as_recurring_actions'] = [];
	}

	/**
	 * Disabled bulk reports off regardless of backlog.
	 *
	 * @since 4.4.0
	 * @return void
	 */
	public function testStateOffWhenDisabled() {
		$service = $this->makeService( 12 );
		$state   = $service->derive_state( false, 12, [] );
		$this->assertSame( BulkStatsService::STATE_OFF, $state );
	}

	/**
	 * Enabled with backlog and empty queue is idle.
	 *
	 * @since 4.4.0
	 * @return void
	 */
	public function testStateIdleWhenEnabledWithBacklog() {
		$service = $this->makeService( 5 );
		$this->assertSame( BulkStatsService::STATE_IDLE, $service->derive_state( true, 5, [] ) );
	}

	/**
	 * Enabled with empty backlog and empty queue is complete.
	 *
	 * @since 4.4.0
	 * @return void
	 */
	public function testStateCompleteWhenNothingLeft() {
		$service = $this->makeService( 0 );
		$this->assertSame( BulkStatsService::STATE_COMPLETE, $service->derive_state( true, 0, [] ) );
	}

	/**
	 * Future-scheduled pending actions map to queued.
	 *
	 * @since 4.4.0
	 * @return void
	 */
	public function testStateQueuedWhenPendingInFuture() {
		$service = $this->makeService( 3 );
		$pending = [
			[ 'timestamp' => time() + 120 ],
		];
		$this->assertSame( BulkStatsService::STATE_QUEUED, $service->derive_state( true, 3, $pending ) );
	}

	/**
	 * Due pending actions map to working.
	 *
	 * @since 4.4.0
	 * @return void
	 */
	public function testStateWorkingWhenPendingDue() {
		$service = $this->makeService( 3 );
		$pending = [
			[ 'timestamp' => time() - 30 ],
		];
		$this->assertSame( BulkStatsService::STATE_WORKING, $service->derive_state( true, 3, $pending ) );
	}

	/**
	 * Pending actions older than the stale threshold map to stuck.
	 *
	 * @since 4.4.0
	 * @return void
	 */
	public function testStateStuckWhenPendingPastThreshold() {
		$service = $this->makeService( 1 );
		$pending = [
			[ 'timestamp' => time() - (int) FLUX_MEDIA_OPTIMIZER_STALE_JOB_THRESHOLD - 10 ],
		];
		$this->assertSame( BulkStatsService::STATE_STUCK, $service->derive_state( true, 1, $pending ) );
	}

	/**
	 * get_stats returns the contract keys when bulk is enabled.
	 *
	 * @since 4.4.0
	 * @return void
	 */
	public function testGetStatsPayloadWhenEnabled() {
		$GLOBALS['fmo_test_options'][ Settings::get_options_option_name() ] = [
			'bulk_conversion_enabled' => true,
		];

		as_schedule_single_action(
			time() + 60,
			'flux_media_optimizer_convert_attachment',
			[ 'attachment_id' => 9 ],
			'flux-media-optimizer'
		);
		as_schedule_recurring_action(
			time() + 300,
			20 * MINUTE_IN_SECONDS,
			'flux_media_optimizer_bulk_discovery',
			[],
			'flux-media-optimizer'
		);

		$service = $this->makeService( 7 );
		$stats   = $service->get_stats();

		$this->assertTrue( $stats['enabled'] );
		$this->assertSame( 7, $stats['eligible_remaining'] );
		$this->assertSame( 1, $stats['pending_actions'] );
		$this->assertNotNull( $stats['next_discovery_at'] );
		$this->assertSame( BulkStatsService::STATE_QUEUED, $stats['state'] );
	}

	/**
	 * Build service with a mocked eligible count.
	 *
	 * @since 4.4.0
	 * @param int $eligible Eligible remaining.
	 * @return BulkStatsService
	 */
	private function makeService( int $eligible ): BulkStatsService {
		$converter = $this->createMock( BulkConverter::class );
		$converter->method( 'count_eligible_media' )->willReturn( $eligible );
		return new BulkStatsService( $converter );
	}
}
