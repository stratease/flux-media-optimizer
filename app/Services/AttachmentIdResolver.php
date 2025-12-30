<?php
/**
 * Attachment ID resolver service.
 *
 * Provides centralized methods to resolve attachment_id from various inputs
 * (URLs, file paths, both local and external file URLs).
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
	 * Resolve attachment ID from a URL (WordPress URL or external file URL).
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

		// If it's an external URL, try to resolve from file URLs meta.
		return self::from_file_url( $url );
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
	 * Resolve attachment ID from a file URL (external or local).
	 *
	 * Queries attachment meta for matching file URLs using AttachmentMetaHandler.
	 *
	 * @since 3.0.0
	 * @param string $file_url File URL to resolve.
	 * @return int|null Attachment ID or null if not found.
	 */
	public static function from_file_url( $file_url ) {
		if ( empty( $file_url ) || ! is_string( $file_url ) ) {
			return null;
		}

		// Find by matching file URL in attachment meta.
		return self::from_file_url_in_meta( $file_url );
	}

	/**
	 * Resolve attachment ID from file URL stored in attachment meta.
	 *
	 * Uses AttachmentMetaHandler file URLs meta field for efficient lookup.
	 * Also checks converted files structure for additional URL matches.
	 *
	 * @since 3.0.0
	 * @param string $file_url File URL to find.
	 * @return int|null Attachment ID or null if not found.
	 */
	private static function from_file_url_in_meta( $file_url ) {
		global $wpdb;

		// Use dedicated file URLs meta field for efficient lookup.
		$meta_key = AttachmentMetaHandler::META_KEY_FILE_URLS;

		// Query attachments with this meta key and search for matching URL.
		// Use LIKE to find the URL in the serialized array.
		$results = $wpdb->get_results( $wpdb->prepare(
			"SELECT post_id, meta_value FROM {$wpdb->postmeta} WHERE meta_key = %s AND meta_value LIKE %s",
			$meta_key,
			'%' . $wpdb->esc_like( $file_url ) . '%'
		), ARRAY_A );

		foreach ( $results as $row ) {
			$meta_value = maybe_unserialize( $row['meta_value'] );
			if ( ! is_array( $meta_value ) ) {
				continue;
			}

			// Check if the file URL exists in the array.
			if ( in_array( $file_url, $meta_value, true ) ) {
				return (int) $row['post_id'];
			}
		}

		// Fallback: Check converted files structure for file URLs.
		// This handles cases where URLs might be stored in the converted files meta.
		$converted_files_meta_key = AttachmentMetaHandler::META_KEY_CONVERTED_FILES_BY_SIZE;
		$converted_results = $wpdb->get_results( $wpdb->prepare(
			"SELECT post_id, meta_value FROM {$wpdb->postmeta} WHERE meta_key = %s AND meta_value LIKE %s",
			$converted_files_meta_key,
			'%' . $wpdb->esc_like( $file_url ) . '%'
		), ARRAY_A );

		foreach ( $converted_results as $row ) {
			$meta_value = maybe_unserialize( $row['meta_value'] );
			if ( ! is_array( $meta_value ) ) {
				continue;
			}

			// Recursively search for the URL in the converted files structure.
			if ( self::search_url_in_converted_files( $meta_value, $file_url ) ) {
				return (int) $row['post_id'];
			}
		}

		return null;
	}

	/**
	 * Recursively search for a URL in the converted files structure.
	 *
	 * @since 3.0.0
	 * @param array  $data Structure to search (can be nested arrays).
	 * @param string $url  URL to find.
	 * @return bool True if URL is found, false otherwise.
	 */
	private static function search_url_in_converted_files( $data, $url ) {
		if ( ! is_array( $data ) ) {
			return false;
		}

		foreach ( $data as $value ) {
			if ( is_array( $value ) ) {
				// Check if this is a file data structure with 'url' key.
				if ( isset( $value['url'] ) && $value['url'] === $url ) {
					return true;
				}
				// Recursively search nested arrays.
				if ( self::search_url_in_converted_files( $value, $url ) ) {
					return true;
				}
			} elseif ( is_string( $value ) && $value === $url ) {
				return true;
			}
		}

		return false;
	}
}
