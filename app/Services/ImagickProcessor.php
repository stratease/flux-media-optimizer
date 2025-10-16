<?php
/**
 * Imagick image processor implementation.
 *
 * @package FluxMedia
 * @since 0.1.0
 */

namespace FluxMedia\App\Services;

use FluxMedia\App\Services\ImageProcessorInterface;
use FluxMedia\App\Services\LoggerInterface;
use Imagick;
use ImagickException;

/**
 * Imagick-based image processor with high-quality conversion settings.
 *
 * @since 0.1.0
 */
class ImagickProcessor implements ImageProcessorInterface {

	/**
	 * Logger instance.
	 *
	 * @since 0.1.0
	 * @var LoggerInterface
	 */
	private $logger;

	/**
	 * Imagick instance.
	 *
	 * @since 0.1.0
	 * @var Imagick
	 */
	private $imagick;

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 * @param LoggerInterface $logger Logger instance.
	 */
	public function __construct( LoggerInterface $logger ) {
		$this->logger = $logger;
		$this->imagick = new Imagick();
	}

	/**
	 * Get processor information.
	 *
	 * @since 0.1.0
	 * @return array Processor information.
	 */
	public function get_info() {
		$formats = $this->imagick->queryFormats();
		
		return [
			'available' => true,
			'type' => 'imagick',
			'version' => $this->imagick->getVersion()['versionString'],
			'webp_support' => in_array( 'WEBP', $formats, true ),
			'avif_support' => in_array( 'AVIF', $formats, true ),
			'supported_formats' => $formats,
		];
	}

	/**
	 * Convert image to WebP format.
	 *
	 * @since 0.1.0
	 * @param string $source_path Source image path.
	 * @param string $destination_path Destination path.
	 * @param array  $options Conversion options.
	 * @return bool True on success, false on failure.
	 */
	public function convert_to_webp( $source_path, $destination_path, $options = [] ) {
		if ( ! $this->supports_webp() ) {
			$this->logger->error( 'Imagick does not support WebP format' );
			return false;
		}

		try {
			$image = new Imagick( $source_path );
			
			// Set optimized WebP options for better compression.
			$image->setImageFormat( 'WEBP' );
			$image->setImageCompressionQuality( $options['webp_quality'] );
			
			// Enable lossless compression if requested.
			if ( $options['lossless'] ?? false ) {
				$image->setOption( 'webp:lossless', 'true' );
			} else {
				// Use optimized WebP options for better compression and smaller file sizes.
				$image->setOption( 'webp:method', '4' ); // Balanced compression method (was 6).
				$image->setOption( 'webp:pass', '6' ); // Fewer passes for faster/smaller files (was 10).
				$image->setOption( 'webp:preprocessing', '1' ); // Less aggressive preprocessing (was 2).
			}

			// Strip metadata for smaller file size.
			$image->stripImage();

			// Write the converted image.
			$result = $image->writeImage( $destination_path );
			$image->clear();
			$image->destroy();

			// Check if writeImage actually succeeded
			if ( $result === false ) {
				$this->logger->error( "Imagick writeImage() failed for WebP conversion to: {$destination_path}" );
				return false;
			}

			// Verify the file was actually created
			if ( ! file_exists( $destination_path ) ) {
				$this->logger->error( "WebP file was not created at: {$destination_path}" );
				return false;
			}

			return $result;
		} catch ( ImagickException $e ) {
			$this->logger->error( "Imagick WebP conversion failed: {$e->getMessage()}" );
			return false;
		}
	}

	/**
	 * Convert image to AVIF format.
	 *
	 * @since 0.1.0
	 * @param string $source_path Source image path.
	 * @param string $destination_path Destination path.
	 * @param array  $options Conversion options.
	 * @return bool True on success, false on failure.
	 */
	public function convert_to_avif( $source_path, $destination_path, $options = [] ) {
		if ( ! $this->supports_avif() ) {
			$this->logger->error( 'Imagick does not support AVIF format' );
			return false;
		}

		try {
			$image = new Imagick( $source_path );
			
			// Set high-quality AVIF options.
			$image->setImageFormat( 'AVIF' );
			
			// Set AVIF-specific options for optimal quality.
			$image->setOption( 'avif:speed', (string) ( $options['avif_speed'] ) );
			
			// Get quality value from raw settings
			$quality = $options['avif_quality'];
			
			// Try to use avif:crf for better quality control (ImageMagick 7+)
			// Convert quality (0-100) to CRF (0-63) for more precise control
			$crf_value = (int) ( 63 - ( $quality * 0.63 ) );
			$crf_success = $image->setOption( 'avif:crf', (string) $crf_value );
			
			if ( $crf_success ) {
				// avif:crf is supported, use it for precise quality control
				$this->logger->debug( "Using avif:crf={$crf_value} for quality control" );
			} else {
				// Fallback to setImageCompressionQuality for older ImageMagick versions
				$image->setImageCompressionQuality( $quality );
				$this->logger->debug( "avif:crf not supported, using setImageCompressionQuality={$quality}" );
			}
			
			// Adaptive color settings (optional, based on image color space)
			$colorprim = ( $image->getImageColorspace() === Imagick::COLORSPACE_SRGB ) ? 'bt709' : 'bt2020';
			$image->setOption( 'avif:colorprim', $colorprim );
			$image->setOption( 'avif:transfer', $colorprim );
			$image->setOption( 'avif:colormatrix', $colorprim );
			// Strip metadata for smaller file size.
			$image->stripImage();

			// Write the converted image.
			$result = $image->writeImage( $destination_path );
			$image->clear();
			$image->destroy();

			// Check if writeImage actually succeeded
			if ( $result === false ) {
				$this->logger->error( "Imagick writeImage() failed for AVIF conversion to: {$destination_path}" );
				return false;
			}

			// Verify the file was actually created
			if ( ! file_exists( $destination_path ) ) {
				$this->logger->error( "AVIF file was not created at: {$destination_path}" );
				return false;
			}

			return $result;
		} catch ( ImagickException $e ) {
			$this->logger->error( "Imagick AVIF conversion failed: {$e->getMessage()}" );
			return false;
		}
	}

	/**
	 * Check if processor supports WebP.
	 *
	 * @since 0.1.0
	 * @return bool True if WebP is supported, false otherwise.
	 */
	public function supports_webp() {
		$formats = $this->imagick->queryFormats();
		return in_array( 'WEBP', $formats, true );
	}

	/**
	 * Check if processor supports AVIF.
	 *
	 * @since 0.1.0
	 * @return bool True if AVIF is supported, false otherwise.
	 */
	public function supports_avif() {
		$formats = $this->imagick->queryFormats();
		return in_array( 'AVIF', $formats, true );
	}
}
