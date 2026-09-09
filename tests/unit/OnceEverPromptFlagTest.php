<?php
/**
 * Unit tests for OnceEverPromptFlag.
 *
 * @package FluxMedia\Tests\Unit
 * @since 4.3.1
 */

namespace FluxMedia\Tests\Unit;

use FluxMedia\App\Services\OnceEverPromptFlag;
use PHPUnit\Framework\TestCase;

/**
 * OnceEverPromptFlag unit tests.
 *
 * @since 4.3.1
 */
class OnceEverPromptFlagTest extends TestCase {

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
	 * Set stores the option as 1 and is_set returns true.
	 *
	 * @since 4.3.1
	 * @return void
	 */
	public function testSetStoresFlagAndIsSetReturnsTrue() {
		$option = 'flux_media_optimizer_test_once_ever';

		$this->assertFalse( OnceEverPromptFlag::is_set( $option ) );

		OnceEverPromptFlag::set( $option );

		$this->assertTrue( OnceEverPromptFlag::is_set( $option ) );
		$this->assertSame( 1, $GLOBALS['fmo_test_options'][ $option ] );
	}

	/**
	 * Clear removes the option so is_set returns false.
	 *
	 * @since 4.3.1
	 * @return void
	 */
	public function testClearRemovesFlag() {
		$option = 'flux_media_optimizer_test_once_ever';

		OnceEverPromptFlag::set( $option );
		$this->assertTrue( OnceEverPromptFlag::is_set( $option ) );

		OnceEverPromptFlag::clear( $option );

		$this->assertFalse( OnceEverPromptFlag::is_set( $option ) );
		$this->assertArrayNotHasKey( $option, $GLOBALS['fmo_test_options'] );
	}
}
