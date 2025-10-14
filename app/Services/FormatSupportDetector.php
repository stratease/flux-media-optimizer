<?php
/**
 * Format support detector for checking low-level system capabilities.
 *
 * @package FluxMedia
 * @since 0.1.0
 */

namespace FluxMedia\App\Services;

use FluxMedia\App\Services\ProcessorTypes;

/**
 * Detects format support at the system level (libraries, binaries, etc.).
 *
 * @since 0.1.0
 */
class FormatSupportDetector {

    /**
     * Processor detector instance.
     *
     * @since 0.1.0
     * @var ProcessorDetector
     */
    private $processor_detector;

    /**
     * Constructor.
     *
     * @since 0.1.0
     * @param ProcessorDetector $processor_detector Processor detector instance.
     */
    public function __construct( ProcessorDetector $processor_detector ) {
        $this->processor_detector = $processor_detector;
    }

    /**
     * Check if WebP format is supported at the system level.
     *
     * @since 0.1.0
     * @return bool True if WebP is supported, false otherwise.
     */
    public function supports_webp() {
        $image_processors = $this->processor_detector->get_available_image_processors();
        
        // Check if any available processor supports WebP
        foreach ( $image_processors as $processor ) {
            if ( $processor['webp_support'] ?? false ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if AVIF format is supported at the system level.
     *
     * @since 0.1.0
     * @return bool True if AVIF is supported, false otherwise.
     */
    public function supports_avif() {
        $image_processors = $this->processor_detector->get_available_image_processors();
        
        // Check if any available processor supports AVIF
        foreach ( $image_processors as $processor ) {
            if ( $processor['avif_support'] ?? false ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if AV1 video format is supported at the system level.
     *
     * @since 0.1.0
     * @return bool True if AV1 is supported, false otherwise.
     */
    public function supports_av1() {
        $video_processors = $this->processor_detector->get_available_video_processors();
        
        // Check if any available processor supports AV1
        foreach ( $video_processors as $processor ) {
            if ( $processor['av1_support'] ?? false ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if WebM video format is supported at the system level.
     *
     * @since 0.1.0
     * @return bool True if WebM is supported, false otherwise.
     */
    public function supports_webm() {
        $video_processors = $this->processor_detector->get_available_video_processors();
        
        // Check if any available processor supports WebM
        foreach ( $video_processors as $processor ) {
            if ( $processor['webm_support'] ?? false ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get detailed format support information.
     *
     * @since 0.1.0
     * @return array Format support details.
     */
    public function get_format_support_info() {
        $image_processors = $this->processor_detector->get_available_image_processors();
        $video_processors = $this->processor_detector->get_available_video_processors();

        return [
            'webp' => [
                'supported' => $this->supports_webp(),
                'gd_support' => $image_processors[ ProcessorTypes::IMAGE_GD ]['webp_support'] ?? false,
                'imagick_support' => $image_processors[ ProcessorTypes::IMAGE_IMAGICK ]['webp_support'] ?? false,
            ],
            'avif' => [
                'supported' => $this->supports_avif(),
                'gd_support' => $image_processors[ ProcessorTypes::IMAGE_GD ]['avif_support'] ?? false,
                'imagick_support' => $image_processors[ ProcessorTypes::IMAGE_IMAGICK ]['avif_support'] ?? false,
            ],
            'av1' => [
                'supported' => $this->supports_av1(),
                'ffmpeg_available' => $this->processor_detector->is_ffmpeg_available(),
                'codec_support' => $video_processors[ ProcessorTypes::VIDEO_FFMPEG ]['av1_support'] ?? false,
            ],
            'webm' => [
                'supported' => $this->supports_webm(),
                'ffmpeg_available' => $this->processor_detector->is_ffmpeg_available(),
                'codec_support' => $video_processors[ ProcessorTypes::VIDEO_FFMPEG ]['webm_support'] ?? false,
            ],
        ];
    }

}
