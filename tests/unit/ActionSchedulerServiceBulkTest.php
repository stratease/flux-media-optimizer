<?php
/**
 * Unit tests for ActionSchedulerService bulk lifecycle helpers.
 *
 * @package FluxMedia\Tests\Unit
 * @since 4.4.0
 */

namespace FluxMedia\Tests\Unit;

use FluxMedia\App\Services\ActionSchedulerService;
use FluxMedia\App\Services\BulkConverter;
use FluxMedia\App\Services\MediaProcessingServiceLocator;
use FluxMedia\App\Services\Settings;
use FluxMedia\FluxPlugins\Common\Logger\Logger;
use PHPUnit\Framework\TestCase;

/**
 * ActionSchedulerService bulk schedule tests.
 *
 * @since 4.4.0
 */
class ActionSchedulerServiceBulkTest extends TestCase {

	/**
	 * Reset AS stubs.
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
	 * Enabling schedules discovery and does not convert inside the settings request.
	 *
	 * @since 4.4.0
	 * @since 4.4.1 Discovery is scheduled only; convert work waits for Action Scheduler.
	 * @return void
	 */
	public function testOnEnableSchedulesAndKicksDiscovery() {
		$GLOBALS['fmo_test_options'][ Settings::get_options_option_name() ] = [
			'bulk_conversion_enabled' => true,
		];

		$bulk = $this->createMock( BulkConverter::class );
		$bulk->expects( $this->never() )->method( 'handle_bulk_discovery' );

		$service = new ActionSchedulerService(
			$this->createMock( Logger::class ),
			$this->createMock( MediaProcessingServiceLocator::class ),
			$bulk
		);

		$service->on_bulk_conversion_setting_changed( true, false );

		$this->assertNotEmpty( $GLOBALS['fmo_test_as_recurring_actions'] );
		$this->assertSame( 'flux_media_optimizer_bulk_discovery', $GLOBALS['fmo_test_as_recurring_actions'][0]['hook'] );
		$now = time();
		$this->assertSame( $now + 10, BulkConverter::discovery_schedule_time( 0, $now ) );
	}

	/**
	 * Disabling unschedules discovery only.
	 *
	 * @since 4.4.0
	 * @return void
	 */
	public function testOnDisableUnschedulesDiscovery() {
		$GLOBALS['fmo_test_options'][ Settings::get_options_option_name() ] = [
			'bulk_conversion_enabled' => false,
		];

		as_schedule_recurring_action(
			time(),
			20 * MINUTE_IN_SECONDS,
			'flux_media_optimizer_bulk_discovery',
			[],
			'flux-media-optimizer'
		);
		as_schedule_single_action(
			time() + 30,
			'flux_media_optimizer_convert_attachment',
			[ 'attachment_id' => 11 ],
			'flux-media-optimizer'
		);

		$service = new ActionSchedulerService(
			$this->createMock( Logger::class ),
			$this->createMock( MediaProcessingServiceLocator::class ),
			$this->createMock( BulkConverter::class )
		);

		$service->on_bulk_conversion_setting_changed( false, true );

		$this->assertEmpty( $GLOBALS['fmo_test_as_recurring_actions'] );
		$convert = array_values(
			array_filter(
				$GLOBALS['fmo_test_as_scheduled_actions'],
				static function ( $action ) {
					return ( $action['hook'] ?? '' ) === 'flux_media_optimizer_convert_attachment';
				}
			)
		);
		$this->assertCount( 1, $convert );
	}
}
