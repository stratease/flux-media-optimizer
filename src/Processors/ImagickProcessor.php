<?php
/**
 * Imagick image processor implementation.
 *
 * @package FluxMedia
 * @since 1.0.0
 */

namespace FluxMedia\Processors;

use FluxMedia\Interfaces\ImageProcessorInterface;
use FluxMedia\Utils\Logger;

/**
 * Imagick-based image processor with high-quality conversion settings.
 *
 * @since 1.0.0
 */
class ImagickProcessor implements ImageProcessorInterface {

	/**
	 * Logger instance.
	 *
	 * @since 1.0.0
	 * @var Logger
	 */
	private $logger;

	/**
	 * Imagick instance.
	 *
	 * @since 1.0.0
	 * @var \Imagick
	 */
	private $imagick;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 * @param Logger $logger Logger instance.
	 */
	public function __construct( Logger $logger ) {
		$this->logger = $logger;
		$this->imagick = new \Imagick();
	}

	/**
	 * Get processor information.
	 *
	 * @since 1.0.0
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
	 * @since 1.0.0
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
			$image = new \Imagick( $source_path );
			
			// Set high-quality WebP options.
			$image->setImageFormat( 'WEBP' );
			$image->setImageCompressionQuality( $options['quality'] ?? 85 );
			
			// Enable lossless compression if requested.
			if ( $options['lossless'] ?? false ) {
				$image->setOption( 'webp:lossless', 'true' );
			} else {
				// Use advanced WebP options for better compression.
				$image->setOption( 'webp:method', '6' ); // Best compression method.
				$image->setOption( 'webp:pass', '10' ); // Maximum number of entropy-analysis passes.
				$image->setOption( 'webp:preprocessing', '2' ); // Preprocessing filter.
			}

			// Strip metadata for smaller file size.
			$image->stripImage();

			// Write the converted image.
			$result = $image->writeImage( $destination_path );
			$image->clear();
			$image->destroy();

			return $result;
		} catch ( \ImagickException $e ) {
			$this->logger->error( "Imagick WebP conversion failed: {$e->getMessage()}" );
			return false;
		}
	}

	/**
	 * Convert image to AVIF format.
	 *
	 * @since 1.0.0
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
			$image = new \Imagick( $source_path );
			
			// Set high-quality AVIF options.
			$image->setImageFormat( 'AVIF' );
			$image->setImageCompressionQuality( $options['quality'] ?? 80 );
			
			// Set AVIF-specific options for optimal quality.
			$image->setOption( 'avif:speed', (string) ( $options['speed'] ?? 6 ) );
			$image->setOption( 'avif:crf', (string) ( $options['quality'] ?? 80 ) );
			
			// Use advanced AVIF settings.
			$image->setOption( 'avif:colorprim', 'bt709' ); // Color primaries.
			$image->setOption( 'avif:transfer', 'bt709' ); // Transfer characteristics.
			$image->setOption( 'avif:colormatrix', 'bt709' ); // Color matrix.

			// Strip metadata for smaller file size.
			$image->stripImage();

			// Write the converted image.
			$result = $image->writeImage( $destination_path );
			$image->clear();
			$image->destroy();

			return $result;
		} catch ( \ImagickException $e ) {
			$this->logger->error( "Imagick AVIF conversion failed: {$e->getMessage()}" );
			return false;
		}
	}

	/**
	 * Check if processor supports WebP.
	 *
	 * @since 1.0.0
	 * @return bool True if WebP is supported, false otherwise.
	 */
	public function supports_webp() {
		$formats = $this->imagick->queryFormats();
		return in_array( 'WEBP', $formats, true );
	}

	/**
	 * Check if processor supports AVIF.
	 *
	 * @since 1.0.0
	 * @return bool True if AVIF is supported, false otherwise.
	 */
	public function supports_avif() {
		$formats = $this->imagick->queryFormats();
		return in_array( 'AVIF', $formats, true );
	}
}
