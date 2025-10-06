<?php
/**
 * Video processor interface.
 *
 * @package FluxMedia
 * @since 1.0.0
 */

namespace FluxMedia\Interfaces;

/**
 * Interface for video processors (FFmpeg, etc.).
 *
 * @since 1.0.0
 */
interface VideoProcessorInterface {

	/**
	 * Get processor information.
	 *
	 * @since 1.0.0
	 * @return array Processor information including capabilities.
	 */
	public function get_info();

	/**
	 * Convert video to AV1 format.
	 *
	 * @since 1.0.0
	 * @param string $source_path Source video path.
	 * @param string $destination_path Destination path.
	 * @param array  $options Conversion options.
	 * @return bool True on success, false on failure.
	 */
	public function convert_to_av1( $source_path, $destination_path, $options = [] );

	/**
	 * Convert video to WebM format.
	 *
	 * @since 1.0.0
	 * @param string $source_path Source video path.
	 * @param string $destination_path Destination path.
	 * @param array  $options Conversion options.
	 * @return bool True on success, false on failure.
	 */
	public function convert_to_webm( $source_path, $destination_path, $options = [] );

	/**
	 * Get video metadata.
	 *
	 * @since 1.0.0
	 * @param string $file_path Video file path.
	 * @return array|false Video metadata or false on failure.
	 */
	public function get_metadata( $file_path );

	/**
	 * Check if processor supports AV1.
	 *
	 * @since 1.0.0
	 * @return bool True if AV1 is supported, false otherwise.
	 */
	public function supports_av1();

	/**
	 * Check if processor supports WebM.
	 *
	 * @since 1.0.0
	 * @return bool True if WebM is supported, false otherwise.
	 */
	public function supports_webm();
}
