<?php
/**
 * Unit tests for WelcomeService.
 *
 * @package FluxMedia\Tests\Unit
 * @since 4.3.1
 */

namespace FluxMedia\Tests\Unit;

use FluxMedia\App\Services\WelcomeService;
use PHPUnit\Framework\TestCase;

/**
 * WelcomeService unit tests.
 *
 * @since 4.3.1
 */
class WelcomeServiceTest extends TestCase {

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
	 * Activation arms the once-ever welcome flag.
	 *
	 * @since 4.3.1
	 * @return void
	 */
	public function testArmForActivationSetsShowWelcomeOption() {
		$this->assertFalse( WelcomeService::should_show_from_option() );

		WelcomeService::arm_for_activation();

		$this->assertTrue( WelcomeService::should_show_from_option() );
		$this->assertSame( 1, $GLOBALS['fmo_test_options'][ WelcomeService::OPTION_SHOW_WELCOME ] );
	}

	/**
	 * Mark viewed clears the once-ever welcome flag.
	 *
	 * @since 4.3.1
	 * @return void
	 */
	public function testMarkViewedClearsShowWelcomeOption() {
		WelcomeService::arm_for_activation();
		$this->assertTrue( WelcomeService::should_show_from_option() );

		WelcomeService::mark_viewed();

		$this->assertFalse( WelcomeService::should_show_from_option() );
		$this->assertArrayNotHasKey( WelcomeService::OPTION_SHOW_WELCOME, $GLOBALS['fmo_test_options'] );
	}
}
