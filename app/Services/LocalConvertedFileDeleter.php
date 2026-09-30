<?php
/**
 * Deletes local converted files without the WordPress filesystem API.
 *
 * @package FluxMedia\App\Services
 * @since 4.4.1
 */

namespace FluxMedia\App\Services;

/**
 * Best-effort local file delete for attachment and uninstall cleanup.
 *
 * Never calls WP_Filesystem(). FTP/SSH hosts prompt for credentials inside
 * that API and can break AJAX or clobber the plugin-delete filesystem handle.
 *
 * @since 4.4.1
 */
class LocalConvertedFileDeleter {

	/**
	 * Delete local files described by converted-files-by-size meta.
	 *
	 * CDN URLs are skipped. Paths outside uploads are skipped.
	 *
	 * @since 4.4.1
	 * @param array<string, mixed> $grouped Converted files grouped by size.
	 * @return int Number of files deleted.
	 */
	public static function delete_grouped( array $grouped ): int {
		$base_url = UploadPathGuard::get_uploads_baseurl();
		$base_dir = UploadPathGuard::get_uploads_basedir();
		$deleted  = 0;

		foreach ( $grouped as $size_formats ) {
			if ( ! is_array( $size_formats ) ) {
				continue;
			}
			foreach ( $size_formats as $data ) {
				$url_or_path = self::extract_url_or_path( $data );
				$path        = self::resolve_local_path( $url_or_path, $base_url, $base_dir );
				if ( false === $path ) {
					continue;
				}
				if ( function_exists( 'wp_delete_file' ) && wp_delete_file( $path ) ) {
					$deleted++;
				}
			}
		}

		return $deleted;
	}

	/**
	 * Resolve a stored URL or path to a file inside uploads.
	 *
	 * @since 4.4.1
	 * @param mixed        $url_or_path Stored value.
	 * @param string|false $base_url    Uploads base URL.
	 * @param string|false $base_dir    Uploads base directory.
	 * @return string|false
	 */
	public static function resolve_local_path( $url_or_path, $base_url, $base_dir ) {
		if ( ! is_string( $url_or_path ) || '' === $url_or_path || false === $base_dir ) {
			return false;
		}

		if ( AttachmentMetaHandler::is_file_url( $url_or_path ) ) {
			if ( false === $base_url ) {
				return false;
			}
			return UploadPathGuard::local_upload_url_to_path( $url_or_path, $base_url, $base_dir );
		}

		$path = wp_normalize_path( $url_or_path );
		if ( ! UploadPathGuard::is_existing_path_within( $path, $base_dir ) ) {
			return false;
		}

		return $path;
	}

	/**
	 * Pull a URL or path out of the grouped meta value.
	 *
	 * @since 4.4.1
	 * @param mixed $data Meta node.
	 * @return string|null
	 */
	private static function extract_url_or_path( $data ) {
		if ( is_array( $data ) && isset( $data['url'] ) && is_string( $data['url'] ) ) {
			return $data['url'];
		}
		if ( is_string( $data ) ) {
			return $data;
		}
		return null;
	}
}
