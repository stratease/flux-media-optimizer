<?php
/**
 * Media processing service locator.
 *
 * Central service locator that routes media processing requests to either
 * local or external processing services based on settings and license validity.
 *
 * @package FluxMedia\App\Services
 * @since 3.0.0
 */

namespace FluxMedia\App\Services;

/**
 * Media processing service locator.
 *
 * Provides the appropriate processing service based on configuration.
 *
 * @since 3.0.0
 */
class MediaProcessingServiceLocator {

	/**
	 * License validation cache instance.
	 *
	 * @since 3.0.0
	 * @var LicenseValidationCache
	 */
	private $license_cache;

	/**
	 * Local processing service instance.
	 *
	 * @since 3.0.0
	 * @var LocalProcessingService|null
	 */
	private $local_service;

	/**
	 * External processing service instance.
	 *
	 * @since 3.0.0
	 * @var ExternalProcessingService|null
	 */
	private $external_service;

	/**
	 * External optimization provider instance.
	 *
	 * @since 3.0.0
	 * @var ExternalOptimizationProvider|null
	 */
	private $external_provider;

	/**
	 * Logger instance.
	 *
	 * @since 3.0.0
	 * @var LoggerInterface
	 */
	private $logger;

	/**
	 * Image converter instance.
	 *
	 * @since 3.0.0
	 * @var ImageConverter
	 */
	private $image_converter;

	/**
	 * Video converter instance.
	 *
	 * @since 3.0.0
	 * @var VideoConverter
	 */
	private $video_converter;

	/**
	 * Conversion tracker instance.
	 *
	 * @since 3.0.0
	 * @var ConversionTracker
	 */
	private $conversion_tracker;

	/**
	 * Bulk converter instance.
	 *
	 * @since 3.0.0
	 * @var BulkConverter
	 */
	private $bulk_converter;

	/**
	 * WordPress provider instance.
	 *
	 * @since 3.0.0
	 * @var WordPressProvider
	 */
	private $wordpress_provider;

	/**
	 * Constructor.
	 *
	 * @since 3.0.0
	 * @param LicenseValidationCache $license_cache License validation cache.
	 * @param ImageConverter         $image_converter Image converter service.
	 * @param VideoConverter         $video_converter Video converter service.
	 * @param ConversionTracker      $conversion_tracker Conversion tracker service.
	 * @param BulkConverter          $bulk_converter Bulk converter service.
	 * @param LoggerInterface        $logger Logger instance.
	 * @param WordPressProvider       $wordpress_provider WordPress provider instance.
	 */
	public function __construct(
		LicenseValidationCache $license_cache,
		ImageConverter $image_converter,
		VideoConverter $video_converter,
		ConversionTracker $conversion_tracker,
		BulkConverter $bulk_converter,
		LoggerInterface $logger,
		WordPressProvider $wordpress_provider
	) {
		$this->license_cache = $license_cache;
		$this->image_converter = $image_converter;
		$this->video_converter = $video_converter;
		$this->conversion_tracker = $conversion_tracker;
		$this->bulk_converter = $bulk_converter;
		$this->logger = $logger;
		$this->wordpress_provider = $wordpress_provider;
	}

	/**
	 * Initialize the service locator.
	 *
	 * Initializes external provider if external service is enabled.
	 *
	 * @since 3.0.0
	 * @return void
	 */
	public function init() {
		// Initialize external provider if external service is enabled and license is valid.
		if ( Settings::is_external_service_enabled() && $this->license_cache->is_license_valid() ) {
			$this->external_provider = new ExternalOptimizationProvider( $this->logger );
			$this->external_provider->init();
		}
	}

	/**
	 * Get the appropriate processing service.
	 *
	 * Returns ExternalProcessingService if external service is enabled and license is valid,
	 * otherwise returns LocalProcessingService.
	 *
	 * @since 3.0.0
	 * @return ProcessingServiceInterface Processing service instance.
	 */
	public function get_processor() {
		// Check if external service is enabled and license is valid.
		if ( Settings::is_external_service_enabled() && $this->license_cache->is_license_valid() ) {
			return $this->get_external_service();
		}

		return $this->get_local_service();
	}

	/**
	 * Get local processing service instance.
	 *
	 * @since 3.0.0
	 * @return LocalProcessingService Local processing service.
	 */
	private function get_local_service() {
		if ( null === $this->local_service ) {
			$this->local_service = new LocalProcessingService(
				$this->image_converter,
				$this->video_converter,
				$this->conversion_tracker,
				$this->bulk_converter,
				$this->logger,
				$this->wordpress_provider
			);
		}

		return $this->local_service;
	}

	/**
	 * Get external processing service instance.
	 *
	 * @since 3.0.0
	 * @return ExternalProcessingService External processing service.
	 */
	private function get_external_service() {
		if ( null === $this->external_service ) {
			// Ensure external provider is initialized.
			if ( null === $this->external_provider ) {
				$this->external_provider = new ExternalOptimizationProvider( $this->logger );
				$this->external_provider->init();
			}

			$this->external_service = new ExternalProcessingService( $this->external_provider );
		}

		return $this->external_service;
	}
}

