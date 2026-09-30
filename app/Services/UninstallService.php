<?php
/**
 * Plugin uninstall cleanup that does not touch the WordPress filesystem API.
 *
 * @package FluxMedia\App\Services
 * @since 4.4.1
 */

namespace FluxMedia\App\Services;

/**
 * Drops plugin data on uninstall without calling WP_Filesystem().
 *
 * WordPress connects $wp_filesystem before uninstall, then deletes the plugin
 * directory with that same global. Replacing it with an unauthenticated
 * WP_Filesystem() call makes FTP/SSH deletes fail.
 *
 * @since 4.4.1
 */
class UninstallService {

	/**
	 * Run uninstall cleanup. Best-effort file deletes never block data cleanup.
	 *
	 * @since 4.4.1
	 * @return void
	 */
	public static function run(): void {
		self::delete_local_converted_files();
		self::delete_plugin_meta();

		if ( class_exists( Database::class ) ) {
			Database::drop_tables();
		}

		foreach ( Settings::get_uninstall_option_names() as $option ) {
			delete_option( $option );
			if ( function_exists( 'delete_site_option' ) ) {
				delete_site_option( $option );
			}
		}

		self::delete_plugin_transients();

		if ( function_exists( 'wp_clear_scheduled_hook' ) ) {
			wp_clear_scheduled_hook( 'flux_media_optimizer_cleanup' );
		}
	}

	/**
	 * Delete local converted files recorded in attachment meta, then an empty legacy directory.
	 *
	 * @since 4.4.1
	 * @return void
	 */
	public static function delete_local_converted_files(): void {
		global $wpdb;

		if ( ! isset( $wpdb ) || ! is_object( $wpdb ) || ! method_exists( $wpdb, 'get_results' ) ) {
			return;
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- uninstall must read every converted-file row once.
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT meta_value FROM {$wpdb->postmeta} WHERE meta_key = %s",
				AttachmentMetaHandler::META_KEY_CONVERTED_FILES_BY_SIZE
			)
		);

		if ( is_array( $rows ) ) {
			foreach ( $rows as $row ) {
				$value = is_object( $row ) && isset( $row->meta_value ) ? $row->meta_value : null;
				if ( is_string( $value ) && function_exists( 'maybe_unserialize' ) ) {
					$value = maybe_unserialize( $value );
				}
				if ( is_array( $value ) ) {
					LocalConvertedFileDeleter::delete_grouped( $value );
				}
			}
		}

		self::delete_legacy_converted_directory();
	}

	/**
	 * Delete every attachment meta key owned by this plugin.
	 *
	 * @since 4.4.1
	 * @return void
	 */
	public static function delete_plugin_meta(): void {
		global $wpdb;

		if ( ! isset( $wpdb ) || ! is_object( $wpdb ) || ! method_exists( $wpdb, 'query' ) ) {
			return;
		}

		$like = self::plugin_meta_like_pattern();
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- one-shot uninstall meta wipe.
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE %s",
				$like
			)
		);
	}

	/**
	 * LIKE pattern for plugin attachment meta, with escaped underscores.
	 *
	 * @since 4.4.1
	 * @return string
	 */
	public static function plugin_meta_like_pattern(): string {
		global $wpdb;

		$prefix = '_flux_media_optimizer_';
		if ( isset( $wpdb ) && is_object( $wpdb ) && method_exists( $wpdb, 'esc_like' ) ) {
			return $wpdb->esc_like( $prefix ) . '%';
		}

		return addcslashes( $prefix, '_%\\' ) . '%';
	}

	/**
	 * Remove plugin transients from the options table.
	 *
	 * @since 4.4.1
	 * @return void
	 */
	private static function delete_plugin_transients(): void {
		global $wpdb;

		if ( ! isset( $wpdb ) || ! is_object( $wpdb ) || empty( $wpdb->options ) || ! method_exists( $wpdb, 'query' ) ) {
			return;
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- one-shot uninstall transient wipe.
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
				$wpdb->esc_like( '_transient_flux_media_optimizer_' ) . '%',
				$wpdb->esc_like( '_transient_timeout_flux_media_optimizer_' ) . '%'
			)
		);
	}

	/**
	 * Remove the unused legacy converted directory when it sits inside uploads.
	 *
	 * @since 4.4.1
	 * @return void
	 */
	private static function delete_legacy_converted_directory(): void {
		if ( ! function_exists( 'wp_upload_dir' ) ) {
			return;
		}

		$upload_dir = wp_upload_dir();
		if ( ! is_array( $upload_dir ) || empty( $upload_dir['basedir'] ) ) {
			return;
		}

		$dir  = wp_normalize_path( $upload_dir['basedir'] . '/flux-media-optimizer-converted' );
		$root = UploadPathGuard::get_uploads_basedir();
		if ( false === $root || ! is_dir( $dir ) || ! UploadPathGuard::is_existing_path_within( $dir, $root ) ) {
			return;
		}

		self::delete_tree( $dir );
	}

	/**
	 * Recursively delete a directory with wp_delete_file and rmdir.
	 *
	 * @since 4.4.1
	 * @param string $dir Directory path.
	 * @return void
	 */
	private static function delete_tree( $dir ): void {
		$entries = scandir( $dir );
		if ( ! is_array( $entries ) ) {
			return;
		}

		foreach ( $entries as $entry ) {
			if ( '.' === $entry || '..' === $entry ) {
				continue;
			}
			$path = $dir . '/' . $entry;
			if ( is_dir( $path ) ) {
				self::delete_tree( $path );
				continue;
			}
			if ( function_exists( 'wp_delete_file' ) ) {
				wp_delete_file( $path );
			}
		}

		@rmdir( $dir ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- leftover dir must not abort uninstall.
	}
}
