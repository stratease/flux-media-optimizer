<?php
/**
 * Image conversion service with GD/Imagick wrapper.
 *
 * @package FluxMedia
 * @since 1.0.0
 */

namespace FluxMedia\Services;

use FluxMedia\Utils\Logger;
use FluxMedia\Interfaces\ImageProcessorInterface;
use FluxMedia\Processors\GDProcessor;
use FluxMedia\Processors\ImagickProcessor;

/**
 * Image conversion service that handles WebP and AVIF conversion.
 *
 * @since 1.0.0
 */
class ImageConverter {

	/**
	 * Logger instance.
	 *
	 * @since 1.0.0
	 * @var Logger
	 */
	private $logger;

	/**
	 * Image processor instance.
	 *
	 * @since 1.0.0
	 * @var ImageProcessorInterface
	 */
	private $processor;

	/**
	 * Supported image formats.
	 *
	 * @since 1.0.0
	 * @var array
	 */
	private $supported_formats = [
		'image/jpeg',
		'image/png',
		'image/gif',
		'image/webp',
	];

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 * @param Logger $logger Logger instance.
	 */
	public function __construct( Logger $logger ) {
		$this->logger = $logger;
		$this->processor = $this->get_available_processor();
	}

	/**
	 * Get the available image processor (Imagick or GD).
	 *
	 * @since 1.0.0
	 * @return ImageProcessorInterface|null The processor instance or null if none available.
	 */
	private function get_available_processor() {
		// Prefer Imagick for better quality and more features.
		if ( class_exists( 'Imagick' ) && extension_loaded( 'imagick' ) ) {
			$imagick = new \Imagick();
			$formats = $imagick->queryFormats();
			
			// Check if Imagick supports WebP and AVIF.
			if ( in_array( 'WEBP', $formats, true ) && in_array( 'AVIF', $formats, true ) ) {
				return new ImagickProcessor( $this->logger );
			}
		}

		// Fallback to GD if available.
		if ( extension_loaded( 'gd' ) ) {
			// Check GD version and WebP support.
			$gd_info = gd_info();
			if ( isset( $gd_info['WebP Support'] ) && $gd_info['WebP Support'] ) {
				return new GDProcessor( $this->logger );
			}
		}

		$this->logger->warning( 'No suitable image processor found. Imagick or GD with WebP support required.' );
		return null;
	}

	/**
	 * Check if image conversion is available.
	 *
	 * @since 1.0.0
	 * @return bool True if conversion is available, false otherwise.
	 */
	public function is_available() {
		return null !== $this->processor;
	}

	/**
	 * Get processor information.
	 *
	 * @since 1.0.0
	 * @return array Processor information.
	 */
	public function get_processor_info() {
		if ( ! $this->processor ) {
			return [
				'available' => false,
				'type' => 'none',
				'webp_support' => false,
				'avif_support' => false,
			];
		}

		return $this->processor->get_info();
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
		if ( ! $this->processor ) {
			$this->logger->error( 'No image processor available for WebP conversion' );
			return false;
		}

		$default_options = [
			'quality' => 85,
			'lossless' => false,
		];

		$options = array_merge( $default_options, $options );

		try {
			$result = $this->processor->convert_to_webp( $source_path, $destination_path, $options );
			
			if ( $result ) {
				$this->logger->info( "Successfully converted image to WebP: {$source_path}" );
			} else {
				$this->logger->error( "Failed to convert image to WebP: {$source_path}" );
			}

			return $result;
		} catch ( \Exception $e ) {
			$this->logger->error( "Exception during WebP conversion: {$e->getMessage()}" );
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
		if ( ! $this->processor ) {
			$this->logger->error( 'No image processor available for AVIF conversion' );
			return false;
		}

		$default_options = [
			'quality' => 80,
			'speed' => 6,
		];

		$options = array_merge( $default_options, $options );

		try {
			$result = $this->processor->convert_to_avif( $source_path, $destination_path, $options );
			
			if ( $result ) {
				$this->logger->info( "Successfully converted image to AVIF: {$source_path}" );
			} else {
				$this->logger->error( "Failed to convert image to AVIF: {$source_path}" );
			}

			return $result;
		} catch ( \Exception $e ) {
			$this->logger->error( "Exception during AVIF conversion: {$e->getMessage()}" );
			return false;
		}
	}

	/**
	 * Check if file is a supported image format.
	 *
	 * @since 1.0.0
	 * @param string $file_path File path to check.
	 * @return bool True if supported, false otherwise.
	 */
	public function is_supported_image( $file_path ) {
		$mime_type = wp_check_filetype( $file_path )['type'];
		return in_array( $mime_type, $this->supported_formats, true );
	}

	/**
	 * Get file size reduction percentage.
	 *
	 * @since 1.0.0
	 * @param string $original_path Original file path.
	 * @param string $converted_path Converted file path.
	 * @return float Reduction percentage.
	 */
	public function get_size_reduction( $original_path, $converted_path ) {
		if ( ! file_exists( $original_path ) || ! file_exists( $converted_path ) ) {
			return 0.0;
		}

		$original_size = filesize( $original_path );
		$converted_size = filesize( $converted_path );

		if ( $original_size === 0 ) {
			return 0.0;
		}

		return ( ( $original_size - $converted_size ) / $original_size ) * 100;
	}

	/**
	 * Convert image using hybrid approach (both WebP and AVIF).
	 * Creates both formats for optimal performance and compatibility.
	 *
	 * @since 1.0.0
	 * @param string $source_path Source image path.
	 * @param string $webp_path Destination WebP path.
	 * @param string $avif_path Destination AVIF path.
	 * @param array  $webp_options WebP conversion options.
	 * @param array  $avif_options AVIF conversion options.
	 * @return array Results array with 'webp' and 'avif' keys.
	 */
	public function convert_hybrid( $source_path, $webp_path, $avif_path, $webp_options = [], $avif_options = [] ) {
		$results = [
			'webp' => false,
			'avif' => false,
		];

		// Convert to WebP
		$results['webp'] = $this->convert_to_webp( $source_path, $webp_path, $webp_options );
		
		// Convert to AVIF
		$results['avif'] = $this->convert_to_avif( $source_path, $avif_path, $avif_options );

		// Log hybrid conversion results
		if ( $results['webp'] && $results['avif'] ) {
			$this->logger->info( "Successfully converted image using hybrid approach: {$source_path}" );
		} elseif ( $results['webp'] || $results['avif'] ) {
			$this->logger->warning( "Partial hybrid conversion success: {$source_path} (WebP: " . ( $results['webp'] ? 'success' : 'failed' ) . ", AVIF: " . ( $results['avif'] ? 'success' : 'failed' ) . ")" );
		} else {
			$this->logger->error( "Hybrid conversion failed for both formats: {$source_path}" );
		}

		return $results;
	}
}
