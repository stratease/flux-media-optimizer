<?php
/**
 * Regression: optimized videos must not feed image_downsize with video URLs.
 *
 * @package FluxMedia\Tests\Unit
 * @since 4.3.2
 */

namespace FluxMedia\Tests\Unit;

use FluxMedia\App\Services\AttachmentMetaHandler;
use FluxMedia\App\Services\Converter;
use FluxMedia\App\Services\WordPressProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Guards Media Library list thumbnails from using converted video URLs as <img> src.
 *
 * @since 4.3.2
 */
class ImageDownsizeVideoGuardTest extends TestCase {

	/**
	 * Reset stub state.
	 *
	 * @since 4.3.2
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['fmo_test_post_meta']           = [];
		$GLOBALS['fmo_test_mimetypes']           = [];
		$GLOBALS['fmo_test_attachment_metadata'] = [];
		$GLOBALS['fmo_test_is_admin']            = false;
		$GLOBALS['fmo_test_doing_ajax']          = false;
	}

	/**
	 * Build WordPressProvider without constructor dependencies.
	 *
	 * @since 4.3.2
	 * @return WordPressProvider
	 */
	private function make_provider(): WordPressProvider {
		$ref = new ReflectionClass( WordPressProvider::class );
		return $ref->newInstanceWithoutConstructor();
	}

	/**
	 * Optimized video with converted meta must not return a URL from image_downsize.
	 *
	 * @since 4.3.2
	 * @return void
	 */
	public function testOptimizedVideoImageDownsizeReturnsDefault() {
		$attachment_id = 1583;
		$GLOBALS['fmo_test_mimetypes'][ $attachment_id ] = 'video/mp4';

		AttachmentMetaHandler::set_converted_files_grouped_by_size(
			$attachment_id,
			[
				'full' => [
					'original' => [
						'url'      => 'https://example.com/uploads/clip.mp4',
						'filesize' => 1500000,
					],
					'av1'      => [
						'url'      => 'https://cdn.example.com/clip-av1.mp4',
						'filesize' => 800000,
					],
					'webm'     => [
						'url'      => 'https://cdn.example.com/clip.webm',
						'filesize' => 900000,
					],
				],
			]
		);

		$provider = $this->make_provider();
		$result   = $provider->handle_image_downsize_filter( false, $attachment_id, [ 60, 60 ] );

		$this->assertFalse( $result );
	}

	/**
	 * Optimized image still receives converted WebP URL from image_downsize.
	 *
	 * @since 4.3.2
	 * @return void
	 */
	public function testOptimizedImageImageDownsizeReturnsWebpUrl() {
		$attachment_id = 42;
		$GLOBALS['fmo_test_mimetypes'][ $attachment_id ] = 'image/jpeg';
		$GLOBALS['fmo_test_attachment_metadata'][ $attachment_id ] = [
			'width'  => 800,
			'height' => 600,
		];

		$webp_url = 'https://cdn.example.com/photo.webp';
		AttachmentMetaHandler::set_converted_files_grouped_by_size(
			$attachment_id,
			[
				'full' => [
					'original'                 => [
						'url'      => 'https://example.com/uploads/photo.jpg',
						'filesize' => 200000,
					],
					Converter::FORMAT_WEBP     => [
						'url'      => $webp_url,
						'filesize' => 50000,
					],
				],
			]
		);

		$provider = $this->make_provider();
		$result   = $provider->handle_image_downsize_filter( false, $attachment_id, 'full' );

		$this->assertIsArray( $result );
		$this->assertSame( $webp_url, $result[0] );
	}
}
