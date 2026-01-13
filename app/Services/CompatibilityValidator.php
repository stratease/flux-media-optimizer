<?php
/**
 * Compatibility validation service for Flux Media Optimizer plugin.
 *
 * Validates compatibility between the WordPress plugin and the remote Flux Media API service.
 * This is independent of license validation and focuses solely on version compatibility.
 *
 * @package FluxMedia
 * @since 3.0.0
 */

namespace FluxMedia\App\Services;

use FluxMedia\FluxPlugins\Common\Logger\Logger;
use FluxMedia\App\Services\CompatibilityResponse;
use FluxMedia\App\Services\ExternalApiClient;

/**
 * Compatibility validator class.
 *
 * @since 3.0.0
 */
class CompatibilityValidator {

	/**
	 * Singleton instance.
	 *
	 * @since 3.0.0
	 * @var CompatibilityValidator|null
	 */
	private static $instance = null;

	/**
	 * Logger instance.
	 *
	 * @since 3.0.0
	 * @var Logger
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
	 * Cache option name for compatibility results.
	 *
	 * @since 3.0.0
	 * @var string
	 */
	private $cache_option_name = 'flux_media_optimizer_compatibility_cache';

	/**
	 * Default cache TTL in seconds (4-6 hours as specified).
	 *
	 * @since 3.0.0
	 * @var int
	 */
	private $default_cache_ttl = 14400; // 4 hours

	/**
	 * Plugin identifier for API requests.
	 *
	 * @since 3.0.0
	 * @var string
	 */
	private $plugin_identifier;

	/**
	 * Constructor.
	 *
	 * Initializes dependencies internally to avoid dependency injection complexity.
	 *
	 * @since 3.0.0
	 */
	private function __construct() {
		// Initialize logger from common library.
		$this->logger = \FluxMedia\FluxPlugins\Common\Logger\Logger::get_instance();
		
		// Initialize external API client internally.
		$this->api_client = new ExternalApiClient( $this->logger );
		
		$this->plugin_identifier = FLUX_MEDIA_OPTIMIZER_PLUGIN_SLUG;
	}

