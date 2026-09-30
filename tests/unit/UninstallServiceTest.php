<?php
/**
 * Unit tests for uninstall cleanup.
 *
 * @package FluxMedia\Tests\Unit
 * @since 4.4.1
 */

namespace FluxMedia\Tests\Unit;

use FluxMedia\App\Services\LocalConvertedFileDeleter;
use FluxMedia\App\Services\Settings;
use FluxMedia\App\Services\UninstallService;
use PHPUnit\Framework\TestCase;

/**
 * Uninstall must not call WP_Filesystem and must still delete plugin meta.
 *
 * @since 4.4.1
 */
class UninstallServiceTest extends TestCase {

	/**
	 * Uploads root for this test.
	 *
	 * @since 4.4.1
	 * @var string
	 */
	private $uploads;

	/**
	 * Create a temp uploads directory and a fake wpdb.
	 *
	 * @since 4.4.1
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->uploads = sys_get_temp_dir() . '/fmo-uninstall-' . uniqid( '', true );
		mkdir( $this->uploads . '/2024', 0755, true );
		$GLOBALS['fmo_test_upload_dir'] = [
			'basedir' => $this->uploads,
			'baseurl' => 'https://example.com/wp-content/uploads',
			'error'   => false,
		];
		$GLOBALS['fmo_test_deleted_files']  = [];
		$GLOBALS['fmo_wp_filesystem_calls'] = 0;
		$GLOBALS['fmo_test_options']        = [
			Settings::get_options_option_name() => [ 'log_level' => 'error' ],
		];
		$GLOBALS['wpdb']                    = new class() {
			public $posts    = 'wp_posts';
			public $postmeta = 'wp_postmeta';
			public $options  = 'wp_options';
			public $prefix   = 'wp_';
			public $queries  = [];

			public function esc_like( $text ) {
				return addcslashes( (string) $text, '_%\\' );
			}

			public function prepare( $query, ...$args ) {
				$index = 0;
				return preg_replace_callback(
					'/%s/',
					function () use ( &$index, $args ) {
						$value = $args[ $index ] ?? '';
						$index++;
						return "'" . $value . "'";
					},
					$query
				);
			}

			public function query( $sql ) {
				$this->queries[] = $sql;
				return 0;
			}

			public function get_results( $sql ) {
				$this->queries[] = $sql;
				return [];
			}
		};
	}

	/**
	 * Remove the temp uploads tree.
	 *
	 * @since 4.4.1
	 * @return void
	 */
	protected function tearDown(): void {
		if ( is_dir( $this->uploads ) ) {
			$iterator = new \RecursiveIteratorIterator(
				new \RecursiveDirectoryIterator( $this->uploads, \FilesystemIterator::SKIP_DOTS ),
				\RecursiveIteratorIterator::CHILD_FIRST
			);
			foreach ( $iterator as $item ) {
				$item->isDir() ? rmdir( $item->getPathname() ) : unlink( $item->getPathname() );
			}
			rmdir( $this->uploads );
		}
		parent::tearDown();
	}

	/**
	 * Uninstall never calls WP_Filesystem and still issues the meta LIKE delete.
	 *
	 * @since 4.4.1
	 * @return void
	 */
	public function testRunDoesNotCallWpFilesystemAndDeletesMeta() {
		UninstallService::run();

		$this->assertSame( 0, $GLOBALS['fmo_wp_filesystem_calls'] );
		$meta = array_values(
			array_filter(
				$GLOBALS['wpdb']->queries,
				static function ( $sql ) {
					return false !== strpos( $sql, 'wp_postmeta' ) && false !== strpos( $sql, 'DELETE' );
				}
			)
		);
		$this->assertNotEmpty( $meta );
		$this->assertStringContainsString( '\_flux\_media\_optimizer\_%', $meta[0] );
		$this->assertArrayNotHasKey( 'flux-plugins_account_id', $GLOBALS['fmo_test_options'] );
	}

	/**
	 * Local uploads URLs are deleted. CDN URLs are not.
	 *
	 * @since 4.4.1
	 * @return void
	 */
	public function testDeleteGroupedSkipsCdnAndDeletesLocal() {
		$file = $this->uploads . '/2024/photo.webp';
		file_put_contents( $file, 'webp' );
		$calls = (int) $GLOBALS['fmo_wp_filesystem_calls'];

		$deleted = LocalConvertedFileDeleter::delete_grouped(
			[
				'full' => [
					'webp' => [ 'url' => 'https://example.com/wp-content/uploads/2024/photo.webp' ],
					'cdn'  => [ 'url' => 'https://cdn.fluxplugins.com/photo.webp' ],
				],
			]
		);

		$this->assertSame( 1, $deleted );
		$this->assertFileDoesNotExist( $file );
		$this->assertSame( $calls, $GLOBALS['fmo_wp_filesystem_calls'] );
		$this->assertNotContains( 'https://cdn.fluxplugins.com/photo.webp', $GLOBALS['fmo_test_deleted_files'] );
	}
}
