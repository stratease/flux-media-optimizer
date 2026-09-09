<?php
/**
 * Unit tests for ConversionTracker distinct attachment count.
 *
 * @package FluxMedia\Tests\Unit
 * @since 4.3.1
 */

namespace FluxMedia\Tests\Unit;

use FluxMedia\App\Services\ConversionTracker;
use FluxMedia\FluxPlugins\Common\Logger\Logger;
use PHPUnit\Framework\TestCase;

/**
 * ConversionTracker distinct count tests.
 *
 * @since 4.3.1
 */
class ConversionTrackerDistinctCountTest extends TestCase {

	/**
	 * @var mixed
	 */
	private $wpdb_backup;

	/**
	 * Restore global $wpdb after each test.
	 *
	 * @since 4.3.1
	 * @return void
	 */
	protected function tearDown(): void {
		$GLOBALS['wpdb'] = $this->wpdb_backup;
		parent::tearDown();
	}

	/**
	 * count_distinct_optimized_attachments casts SQL result to int.
	 *
	 * @since 4.3.1
	 * @return void
	 */
	public function testCountDistinctOptimizedAttachments() {
		$this->wpdb_backup = $GLOBALS['wpdb'] ?? null;

		$mock = new class() {
			/**
			 * @var string
			 */
			public $prefix = 'wp_';

			/**
			 * Stub COUNT DISTINCT query.
			 *
			 * @param string $query SQL.
			 * @return string
			 */
			public function get_var( $query ) {
				return '7';
			}
		};

		$GLOBALS['wpdb'] = $mock;

		$tracker = new ConversionTracker( Logger::get_instance() );

		$this->assertSame( 7, $tracker->count_distinct_optimized_attachments() );
	}
}