	/**
	 * Get singleton instance of CompatibilityValidator.
	 *
	 * Factory method that creates and returns the singleton instance.
	 * Dependencies are initialized internally, so no parameters are needed.
	 *
	 * @since 3.0.0
	 * @return CompatibilityValidator Singleton instance.
	 */
	public static function get_instance() {
		if ( self::$instance === null ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Reset singleton instance (for testing purposes).
	 *
	 * @since 3.0.0
	 * @return void
	 */
	public static function reset_instance() {
		self::$instance = null;
	}

	/**
	 * Check compatibility with the remote API service.
	 *
	 * Uses cached result if available and valid, otherwise fetches from API.
	 *
	 * @since 3.0.0
	 * @param bool $force_refresh Force refresh of cached result.
	 * @return CompatibilityResponse Compatibility response object.
	 */
	public function check_compatibility( $force_refresh = false ) {
		// Check if cache is disabled via constant.
		$cache_disabled = defined( 'FLUX_PLUGINS_COMMON_DISABLE_CACHE' ) && FLUX_PLUGINS_COMMON_DISABLE_CACHE;

		// Check cache first unless forced refresh or cache is disabled.
		if ( ! $force_refresh && ! $cache_disabled ) {
			$cached_result = $this->get_cached_result();
			if ( $cached_result !== null ) {
				$this->logger->debug( 'Using cached compatibility result' );
				return $cached_result;
			}
		}

		// Fetch fresh result from API.
		$result = $this->fetch_compatibility_from_api();

		// Cache the result if valid and cache is not disabled.
		if ( $result !== null ) {
			$this->cache_result( $result );
		} elseif ( $result === null ) {
			// If API is unreachable, try to use stale cache.
			$stale_cache = $this->get_cached_result( true );
			if ( $stale_cache !== null ) {
				$this->logger->debug( 'API unreachable, using stale cached compatibility result' );
				return $stale_cache;
			}
		}

		// If no cache and API failed, return a safe default.
		if ( $result === null ) {
			$this->logger->warning( 'Compatibility check failed and no cache available, returning safe default' );
			return $this->get_safe_default_result();
		}

		return $result;
	}

	/**
	 * Fetch compatibility result from the API.
	 *
	 * @since 3.0.0
	 * @return CompatibilityResponse|null Compatibility response object or null on failure.
	 */
	private function fetch_compatibility_from_api() {
		$plugin_version = FLUX_MEDIA_OPTIMIZER_VERSION;

		if ( empty( $plugin_version ) ) {
			$this->logger->error( 'Plugin version not defined, cannot check compatibility' );
			return null;
		}

		$result = $this->api_client->check_compatibility( $this->plugin_identifier, $plugin_version );

		// Handle error responses (array format).
		if ( is_array( $result ) && ( ! isset( $result['success'] ) || ! $result['success'] ) ) {
			$error = isset( $result['error'] ) ? $result['error'] : 'Unknown error';
			$this->logger->warning( "Compatibility check API call failed: {$error}" );
			return null;
		}

		// Handle CompatibilityResponse object.
		if ( $result instanceof CompatibilityResponse ) {
			if ( ! $result->is_success() ) {
				$this->logger->warning( 'Compatibility check API call returned unsuccessful response' );
				return null;
			}
			return $result;
		}

		// Unexpected response format.
		$this->logger->warning( 'Compatibility check API call returned unexpected response format' );
		return null;
	}

	/**
	 * Get cached compatibility result.
	 *
	 * @since 3.0.0
	 * @param bool $include_stale Include stale cache (expired but still available).
	 * @return CompatibilityResponse|null Cached result or null if not available/expired.
	 */
	private function get_cached_result( $include_stale = false ) {
		$cache_data = get_site_option( $this->cache_option_name, null );

		if ( $cache_data === null || ! is_array( $cache_data ) ) {
			return null;
		}

		// Check if cache is expired.
		$expires_at = isset( $cache_data['expires_at'] ) ? (int) $cache_data['expires_at'] : 0;
		$now        = time();

		if ( $expires_at > 0 && $now > $expires_at ) {
			if ( ! $include_stale ) {
				return null;
			}
		}

		// Reconstruct CompatibilityResponse from cached data.
		if ( isset( $cache_data['result'] ) && is_array( $cache_data['result'] ) ) {
			return new CompatibilityResponse( $cache_data['result'] );
		}

		return null;
	}

	/**
	 * Cache compatibility result.
	 *
	 * @since 3.0.0
	 * @param CompatibilityResponse $result Compatibility result to cache.
	 * @return void
	 */
	private function cache_result( CompatibilityResponse $result ) {
		$ttl = $result->get_cache_ttl_seconds();
		if ( $ttl <= 0 ) {
			$ttl = $this->default_cache_ttl;
		}
		$expires_at = time() + $ttl;

		$cache_data = [
			'result'     => $result->to_array(),
			'expires_at' => $expires_at,
			'cached_at'  => time(),
		];

		update_site_option( $this->cache_option_name, $cache_data );
		$this->logger->debug( "Cached compatibility result, expires in {$ttl} seconds" );
	}

	/**
	 * Get safe default result when API is unreachable and no cache exists.
	 *
	 * @since 3.0.0
	 * @return CompatibilityResponse Safe default compatibility result.
	 */
	private function get_safe_default_result() {
		$default_data = [
			'success' => true,
			'compatibility_responses' => [],
		];

		return new CompatibilityResponse( $default_data );
	}

	/**
	 * Check if operations should be blocked.
	 *
	 * This is the single source of truth for checking if operations should be blocked.
	 * Uses cached data if available (including stale cache), otherwise returns false (allow operations).
	 * This method never triggers API calls - use check_compatibility() if fresh data is needed.
	 *
	 * @since 3.0.0
	 * @return bool True if operations should be blocked, false otherwise.
	 */
	public function should_block_operations() {
		// Only check cached data - don't trigger API calls here.
		$result = $this->get_cached_result( true ); // Include stale cache.

		// If no cache, assume operations are allowed (fail open).
		if ( $result === null ) {
			return false;
		}

		// Use internal method to check if any items are disabled.
		return $result->has_disabled_items();
	}

	/**
	 * Get all notice data for admin display.
	 *
	 * Returns all compatibility response items that have messages to display.
	 * This method only uses cached data and never makes API calls.
	 *
	 * @since 3.0.0
	 * @return array Array of notice data arrays, or empty array if no notices.
	 */
	public function get_notices() {
		// Only use cached data - never make API calls from here.
		$result = $this->get_cached_result( true ); // Include stale cache for display.

		// If no cache available, return empty array (no notices to show).
		if ( $result === null ) {
			return [];
		}

		if ( ! $result->has_responses() ) {
			return [];
		}

		$notices = [];
		foreach ( $result->get_compatibility_responses() as $response_item ) {
			// Only include items that have a message.
			if ( ! empty( $response_item->get_message() ) ) {
				$notices[] = [
					'type'       => $response_item->get_notice_type(),
					'message'    => $response_item->get_message(),
					'code'       => $response_item->get_error_code(),
					'action'     => $response_item->get_action(),
					// Include both error_code and message for hashing in notice handler.
					'error_code' => $response_item->get_error_code(),
				];
			}
		}

		return $notices;
	}

	/**
	 * Clear compatibility cache.
	 *
	 * @since 3.0.0
	 * @return void
	 */
	public function clear_cache() {
		delete_site_option( $this->cache_option_name );
		$this->logger->debug( 'Compatibility cache cleared' );
	}

	/**
	 * Invalidate cache when plugin version changes.
	 *
	 * Should be called when plugin is updated.
	 *
	 * @since 3.0.0
	 * @return void
	 */
	public function invalidate_on_version_change() {
		$cached_version = get_site_option( 'flux_media_optimizer_compatibility_version', '' );
		$current_version = defined( 'FLUX_MEDIA_OPTIMIZER_VERSION' ) ? FLUX_MEDIA_OPTIMIZER_VERSION : '';

		if ( $cached_version !== $current_version ) {
			$this->clear_cache();
			update_site_option( 'flux_media_optimizer_compatibility_version', $current_version );
			$this->logger->debug( "Plugin version changed from {$cached_version} to {$current_version}, cleared compatibility cache" );
		}
	}
}

