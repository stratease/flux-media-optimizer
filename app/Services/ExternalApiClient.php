<?php
/**
 * External API client for Flux Media Optimizer external service.
 *
 * @package FluxMedia
 * @since 3.0.0
 */

namespace FluxMedia\App\Services;

use FluxMedia\App\Http\Controllers\WebhookController;
use FluxMedia\App\Services\CompatibilityResponse;
use FluxMedia\App\Services\CompatibilityValidator;

/**
 * Handles communication with external CDN and processing service.
 *
 * @since 3.0.0
 */
class ExternalApiClient {

	/**
	 * Logger instance.
	 *
	 * @since 3.0.0
	 * @var LoggerInterface
	 */
	private $logger;

	/**
	 * External service base URL.
	 *
	 * @since 3.0.0
	 * @var string
	 */
	private $base_url;

	/**
	 * Constructor.
	 *
	 * @since 3.0.0
	 * @param LoggerInterface $logger Logger instance.
	 */
	public function __construct( LoggerInterface $logger ) {
		$this->logger = $logger;
		$this->base_url = FLUX_MEDIA_OPTIMIZER_EXTERNAL_SERVICE_URL;
	}

	/**
	 * Check compatibility before making external API request.
	 *
	 * Ensures we have fresh compatibility data before operations. Uses cache if valid,
	 * otherwise fetches from API and caches the result. Then checks if operations should be blocked.
	 *
	 * @since 3.0.0
	 * @return bool True if compatible and can proceed, false if blocked.
	 */
	private function check_compatibility_before_request() {
		// Get singleton instance of compatibility validator.
		// The validator initializes its dependencies internally, so this always returns an instance.
		$validator = CompatibilityValidator::get_instance();

		// Ensure we have fresh compatibility data (uses cache if valid, fetches if needed).
		// This ensures we have up-to-date compatibility info before operations.
		$validator->check_compatibility();

		// Check if operations should be blocked using the cached/fresh data.
		if ( $validator->should_block_operations() ) {
			$this->logger->warning( 'External API request blocked: Compatibility check indicates operations are disabled' );
			return false;
		}

		return true;
	}

