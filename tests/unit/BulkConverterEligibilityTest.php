<?php
/**
 * Unit tests for bulk eligible SQL and the count cache.
 *
 * @package FluxMedia\Tests\Unit
 * @since 4.4.1
 */

namespace FluxMedia\Tests\Unit;

use FluxMedia\App\Services\AttachmentMetaHandler;
use FluxMedia\App\Services\BulkConverter;
use FluxMedia\App\Services\ConversionTracker;
use FluxMedia\App\Services\EligibleMimeRegistry;
use FluxMedia\App\Services\MediaProcessingServiceLocator;
use FluxMedia\FluxPlugins\Common\Logger\Logger;
use PHPUnit\Framework\TestCase;

/**
 * Eligible MIME list, SQL shape, and transient cache tests.
 *
 * @since 4.4.1
 */
class BulkConverterEligibilityTest extends TestCase {

	/**
	 * Reset transient stub store.
	 *
	 * @since 4.4.1
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['fmo_test_transients']   = [];
		$GLOBALS['fmo_test_object_cache'] = [];
		$GLOBALS['fmo_test_options']      = [];
	}

	/**
	 * Optimize MIME list includes stored image and video types and excludes SVG and MKV.
	 *
	 * @since 4.4.1
	 * @return void
	 */
	public function testOptimizableMimesIncludeJpegAndQuicktime() {
		$mimes = ( new EligibleMimeRegistry() )->get_optimizable_mime_types();

		$this->assertContains( 'image/jpeg', $mimes );
		$this->assertContains( 'video/quicktime', $mimes );
		$this->assertContains( 'video/ogg', $mimes );
		$this->assertNotContains( 'image/svg+xml', $mimes );
		$this->assertNotContains( 'video/x-matroska', $mimes );
		$this->assertNotContains( 'video/mov', $mimes );
	}

	/**
	 * Local SQL binds an IN list and does not interpolate LIKE wildcards.
	 *
	 * @since 4.4.1
	 * @return void
	 */
	public function testLocalSqlUsesMimeInList() {
		$converter = $this->converter();
		$sql       = $converter->get_eligible_attachments_sql( false );
		$args      = $converter->get_eligible_query_args( false );

		$this->assertStringContainsString( 'post_mime_type IN (', $sql );
		$this->assertStringNotContainsString( 'LIKE', $sql );
		$this->assertContains( 'image/jpeg', $args );
		$this->assertContains( 'video/quicktime', $args );
		$this->assertNotContains( 'image/svg+xml', $args );
		$this->assertNotContains( 'video/x-matroska', $args );
		$this->assertNotContains( 'application/pdf', $args );
		$this->assertSame( substr_count( $sql, '%s' ), count( $args ) );
	}

	/**
	 * Cloud SQL has no MIME predicate, so PDFs stay eligible.
	 *
	 * @since 4.4.1
	 * @return void
	 */
	public function testCloudSqlIncludesAllAttachmentTypes() {
		$converter = $this->converter();
		$sql       = $converter->get_eligible_attachments_sql( true );

		$this->assertStringNotContainsString( 'post_mime_type', $sql );
		$this->assertStringContainsString( 'NOT EXISTS', $sql );
		$this->assertContains( AttachmentMetaHandler::META_KEY_INELIGIBLE, $converter->get_eligible_query_args( true ) );
	}

	/**
	 * Eligible count is served from the 2-minute transient and cleared on ineligible writes.
	 *
	 * @since 4.4.1
	 * @return void
	 */
	public function testCountUsesTransientAndInvalidates() {
		$key = BulkConverter::eligible_count_transient_key( false );
		set_transient( $key, 42, BulkConverter::ELIGIBLE_COUNT_TTL );

		$this->assertSame( 120, BulkConverter::ELIGIBLE_COUNT_TTL );
		$this->assertSame( 42, $this->converter()->count_eligible_media() );

		AttachmentMetaHandler::mark_ineligible( 900 );

		$this->assertFalse( get_transient( $key ) );
	}

	/**
	 * Build a converter with unused collaborators.
	 *
	 * @since 4.4.1
	 * @return BulkConverter
	 */
	private function converter(): BulkConverter {
		return new BulkConverter(
			$this->createMock( Logger::class ),
			$this->createMock( MediaProcessingServiceLocator::class ),
			$this->createMock( ConversionTracker::class )
		);
	}
}
