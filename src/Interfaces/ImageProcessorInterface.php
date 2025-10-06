<?php
/**
 * Image processor interface.
 *
 * @package FluxMedia
 * @since 1.0.0
 */

namespace FluxMedia\Interfaces;

/**
 * Interface for image processors (GD, Imagick, etc.).
 *
 * @since 1.0.0
 */
interface ImageProcessorInterface {

	/**
	 * Get processor information.
	 *
	 * @since 1.0.0
	 * @return array Processor information including capabilities.
	 */
	public function get_info();

	/**
	 * Convert image to WebP format.
	 *
	 * @since 1.0.0
	 * @param string $source_path Source image path.
	 * @param string $destination_path Destination path.
	 * @param array  $options Conversion options.
	 * @return bool True on success, false on failure.
	 */
	public function convert_to_webp( $source_path, $destination_path, $options = [] );

	/**
	 * Convert image to AVIF format.
	 *
	 * @since 1.0.0
	 * @param string $source_path Source image path.
	 * @param string $destination_path Destination path.
	 * @param array  $options Conversion options.
	 * @return bool True on success, false on failure.
	 */
	public function convert_to_avif( $source_path, $destination_path, $options = [] );

	/**
	 * Check if processor supports WebP.
	 *
	 * @since 1.0.0
	 * @return bool True if WebP is supported, false otherwise.
	 */
	public function supports_webp();

	/**
	 * Check if processor supports AVIF.
	 *
	 * @since 1.0.0
	 * @return bool True if AVIF is supported, false otherwise.
	 */
	public function supports_avif();
}