	/**
	 * Submit a job to the external service.
	 *
	 * @since 3.0.0
	 * @param int    $attachment_id Attachment ID.
	 * @param array  $operations    Array of operations to perform.
	 * @param string $mimetype     MIME type of the file.
	 * @return array Response array with 'success', 'status', or 'error'.
	 */
	public function submit_job( $attachment_id, $operations = [], $mimetype = '' ) {
		// Check compatibility before making API request.
		if ( ! $this->check_compatibility_before_request() ) {
			$this->logger->warning( "Job submission blocked for attachment {$attachment_id}: Compatibility check failed" );
			return [
				'success' => false,
				'error' => 'Compatibility check failed. Please update the plugin or check compatibility status.',
			];
		}

		$account_id = Settings::get_account_id();

		if ( empty( $account_id ) ) {
			return [
				'success' => false,
				'error' => 'Account ID not found',
			];
		}

		// Get original file URL from attachment ID (not CDN URL).
		// Use get_attached_file() to get the file path, then convert to URL.
		// This bypasses any wp_get_attachment_url filters that might return CDN URLs.
		$file_path = get_attached_file( $attachment_id );
		if ( ! $file_path || ! file_exists( $file_path ) ) {
			return [
				'success' => false,
				'error' => 'Could not get attachment file path',
			];
		}
		
		// Convert file path to URL using WordPress upload directory
		$upload_dir = wp_upload_dir();
		$base_dir = $upload_dir['basedir'];
		$base_url = $upload_dir['baseurl'];
		
		// Replace the base directory path with the base URL
		if ( strpos( $file_path, $base_dir ) === 0 ) {
			$pull_file_url = str_replace( $base_dir, $base_url, $file_path );
			// Normalize path separators for URLs
			$pull_file_url = str_replace( '\\', '/', $pull_file_url );
		} else {
			// Fallback: try wp_get_attachment_url but this might return CDN URL
			$pull_file_url = wp_get_attachment_url( $attachment_id );
		}
		
		if ( ! $pull_file_url ) {
			return [
				'success' => false,
				'error' => 'Could not get attachment URL',
			];
		}

		// Generate webhook URL.
		$webhook_url = WebhookController::get_webhook_url();

		// Parse FLUX_MEDIA_OPTIMIZER_PULL_FILE_URL_DOMAIN for dev testing purposes into both pull_file_url and webhook_url for consistent integration domain.
		if ( defined( 'FLUX_MEDIA_OPTIMIZER_PULL_FILE_URL_DOMAIN' ) ) {
			$parsed_url = wp_parse_url( $pull_file_url );
			if ( $parsed_url && isset( $parsed_url['path'] ) ) {
				$new_domain = rtrim( FLUX_MEDIA_OPTIMIZER_PULL_FILE_URL_DOMAIN, '/' );
				$path = $parsed_url['path'];
				$query = isset( $parsed_url['query'] ) ? '?' . $parsed_url['query'] : '';
				$pull_file_url = $new_domain . $path . $query;
				// Parse webhook url domain.
				$parsed_webhook = wp_parse_url( $webhook_url );
				if ( $parsed_webhook && isset( $parsed_webhook['path'] ) ) {
					$webhook_path = $parsed_webhook['path'];
					$webhook_query = isset( $parsed_webhook['query'] ) ? '?' . $parsed_webhook['query'] : '';
					$webhook_url = $new_domain . $webhook_path . $webhook_query;
				}
			}
		}

		$endpoint = trailingslashit( $this->base_url ) . 'api/v1/' . FLUX_MEDIA_OPTIMIZER_API_NAMESPACE . '/upload/init';
		
		$body = [
			'account_id'     => $account_id,
			'attachment_id'  => (string) $attachment_id,
			'pull_file_url'  => esc_url_raw( $pull_file_url ),
			'webhook_url'    => esc_url_raw( $webhook_url ),
			'mimetype'       => sanitize_text_field( $mimetype ),
			'operations'     => $operations,
		];

		$response = wp_remote_post( $endpoint, [
			'timeout' => FLUX_MEDIA_OPTIMIZER_EXTERNAL_SERVICE_TIMEOUT,
			'headers' => [
				'Content-Type' => 'application/json',
			],
			'body' => wp_json_encode( $body ),
		] );

		if ( is_wp_error( $response ) ) {
			$error_message = $response->get_error_message();
			$this->logger->error( "Failed to submit job to external service: {$error_message}" );
			return [
				'success' => false,
				'error' => $error_message,
			];
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( $status_code !== 200 ) {
			$error = isset( $data['error'] ) ? $data['error'] : 'Unknown error from external service';
			$this->logger->error( "External service returned error: {$error} (Status: {$status_code})" );
			return [
				'success' => false,
				'error' => $error,
			];
		}

		if ( isset( $data['error'] ) ) {
			$this->logger->error( "External service error: {$data['error']}" );
			return [
				'success' => false,
				'error' => $data['error'],
			];
		}

		if ( ! isset( $data['status'] ) ) {
			$this->logger->error( "Invalid response from external service: " . wp_json_encode( $data ) );
			return [
				'success' => false,
				'error' => 'Invalid response from external service',
			];
		}

		$this->logger->debug( "Job submitted successfully for attachment {$attachment_id}" );

		return [
			'success' => true,
			'status' => sanitize_text_field( $data['status'] ),
		];
	}


	/**
	 * Activate license key with external service.
	 *
	 * This is the activation request that sends license_key, account_id, website domain, and plugin version.
	 * Should be called when license_key changes or is initially set.
	 * All subsequent requests use only account_id.
	 *
	 * @since 3.0.0
	 * @param string $license_key License key to activate.
	 * @return array Response array with 'success', 'valid', 'error', and 'message'.
	 */
	public function activate_license( $license_key ) {
		// Check compatibility before making API request.
		if ( ! $this->check_compatibility_before_request() ) {
			$this->logger->warning( 'License activation blocked: Compatibility check failed' );
			return [
				'success' => false,
				'error' => 'compatibility_check_failed',
				'message' => 'Compatibility check failed. Please update the plugin or check compatibility status.',
			];
		}

		$account_id = Settings::get_account_id();
		
		if ( empty( $account_id ) ) {
			$this->logger->error( "License activation failed: Account ID not found" );
			return [
				'success' => false,
				'error' => 'account_id_required',
				'message' => 'Account ID not found',
			];
		}

		// Get website domain - use full URL as the endpoint expects a URL format.
		$website_domain = home_url();
		if ( empty( $website_domain ) ) {
			$protocol = is_ssl() ? 'https://' : 'http://';
			$website_domain = $protocol . ( $_SERVER['HTTP_HOST'] ?? 'localhost' );
		}

		// Get plugin version.
		$plugin_version = defined( 'FLUX_MEDIA_OPTIMIZER_VERSION' ) ? FLUX_MEDIA_OPTIMIZER_VERSION : '';

		$endpoint = trailingslashit( $this->base_url ) . 'api/v1/licenses/activate';
		
		$request_body = [
			'license_key' => sanitize_text_field( $license_key ),
			'account_id'  => $account_id,
			'domain'      => esc_url_raw( $website_domain ),
		];

		// Include plugin_version if available.
		if ( ! empty( $plugin_version ) ) {
			$request_body['plugin_version'] = sanitize_text_field( $plugin_version );
		}

		$this->logger->debug( "Activating license for account {$account_id}, domain: {$website_domain}" );
		
		$response = wp_remote_post( $endpoint, [
			'timeout' => FLUX_MEDIA_OPTIMIZER_EXTERNAL_SERVICE_TIMEOUT,
			'headers' => [
				'Content-Type' => 'application/json',
			],
			'body' => wp_json_encode( $request_body ),
		] );

		if ( is_wp_error( $response ) ) {
			$error_message = $response->get_error_message();
			$this->logger->error( "License activation network error: {$error_message}" );
			return [
				'success' => false,
				'error' => 'network_error',
				'message' => $error_message,
			];
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		// Handle validation errors (422) - unexpected, indicates a bug in our request.
		if ( $status_code === 422 ) {
			$error = isset( $data['error'] ) ? $data['error'] : 'validation_failed';
			$message = isset( $data['message'] ) ? $data['message'] : 'Invalid request parameters';
			$errors = isset( $data['errors'] ) ? $data['errors'] : [];
			
			$this->logger->error( "License activation validation failed: {$message}", [ 'errors' => $errors, 'request_body' => $request_body ] );
			return [
				'success' => false,
				'error' => $error,
				'message' => $message,
				'errors' => $errors,
				'status_code' => $status_code,
			];
		}

		// Handle license errors (403) - expected business logic errors (invalid, expired, inactive license).
		if ( $status_code === 403 ) {
			$error = isset( $data['error'] ) ? $data['error'] : 'license_invalid';
			$message = isset( $data['message'] ) ? $data['message'] : 'License activation failed';
			
			// These are expected business logic responses, log as debug.
			$this->logger->debug( "License activation rejected: {$error} - {$message}" );
			return [
				'success' => false,
				'error' => $error,
				'message' => $message,
				'status_code' => $status_code,
			];
		}

		// Handle client errors (400) - check error type to determine if expected or unexpected.
		if ( $status_code === 400 ) {
			$error = isset( $data['error'] ) ? $data['error'] : 'activation_failed';
			$message = isset( $data['message'] ) ? $data['message'] : 'License activation failed';
			
			// Some 400 errors are expected business logic (activation_failed), others are unexpected (auth errors, etc.).
			// Log unexpected errors as error for troubleshooting.
			$expected_errors = [ 'activation_failed' ];
			if ( in_array( $error, $expected_errors, true ) ) {
				$this->logger->debug( "License activation failed: {$error} - {$message}" );
			} else {
				$this->logger->error( "License activation unexpected error: {$error} - {$message} (Status: {$status_code})", [ 'response' => $data ] );
			}
			
			return [
				'success' => false,
				'error' => $error,
				'message' => $message,
				'status_code' => $status_code,
			];
		}

		// Handle server errors (500) - unexpected server-side errors.
		if ( $status_code === 500 ) {
			$error = isset( $data['error'] ) ? $data['error'] : 'internal_error';
			$message = isset( $data['message'] ) ? $data['message'] : 'An internal error occurred while processing the activation request';
			
			$this->logger->error( "License activation server error: {$error} - {$message} (Status: {$status_code})", [ 'response' => $data ] );
			return [
				'success' => false,
				'error' => $error,
				'message' => $message,
				'status_code' => $status_code,
			];
		}

		// Handle success (200) - expected successful response.
		if ( $status_code === 200 ) {
			$valid = isset( $data['valid'] ) ? (bool) $data['valid'] : false;
			// Success is expected, log as debug.
			$this->logger->debug( "License activated successfully for account {$account_id}, valid: " . ( $valid ? 'true' : 'false' ) );
			
			return [
				'success' => true,
				'valid' => $valid,
				'message' => isset( $data['message'] ) ? $data['message'] : 'License activated successfully',
			];
		}

		// Handle unexpected status codes - these indicate a problem.
		$error = isset( $data['error'] ) ? $data['error'] : 'unknown_error';
		
		// Get message from data, or fallback to raw response body if not available
		if ( isset( $data['message'] ) ) {
			$message = $data['message'];
		} else {
			$message = "Unexpected response status: {$status_code}";
		}
		
		// In debug mode, include full response in message for troubleshooting
		$is_debug = defined( 'WP_DEBUG' ) && WP_DEBUG;
		if ( $is_debug ) {
			// If we successfully decoded JSON, use that, otherwise use raw body
			if ( $data !== null && is_array( $data ) ) {
				$response_json = wp_json_encode( $data, JSON_PRETTY_PRINT );
				$message = sprintf( '%s. Response: %s', $message, esc_html( $response_json ) );
			} else {
				// Fallback to raw response body, escaped for frontend
				$message = sprintf( '%s. Response: %s', $message, esc_html( $body ) );
			}
		}
		
		$this->logger->error( "License activation unexpected response: {$message} (Status: {$status_code})", [ 'response' => $data ] );
		return [
			'success' => false,
			'error' => $error,
			'message' => $message,
			'status_code' => $status_code,
		];
	}

	/**
	 * Validate license key with external service.
	 *
	 * Checks if the current license key is still valid.
	 * Should be called periodically to verify license status.
	 *
	 * @since 3.0.0
	 * @param string $license_key License key to validate.
	 * @return array Response array with 'success', 'valid', 'error', and 'message'.
	 */
	public function validate_license( $license_key ) {
		// Check compatibility before making API request.
		if ( ! $this->check_compatibility_before_request() ) {
			$this->logger->warning( 'License validation blocked: Compatibility check failed' );
			return [
				'success' => false,
				'error' => 'compatibility_check_failed',
				'message' => 'Compatibility check failed. Please update the plugin or check compatibility status.',
				'status_code' => null,
			];
		}

		$account_id = Settings::get_account_id();
		
		if ( empty( $account_id ) ) {
			$this->logger->error( "License validation failed: Account ID not found" );
			return [
				'success' => false,
				'error' => 'account_id_required',
				'message' => 'Account ID not found',
				'status_code' => null,
			];
		}

		// Get website domain - use full URL as the endpoint expects a URL format.
		$website_domain = home_url();
		if ( empty( $website_domain ) ) {
			$protocol = is_ssl() ? 'https://' : 'http://';
			$website_domain = $protocol . ( $_SERVER['HTTP_HOST'] ?? 'localhost' );
		}

		$endpoint = trailingslashit( $this->base_url ) . 'api/v1/licenses/validate';
		
		$request_body = [
			'license_key' => sanitize_text_field( $license_key ),
			'account_id'  => $account_id,
			'domain'      => esc_url_raw( $website_domain ),
		];

		$this->logger->debug( "Validating license for account {$account_id}, domain: {$website_domain}" );
		
		$response = wp_remote_post( $endpoint, [
			'timeout' => FLUX_MEDIA_OPTIMIZER_EXTERNAL_SERVICE_TIMEOUT,
			'headers' => [
				'Content-Type' => 'application/json',
			],
			'body' => wp_json_encode( $request_body ),
		] );

		if ( is_wp_error( $response ) ) {
			$error_message = $response->get_error_message();
			// Network errors are expected in some scenarios (timeout, connection issues), log as debug.
			$this->logger->debug( "License validation network error: {$error_message}" );
			return [
				'success' => false,
				'error' => 'network_error',
				'message' => $error_message,
				'status_code' => null,
			];
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		// Handle validation errors (422) - unexpected, indicates a bug in our request.
		if ( $status_code === 422 ) {
			$error = isset( $data['error'] ) ? $data['error'] : 'validation_failed';
			$message = isset( $data['message'] ) ? $data['message'] : 'Invalid request parameters';
			$errors = isset( $data['errors'] ) ? $data['errors'] : [];
			
			$this->logger->error( "License validation validation failed: {$message}", [ 'errors' => $errors, 'request_body' => $request_body ] );
			return [
				'success' => false,
				'error' => $error,
				'message' => $message,
				'errors' => $errors,
				'status_code' => $status_code,
			];
		}

		// Handle license errors (403) - expected business logic errors (invalid, expired, inactive license).
		if ( $status_code === 403 ) {
			$error = isset( $data['error'] ) ? $data['error'] : 'license_invalid';
			$message = isset( $data['message'] ) ? $data['message'] : 'License validation failed';
			
			// These are expected business logic responses, log as debug.
			$this->logger->debug( "License validation rejected: {$error} - {$message}" );
			return [
				'success' => false,
				'error' => $error,
				'message' => $message,
				'status_code' => $status_code,
			];
		}

		// Handle client errors (400) - check error type to determine if expected or unexpected.
		if ( $status_code === 400 ) {
			$error = isset( $data['error'] ) ? $data['error'] : 'validation_failed';
			$message = isset( $data['message'] ) ? $data['message'] : 'License validation failed';
			
			// Some 400 errors are expected business logic (validation_failed), others are unexpected (auth errors, etc.).
			// Log unexpected errors as error for troubleshooting.
			$expected_errors = [ 'validation_failed' ];
			if ( in_array( $error, $expected_errors, true ) ) {
				$this->logger->debug( "License validation failed: {$error} - {$message}" );
			} else {
				$this->logger->error( "License validation unexpected error: {$error} - {$message} (Status: {$status_code})", [ 'response' => $data ] );
			}
			
			return [
				'success' => false,
				'error' => $error,
				'message' => $message,
				'status_code' => $status_code,
			];
		}

		// Handle server errors (500) - unexpected server-side errors.
		if ( $status_code === 500 ) {
			$error = isset( $data['error'] ) ? $data['error'] : 'internal_error';
			$message = isset( $data['message'] ) ? $data['message'] : 'An internal error occurred while processing the validation request';
			
			$this->logger->error( "License validation server error: {$error} - {$message} (Status: {$status_code})", [ 'response' => $data ] );
			return [
				'success' => false,
				'error' => $error,
				'message' => $message,
				'status_code' => $status_code,
			];
		}

		// Handle success (200) - expected successful response.
		if ( $status_code === 200 ) {
			$valid = isset( $data['valid'] ) ? (bool) $data['valid'] : false;
			// Success is expected, log as debug.
			$this->logger->debug( "License validated successfully for account {$account_id}, valid: " . ( $valid ? 'true' : 'false' ) );
			
			return [
				'success' => true,
				'valid' => $valid,
				'message' => isset( $data['message'] ) ? $data['message'] : 'License validated successfully',
			];
		}

		// Handle unexpected status codes - these indicate a problem.
		$error = isset( $data['error'] ) ? $data['error'] : 'unknown_error';
		$message = isset( $data['message'] ) ? $data['message'] : "Unexpected response status: {$status_code}";
		
		// In debug mode, include full response in message for troubleshooting
		$is_debug = defined( 'WP_DEBUG' ) && WP_DEBUG;
		if ( $is_debug ) {
			// If we successfully decoded JSON, use that, otherwise use raw body
			if ( $data !== null && is_array( $data ) ) {
				$response_json = wp_json_encode( $data, JSON_PRETTY_PRINT );
				$message = sprintf( '%s. Response: %s', $message, esc_html( $response_json ) );
			} else {
				// Fallback to raw response body, escaped for frontend
				$message = sprintf( '%s. Response: %s', $message, esc_html( $body ) );
			}
		}
		
		$this->logger->error( "License validation unexpected response: {$message} (Status: {$status_code})", [ 'response' => $data ] );
		return [
			'success' => false,
			'error' => $error,
			'message' => $message,
			'status_code' => $status_code,
		];
	}

	/**
	 * Check plugin compatibility with external service.
	 *
	 * This endpoint validates version compatibility between the plugin and API service.
	 * It is independent of license validation and focuses solely on version requirements.
	 *
	 * @since 3.0.0
	 * @param string $plugin_identifier Plugin identifier (e.g., 'flux-media-optimizer').
	 * @param string $plugin_version   Current plugin version.
	 * @return CompatibilityResponse|array Response object or array with 'success' and error info on failure.
	 */
	public function check_compatibility( $plugin_identifier, $plugin_version ) {
		$endpoint = trailingslashit( $this->base_url ) . 'api/v1/compatibility/check';
		
		$request_body = [
			'plugin_identifier' => sanitize_text_field( $plugin_identifier ),
			'plugin_version'    => sanitize_text_field( $plugin_version ),
		];

		$this->logger->debug( "Checking compatibility for plugin {$plugin_identifier} version {$plugin_version}" );
		
		$response = wp_remote_post( $endpoint, [
			'timeout' => FLUX_MEDIA_OPTIMIZER_EXTERNAL_SERVICE_TIMEOUT,
			'headers' => [
				'Content-Type' => 'application/json',
			],
			'body' => wp_json_encode( $request_body ),
		] );

		if ( is_wp_error( $response ) ) {
			$error_message = $response->get_error_message();
			$this->logger->warning( "Compatibility check network error: {$error_message}" );
			return [
				'success' => false,
				'error' => 'network_error',
				'message' => $error_message,
			];
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		// Handle validation errors (422) - unexpected, indicates a bug in our request.
		if ( $status_code === 422 ) {
			$error = isset( $data['error'] ) ? $data['error'] : 'validation_failed';
			$message = isset( $data['message'] ) ? $data['message'] : 'Invalid request parameters';
			$errors = isset( $data['errors'] ) ? $data['errors'] : [];
			
			$this->logger->error( "Compatibility check validation failed: {$message}", [ 'errors' => $errors, 'request_body' => $request_body ] );
			return [
				'success' => false,
				'error' => $error,
				'message' => $message,
				'errors' => $errors,
				'status_code' => $status_code,
			];
		}

		// Handle server errors (500) - unexpected server-side errors.
		if ( $status_code === 500 ) {
			$error = isset( $data['error'] ) ? $data['error'] : 'internal_error';
			$message = isset( $data['message'] ) ? $data['message'] : 'An internal error occurred while processing the compatibility check';
			
			$this->logger->error( "Compatibility check server error: {$error} - {$message} (Status: {$status_code})", [ 'response' => $data ] );
			return [
				'success' => false,
				'error' => $error,
				'message' => $message,
				'status_code' => $status_code,
			];
		}

		// Handle success (200) - expected successful response.
		if ( $status_code === 200 ) {
			$this->logger->debug( "Compatibility check successful for plugin {$plugin_identifier} version {$plugin_version}" );
			
			// Return CompatibilityResponse object.
			return new CompatibilityResponse( $data );
		}

		// Handle unexpected status codes.
		$error = isset( $data['error'] ) ? $data['error'] : 'unknown_error';
		$message = isset( $data['message'] ) ? $data['message'] : "Unexpected response status: {$status_code}";
		
		$this->logger->error( "Compatibility check unexpected response: {$message} (Status: {$status_code})", [ 'response' => $data ] );
		return [
			'success' => false,
			'error' => $error,
			'message' => $message,
			'status_code' => $status_code,
		];
	}

	/**
	 * Delete attachment from external service.
	 *
	 * Notifies the external service to delete files associated with an attachment.
	 * This should be called when an attachment is deleted from WordPress.
	 *
	 * @since 3.0.0
	 * @param int $attachment_id Attachment ID.
	 * @return array Response array with 'success' and optional 'error' or 'message'.
	 */
	public function delete_attachment( $attachment_id ) {
		// Do not Check compatibility before making API request. We want to avoid orphan data when files are being deleted, even if this fails we at least try.

		$account_id = Settings::get_account_id();
		
		if ( empty( $account_id ) ) {
			$this->logger->error( "Attachment deletion failed for attachment {$attachment_id}: Account ID not found" );
			return [
				'success' => false,
				'error' => 'account_id_required',
				'message' => 'Account ID not found',
			];
		}

		$endpoint = trailingslashit( $this->base_url ) . 'api/v1/' . FLUX_MEDIA_OPTIMIZER_API_NAMESPACE . '/upload/delete';
		
		$request_body = [
			'account_id'    => $account_id,
			'attachment_id' => (string) $attachment_id,
		];

		$this->logger->debug( "Deleting attachment {$attachment_id} from external service for account {$account_id}" );
		
		$response = wp_remote_post( $endpoint, [
			'timeout' => FLUX_MEDIA_OPTIMIZER_EXTERNAL_SERVICE_TIMEOUT,
			'headers' => [
				'Content-Type' => 'application/json',
			],
			'body' => wp_json_encode( $request_body ),
		] );

		if ( is_wp_error( $response ) ) {
			$error_message = $response->get_error_message();
			$this->logger->error( "Failed to delete attachment {$attachment_id} from external service: {$error_message}" );
			return [
				'success' => false,
				'error' => 'network_error',
				'message' => $error_message,
			];
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		// Handle success (200) - expected successful response.
		if ( $status_code === 200 ) {
			$this->logger->debug( "Attachment {$attachment_id} deleted successfully from external service" );
			
			return [
				'success' => true,
				'message' => isset( $data['message'] ) ? $data['message'] : 'Attachment deleted successfully',
			];
		}

		// Handle other status codes - log but don't fail hard (deletion is best-effort)
		$error = isset( $data['error'] ) ? $data['error'] : 'unknown_error';
		$message = isset( $data['message'] ) ? $data['message'] : "Unexpected response status: {$status_code}";
		
		// Log as warning since deletion failure shouldn't block WordPress deletion
		$this->logger->warning( "Attachment deletion returned unexpected status: {$message} (Status: {$status_code})", [ 'response' => $data ] );
		
		return [
			'success' => false,
			'error' => $error,
			'message' => $message,
			'status_code' => $status_code,
		];
	}
}

