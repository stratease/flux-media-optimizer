<?php
/**
 * AV1 video format for PHP-FFmpeg.
 *
 * Custom format class to support AV1 encoding with libaom-av1 codec.
 *
 * @package FluxMedia
 * @since 3.0.0
 */

namespace FluxMedia\App\Services;

use FluxMedia\FFMpeg\Format\Video\DefaultVideo;

/**
 * AV1 video format class.
 *
 * Extends DefaultVideo to support AV1 encoding with libaom-av1 codec.
 *
 * @since 3.0.0
 */
class AV1Format extends DefaultVideo {

	/**
	 * Constructor.
	 *
	 * @since 3.0.0
	 * @param string $audio_codec Audio codec (default: libopus).
	 * @param string $video_codec Video codec (default: libaom-av1).
	 */
	public function __construct( $audio_codec = 'libopus', $video_codec = 'libaom-av1' ) {
		$this->setAudioCodec( $audio_codec );
		$this->setVideoCodec( $video_codec );
	}

	/**
	 * Get available audio codecs.
	 *
	 * @since 3.0.0
	 * @return array Array of available audio codecs.
	 */
	public function getAvailableAudioCodecs() {
		return [ 'copy', 'libopus', 'aac', 'libvo_aacenc', 'libfaac', 'libmp3lame', 'libfdk_aac' ];
	}

	/**
	 * Get available video codecs.
	 *
	 * @since 3.0.0
	 * @return array Array of available video codecs.
	 */
	public function getAvailableVideoCodecs() {
		return [ 'libaom-av1', 'libsvtav1' ];
	}

	/**
	 * Get modulus value.
	 *
	 * @since 3.0.0
	 * @return int Modulus value.
	 */
	public function getModulus() {
		return 2;
	}

	/**
	 * Get number of passes.
	 *
	 * @since 3.0.0
	 * @return int Number of passes (AV1 typically uses 1 pass with CRF).
	 */
	public function getPasses() {
		return 1;
	}

	/**
	 * Check if B-frames are supported.
	 *
	 * @since 3.0.0
	 * @return bool True if B-frames are supported, false otherwise.
	 */
	public function supportBFrames() {
		return true;
	}
}

