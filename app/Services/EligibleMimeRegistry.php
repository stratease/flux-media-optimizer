<?php
/**
 * WordPress-stored MIME types eligible for local optimization.
 *
 * @package FluxMedia\App\Services
 * @since 4.4.1
 */

namespace FluxMedia\App\Services;

/**
 * Single source of truth for MIME types local bulk and conversion may optimize.
 *
 * Image types come from SourceFormatRegistry. Video types are the MIME values
 * WordPress stores for mp4, avi, mov, wmv, flv, webm, and ogg video (ogv),
 * not VideoConverter input aliases such as video/mov.
 *
 * @since 4.4.1
 */
class EligibleMimeRegistry {

	/**
	 * Video extensions resolved through the WordPress MIME map.
	 *
	 * Ogg audio (audio/ogg) is excluded. Video Ogg is stored as video/ogg.
	 *
	 * @since 4.4.1
	 * @var array<int, string>
	 */
	private const VIDEO_EXTENSIONS = [ 'mp4', 'avi', 'mov', 'wmv', 'flv', 'webm', 'ogv' ];

	/**
	 * Core MIME map used when wp_get_mime_types() is unavailable (unit tests).
	 *
	 * @since 4.4.1
	 * @var array<string, string>
	 */
	private const FALLBACK_VIDEO_MIMES = [
		'mp4|m4v' => 'video/mp4',
		'avi'     => 'video/avi',
		'mov|qt'  => 'video/quicktime',
		'wmv'     => 'video/x-ms-wmv',
		'flv'     => 'video/x-flv',
		'webm'    => 'video/webm',
		'ogv'     => 'video/ogg',
	];

	/**
	 * Image format registry.
	 *
	 * @since 4.4.1
	 * @var SourceFormatRegistry
	 */
	private $source_formats;

	/**
	 * Constructor.
	 *
	 * @since 4.4.1
	 * @param SourceFormatRegistry|null $source_formats Optional image format registry.
	 */
	public function __construct( ?SourceFormatRegistry $source_formats = null ) {
		$this->source_formats = $source_formats ?? new SourceFormatRegistry();
	}

	/**
	 * MIME types WordPress stores for locally optimizable images and videos.
	 *
	 * @since 4.4.1
	 * @return array<int, string>
	 */
	public function get_optimizable_mime_types(): array {
		$mimes = [];

		foreach ( $this->source_formats->get_supported_extensions() as $extension ) {
			$mime = $this->source_formats->get_mime_for_extension( $extension );
			if ( is_string( $mime ) && '' !== $mime ) {
				$mimes[] = $mime;
			}
		}

		$map = function_exists( 'wp_get_mime_types' ) ? wp_get_mime_types() : self::FALLBACK_VIDEO_MIMES;
		if ( ! is_array( $map ) ) {
			$map = self::FALLBACK_VIDEO_MIMES;
		}

		foreach ( self::VIDEO_EXTENSIONS as $extension ) {
			$mime = $this->mime_for_extension( $map, $extension );
			if ( null !== $mime ) {
				$mimes[] = $mime;
			}
		}

		$mimes = array_values( array_unique( $mimes ) );
		sort( $mimes );

		return $mimes;
	}

	/**
	 * Resolve one extension against a WordPress-style extension|extension MIME map.
	 *
	 * @since 4.4.1
	 * @param array<string, string> $map       Extension list to MIME.
	 * @param string                $extension Extension without a dot.
	 * @return string|null
	 */
	private function mime_for_extension( array $map, $extension ) {
		foreach ( $map as $extensions => $mime ) {
			$parts = explode( '|', (string) $extensions );
			if ( in_array( $extension, $parts, true ) && is_string( $mime ) && '' !== $mime ) {
				return $mime;
			}
		}

		return null;
	}
}
