<?php
/**
 * Unit tests for AttachmentDetailsMountRenderer.
 *
 * @package FluxMedia\Tests\Unit
 * @since 4.3.0
 */

namespace FluxMedia\Tests\Unit;

use FluxMedia\App\Services\AttachmentDetailsMountRenderer;
use PHPUnit\Framework\TestCase;

/**
 * AttachmentDetailsMountRenderer unit tests.
 *
 * @since 4.3.0
 */
class AttachmentDetailsMountRendererTest extends TestCase {

	/**
	 * Reset stubs.
	 *
	 * @since 4.3.0
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['fmo_test_capabilities'] = [];
	}

	/**
	 * Mount field uses a full-width compat tr without embedded JSON payload.
	 *
	 * @since 4.3.0
	 * @return void
	 */
	public function testModifyAttachmentFieldsEmitsSkeletonWithoutPayload() {
		$post = (object) [
			'ID'        => 901,
			'post_type' => 'attachment',
		];

		$renderer = new AttachmentDetailsMountRenderer();
		$fields   = $renderer->modify_attachment_fields( [], $post );

		$this->assertArrayHasKey( 'flux_media_optimizer', $fields );
		$this->assertTrue( $fields['flux_media_optimizer']['show_in_modal'] );
		$this->assertTrue( $fields['flux_media_optimizer']['show_in_edit'] );
		$this->assertArrayHasKey( 'tr', $fields['flux_media_optimizer'] );
		$tr = $fields['flux_media_optimizer']['tr'];
		$this->assertStringContainsString( 'colspan="2"', $tr );
		$this->assertStringContainsString( 'compat-field-flux_media_optimizer', $tr );
		$this->assertStringContainsString( 'data-flux-media-attachment-id="901"', $tr );
		$this->assertStringContainsString( 'data-flux-media-attachment-skeleton="1"', $tr );
		$this->assertStringContainsString( 'data-flux-media-attachment-app="1"', $tr );
		$this->assertStringNotContainsString( 'application/json', $tr );
		$this->assertStringNotContainsString( 'data-flux-media-attachment-data', $tr );
		$this->assertStringNotContainsString( 'fluxMediaConvertAttachment', $tr );
	}

	/**
	 * Field is prepended so it renders first in AttachmentCompat (under Copy URL).
	 *
	 * @since 4.3.0
	 * @return void
	 */
	public function testModifyAttachmentFieldsPrependsOptimizerField() {
		$post = (object) [
			'ID'        => 904,
			'post_type' => 'attachment',
		];

		$renderer = new AttachmentDetailsMountRenderer();
		$fields   = $renderer->modify_attachment_fields(
			[
				'existing_tax' => [ 'label' => 'Tax' ],
				'title'        => [ 'label' => 'Title' ],
			],
			$post
		);

		$this->assertSame(
			[ 'flux_media_optimizer', 'existing_tax', 'title' ],
			array_keys( $fields )
		);
	}

	/**
	 * build_mount_html matches the markup embedded in the compat tr.
	 *
	 * @since 4.3.0
	 * @return void
	 */
	public function testBuildMountHtmlMatchesFieldHtml() {
		$renderer = new AttachmentDetailsMountRenderer();
		$html     = $renderer->build_mount_html( 903 );
		$tr       = $renderer->build_compat_tr( 903 );

		$this->assertStringContainsString( 'data-flux-media-attachment-id="903"', $html );
		$this->assertStringContainsString( 'id="flux-media-optimizer-attachment-903"', $html );
		$this->assertStringContainsString( 'data-flux-media-attachment-skeleton="1"', $html );
		$this->assertStringContainsString( $html, $tr );
		$this->assertStringContainsString( 'colspan="2"', $tr );
	}

	/**
	 * Users without edit_post capability do not receive the mount field.
	 *
	 * @since 4.3.0
	 * @return void
	 */
	public function testModifyAttachmentFieldsSkipsWithoutCapability() {
		$GLOBALS['fmo_test_capabilities']['edit_post:902'] = false;
		$post = (object) [
			'ID'        => 902,
			'post_type' => 'attachment',
		];

		$renderer = new AttachmentDetailsMountRenderer();
		$fields   = $renderer->modify_attachment_fields( [ 'title' => [] ], $post );

		$this->assertArrayNotHasKey( 'flux_media_optimizer', $fields );
		$this->assertArrayHasKey( 'title', $fields );
	}
}
