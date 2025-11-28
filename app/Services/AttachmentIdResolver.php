<?php
/**
 * Attachment ID resolver service.
 *
 * Provides centralized methods to resolve attachment_id from various inputs
 * (URLs, file paths, CDN URLs).
 *
 * @package FluxMedia
 * @since 3.0.0
 */

namespace FluxMedia\App\Services;

/**
 * Service for resolving attachment IDs from various inputs.
 *
 * @since 3.0.0
 */
class AttachmentIdResolver {

	/**
	 * Resolve attachment ID from a URL or file path.
	 *
	 * Automatically detects type and calls appropriate method.
	 *
	 * @since 3.0.0
	 * @param string $input URL or file path.
	 * @return int|null Attachment ID or null if not found.
	 */
	public static function resolve( $input ) {
		if ( empty( $input ) || ! is_string( $input ) ) {
			return null;
		}

		// Check if it's a URL (starts with http:// or https://).
		if ( strpos( $input, 'http://' ) === 0 || strpos( $input, 'https://' ) === 0 ) {
			return self::from_url( $input );
		}

		// Otherwise, treat as file path.
		return self::from_file_path( $input );
	}

	/**
	 * Resolve attachment ID from a URL (WordPress URL or CDN URL).
	 *
	 * @since 3.0.0
	 * @param string $url URL to resolve.
	 * @return int|null Attachment ID or null if not found.
	 */
	public static function from_url( $url ) {
		if ( empty( $url ) || ! is_string( $url ) ) {
			return null;
		}

		// First, try WordPress attachment_url_to_postid() for WordPress URLs.
		$attachment_id = attachment_url_to_postid( $url );
		if ( $attachment_id ) {
			return $attachment_id;
		}

		// If that fails, try database lookup for WordPress URLs.
		$attachment_id = self::from_wordpress_url( $url );
		if ( $attachment_id ) {
			return $attachment_id;
		}

		// If it's a CDN URL, try to resolve from CDN URL.
		return self::from_cdn_url( $url );
	}

	/**
	 * Resolve attachment ID from a WordPress URL using database lookup.
	 *
	 * @since 3.0.0
	 * @param string $url WordPress URL.
	 * @return int|null Attachment ID or null if not found.
	 */
	private static function from_wordpress_url( $url ) {
		global $wpdb;

		// Extract file path from URL.
		$upload_dir = wp_upload_dir();
		$base_url = $upload_dir['baseurl'];
		
		if ( strpos( $url, $base_url ) !== 0 ) {
			return null;
		}

		// Get relative path.
		$relative_path = str_replace( $base_url, '', $url );
		$relative_path = ltrim( $relative_path, '/' );

		// Query by _wp_attached_file meta.
		$attachment_id = $wpdb->get_var( $wpdb->prepare(
			"SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_wp_attached_file' AND meta_value = %s LIMIT 1",
			$relative_path
		) );

		return $attachment_id ? (int) $attachment_id : null;
	}

	/**
	 * Resolve attachment ID from a local file path.
	 *
	 * @since 3.0.0
	 * @param string $file_path File path to resolve.
	 * @return int|null Attachment ID or null if not found.
	 */
	public static function from_file_path( $file_path ) {
		if ( empty( $file_path ) || ! is_string( $file_path ) ) {
			return null;
		}

		global $wpdb;

		// Normalize path.
		$file_path = wp_normalize_path( $file_path );
		
		// Get upload directory.
		$upload_dir = wp_upload_dir();
		$upload_path = wp_normalize_path( $upload_dir['basedir'] );

		// Extract relative path.
		if ( strpos( $file_path, $upload_path ) !== 0 ) {
			return null;
		}

		$relative_path = str_replace( $upload_path, '', $file_path );
		$relative_path = ltrim( $relative_path, '/' );

		// Query by _wp_attached_file meta.
		$attachment_id = $wpdb->get_var( $wpdb->prepare(
			"SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_wp_attached_file' AND meta_value = %s LIMIT 1",
			$relative_path
		) );

		return $attachment_id ? (int) $attachment_id : null;
	}

	/**
	 * Resolve attachment ID from a CDN URL.
	 *
	 * Queries external_jobs table and attachment meta for matching CDN URLs.
	 *
	 * @since 3.0.0
	 * @param string $cdn_url CDN URL to resolve.
	 * @return int|null Attachment ID or null if not found.
	 */
	public static function from_cdn_url( $cdn_url ) {
		if ( empty( $cdn_url ) || ! is_string( $cdn_url ) ) {
			return null;
		}

		global $wpdb;

		// First, try to find by matching CDN URL in attachment meta.
		$attachment_id = self::from_cdn_url_in_meta( $cdn_url );
		if ( $attachment_id ) {
			return $attachment_id;
		}

		// Fallback: try to find by base_url pattern in external_jobs table.
		$table_name = $wpdb->prefix . 'flux_media_optimizer_external_jobs';
		
		// Check if table exists.
		if ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table_name ) ) !== $table_name ) {
			return null;
		}

		// Parse URL to extract base path.
		$parsed = wp_parse_url( $cdn_url );
		if ( empty( $parsed['scheme'] ) || empty( $parsed['host'] ) ) {
			return null;
		}

		$base_url = $parsed['scheme'] . '://' . $parsed['host'];
		if ( ! empty( $parsed['path'] ) ) {
			$path_parts = explode( '/', trim( $parsed['path'], '/' ) );
			// Remove last two parts (format and filename) to get base path.
			if ( count( $path_parts ) > 2 ) {
				array_pop( $path_parts ); // Remove filename.
				array_pop( $path_parts ); // Remove format.
				$base_url .= '/' . implode( '/', $path_parts );
			}
		}
		$base_url = trailingslashit( $base_url );

		// Query external_jobs table for matching base_url.
		$attachment_id = $wpdb->get_var( $wpdb->prepare(
			"SELECT attachment_id FROM {$table_name} WHERE base_url = %s AND status = 'completed' ORDER BY id DESC LIMIT 1",
			$base_url
		) );

		return $attachment_id ? (int) $attachment_id : null;
	}

	/**
	 * Resolve attachment ID from CDN URL stored in attachment meta.
	 *
	 * @since 3.0.0
	 * @param string $cdn_url CDN URL to find.
	 * @return int|null Attachment ID or null if not found.
	 */
	private static function from_cdn_url_in_meta( $cdn_url ) {
		global $wpdb;

		// Search in META_KEY_CONVERTED_FILES_BY_SIZE.
		$meta_key = AttachmentMetaHandler::META_KEY_CONVERTED_FILES_BY_SIZE;
		
		// Query all attachments with this meta key.
		$results = $wpdb->get_results( $wpdb->prepare(
			"SELECT post_id, meta_value FROM {$wpdb->postmeta} WHERE meta_key = %s",
			$meta_key
		), ARRAY_A );

		foreach ( $results as $row ) {
			$meta_value = maybe_unserialize( $row['meta_value'] );
			if ( ! is_array( $meta_value ) ) {
				continue;
			}

			// Search through all sizes and formats.
			foreach ( $meta_value as $size_formats ) {
				if ( ! is_array( $size_formats ) ) {
					continue;
				}
				foreach ( $size_formats as $data ) {
					// Extract URL from unified structure.
					if ( is_array( $data ) && isset( $data['url'] ) ) {
						$url_or_path = $data['url'];
						if ( is_string( $url_or_path ) && $url_or_path === $cdn_url ) {
							return (int) $row['post_id'];
						}
					}
				}
			}
		}

		return null;
	}
}

