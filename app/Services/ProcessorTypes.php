<?php
/**
 * Processor type constants.
 *
 * @package FluxMedia
 * @since 0.1.0
 */

namespace FluxMedia\App\Services;

/**
 * Processor type constants for consistent usage throughout the application.
 *
 * @since 0.1.0
 */
class ProcessorTypes {

    /**
     * Image processor types.
     */
    public const IMAGE_GD = 'gd';
    public const IMAGE_IMAGICK = 'imagick';

    /**
     * Video processor types.
     */
    public const VIDEO_FFMPEG = 'ffmpeg';

    /**
     * Get all image processor types.
     *
     * @since 0.1.0
     * @return array Array of image processor type constants.
     */
    public static function get_image_processors() {
        return [
            self::IMAGE_GD,
            self::IMAGE_IMAGICK,
        ];
    }

    /**
     * Get all video processor types.
     *
     * @since 0.1.0
     * @return array Array of video processor type constants.
     */
    public static function get_video_processors() {
        return [
            self::VIDEO_FFMPEG,
        ];
    }

    /**
     * Check if a processor type is a valid image processor.
     *
     * @since 0.1.0
     * @param string $type Processor type to check.
     * @return bool True if valid image processor, false otherwise.
     */
    public static function is_image_processor( $type ) {
        return in_array( $type, self::get_image_processors(), true );
    }

    /**
     * Check if a processor type is a valid video processor.
     *
     * @since 0.1.0
     * @param string $type Processor type to check.
     * @return bool True if valid video processor, false otherwise.
     */
    public static function is_video_processor( $type ) {
        return in_array( $type, self::get_video_processors(), true );
    }
}
