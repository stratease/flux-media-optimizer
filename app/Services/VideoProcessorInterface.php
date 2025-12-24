<?php
/**
 * Video processor interface for video processing libraries.
 *
 * @package FluxMedia
 * @since 0.1.0
 */

namespace FluxMedia\App\Services;

/**
 * Video processor interface for video processing libraries.
 *
 * @since 0.1.0
 */
interface VideoProcessorInterface {

    /**
     * Convert video to AV1 format.
     *
     * @since 0.1.0
     * @param string $source_path Source video path.
     * @param string $destination_path Destination path.
     * @param array  $options Conversion options.
     * @return bool True on success, false on failure.
     */
    public function convert_to_av1( $source_path, $destination_path, $options = [] );

    /**
     * Convert video to WebM format.
     *
     * @since 0.1.0
     * @param string $source_path Source video path.
     * @param string $destination_path Destination path.
     * @param array  $options Conversion options.
     * @return bool True on success, false on failure.
     */
    public function convert_to_webm( $source_path, $destination_path, $options = [] );

    /**
     * Get processor information.
     *
     * @since 0.1.0
     * @return array Processor information.
     */
    public function get_info();
}
