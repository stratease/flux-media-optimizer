<?php
/**
 * Unit tests for ReviewPromptService.
 *
 * @package FluxMedia\Tests\Unit
 * @since 4.3.1
 */

namespace FluxMedia\Tests\Unit;

use FluxMedia\App\Services\PluginSupportUrls;
use FluxMedia\App\Services\ReviewPromptService;
use PHPUnit\Framework\TestCase;

/**
 * ReviewPromptService unit tests.
 *
 * @since 4.3.1
 */
class ReviewPromptServiceTest extends TestCase {

	/**
	 * Reset option stubs.
	 *
	 * @since 4.3.1
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['fmo_test_options'] = [];
	}

	/**
	 * Milestones require distinct attachments and savings thresholds.
	 *
	 * @since 4.3.1
	 * @return void
	 */
	public function testMilestonesMetRequiresAttachmentsAndSavings() {
		$min_savings = ReviewPromptService::MIN_SAVINGS_BYTES;

		$this->assertFalse( ReviewPromptService::milestones_met( 2, $min_savings ) );
		$this->assertFalse( ReviewPromptService::milestones_met( 3, $min_savings - 1 ) );
		$this->assertTrue( ReviewPromptService::milestones_met( 3, $min_savings ) );
		$this->assertTrue( ReviewPromptService::milestones_met( 10, $min_savings + 1 ) );
	}

	/**
	 * Production should_show requires milestones, no failures, no welcome, not consumed.
	 *
	 * @since 4.3.1
	 * @return void
	 */
	public function testShouldShowGates() {
		$min_savings = ReviewPromptService::MIN_SAVINGS_BYTES;

		$this->assertTrue(
			ReviewPromptService::should_show( false, 3, $min_savings, 0 )
		);
		$this->assertFalse(
			ReviewPromptService::should_show( true, 3, $min_savings, 0 )
		);
		$this->assertFalse(
			ReviewPromptService::should_show( false, 3, $min_savings, 1 )
		);
		$this->assertFalse(
			ReviewPromptService::should_show( false, 2, $min_savings, 0 )
		);
	}

	/**
	 * Mark viewed sets consumed so should_show stays false.
	 *
	 * @since 4.3.1
	 * @return void
	 */
	public function testMarkViewedConsumesForever() {
		$min_savings = ReviewPromptService::MIN_SAVINGS_BYTES;

		$this->assertFalse( ReviewPromptService::is_consumed() );
		$this->assertTrue(
			ReviewPromptService::should_show( false, 3, $min_savings, 0 )
		);

		ReviewPromptService::mark_viewed();

		$this->assertTrue( ReviewPromptService::is_consumed() );
		$this->assertFalse(
			ReviewPromptService::should_show( false, 3, $min_savings, 0 )
		);
		$this->assertSame( 1, $GLOBALS['fmo_test_options'][ ReviewPromptService::OPTION_CONSUMED ] );
	}

	/**
	 * Review and support URLs use PluginSupportUrls SSOT.
	 *
	 * @since 4.3.1
	 * @return void
	 */
	public function testUrlsMatchPluginSupportUrls() {
		$this->assertSame( PluginSupportUrls::REVIEWS_URL, ReviewPromptService::REVIEW_URL );
		$this->assertSame( PluginSupportUrls::SUPPORT_FORUM_URL, ReviewPromptService::SUPPORT_URL );
	}
}
