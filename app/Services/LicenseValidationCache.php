<?php
/**
 * License validation cache service.
 *
 * Manages license validation using license_last_valid_date setting with 24-hour TTL.
 * Performs on-demand validation when cache expires.
 *
 * @package FluxMedia\App\Services
 * @since 3.0.0
 */

namespace FluxMedia\App\Services;

/**
 * License validation cache.
 *
 * Caches license validation status using license_last_valid_date setting.
 * Automatically re-validates when cache expires (24 hours).
 *
 * @since 3.0.0
 */
class LicenseValidationCache {

	/**
	 * Logger instance.
	 *
	 * @since 3.0.0
	 * @var LoggerInterface
	 */
	private $logger;

	/**
	 * External API client instance.
	 *
	 * @since 3.0.0
	 * @var ExternalApiClient
	 */
	private $api_client;

	/**
	 * Cache TTL in seconds (24 hours).
	 *
	 * @since 3.0.0
	 * @var int
	 */
	const CACHE_TTL = 86400; // 24 hours in seconds

	/**
	 * Constructor.
	 *
	 * @since 3.0.0
	 * @param LoggerInterface $logger Logger instance.
	 */
	public function __construct( LoggerInterface $logger ) {
		$this->logger = $logger;
		$this->api_client = new ExternalApiClient( $logger );
	}

	/**
	 * Check if license is currently valid.
	 *
	 * Uses license_last_valid_date setting to cache validation status.
	 * If cache is expired (> 24 hours old) or missing, triggers validation.
	 *
	 * @since 3.0.0
	 * @return bool True if license is valid, false otherwise.
	 */
	public function is_license_valid() {
		$license_key = Settings::get_license_key();
		
		if ( empty( $license_key ) ) {
			return false;
		}

		$last_valid_date = Settings::get_license_last_valid_date();
		
		// If no validation date exists, license is invalid.
		if ( empty( $last_valid_date ) ) {
			return false;
		}

		// Check if cache is still valid (less than 24 hours old).
		$last_valid_timestamp = strtotime( $last_valid_date . ' GMT' );
		$current_timestamp = current_time( 'timestamp', true );
		$cache_age = $current_timestamp - $last_valid_timestamp;

		if ( $cache_age < self::CACHE_TTL ) {
			// Cache is still valid, return true.
			return true;
		}

		// Cache expired, re-validate license.
		$this->logger->debug( "License validation cache expired (age: {$cache_age}s), re-validating license" );
		
		return $this->validate_and_update();
	}

	/**
	 * Validate license and update cache.
	 *
	 * Calls ExternalApiClient to validate license and updates license_last_valid_date setting.
	 *
	 * @since 3.0.0
	 * @return bool True if license is valid, false otherwise.
	 */
	private function validate_and_update() {
		$license_key = Settings::get_license_key();
		
		if ( empty( $license_key ) ) {
			Settings::set_license_last_valid_date( null );
			return false;
		}

		$validation_result = $this->api_client->validate_license( $license_key );

		// Update validation date based on result.
		if ( $validation_result['success'] && isset( $validation_result['valid'] ) && $validation_result['valid'] ) {
			// Validation successful and license is valid - save date.
			Settings::set_license_last_valid_date( current_time( 'mysql', true ) );
			return true;
		} else {
			// Validation failed or license is invalid - clear date.
			Settings::set_license_last_valid_date( null );
			return false;
		}
	}
}

