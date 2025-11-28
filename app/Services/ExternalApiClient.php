<?php
/**
 * External API client for Flux Media Optimizer external service.
 *
 * @package FluxMedia
 * @since 3.0.0
 */

namespace FluxMedia\App\Services;

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
	 * Submit a job to the external service.
	 *
	 * @since 3.0.0
	 * @param int    $attachment_id Attachment ID.
	 * @param array  $operations    Array of operations to perform.
	 * @param string $mimetype     MIME type of the file.
	 * @param string $webhook_url  Webhook URL for callback.
	 * @return array Response array with 'success', 'job_id', 'status', 'base_url', or 'error'.
	 */
	public function submit_job( $attachment_id, $operations = [], $mimetype = '', $webhook_url = '' ) {
		$account_id = Settings::get_account_id();

		if ( empty( $account_id ) ) {
			return [
				'success' => false,
				'error' => 'Account ID not found',
			];
		}

		// Get file URL from attachment ID.
		$pull_file_url = wp_get_attachment_url( $attachment_id );
		if ( ! $pull_file_url ) {
			return [
				'success' => false,
				'error' => 'Could not get attachment URL',
			];
		}

		$endpoint = trailingslashit( $this->base_url ) . 'api/v1/jobs';
		
		$body = [
			'account_id'     => $account_id,
			'attachment_id'  => (string) $attachment_id,
			'pull_file_url'  => esc_url_raw( $pull_file_url ),
			'webhook_url'    => esc_url_raw( $webhook_url ),
			'mimetype'       => sanitize_text_field( $mimetype ),
			'operations'     => $operations,
		];

		$response = wp_remote_post( $endpoint, [
			'timeout' => 30,
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

		if ( ! isset( $data['job_id'] ) || ! isset( $data['status'] ) ) {
			$this->logger->error( "Invalid response from external service: " . wp_json_encode( $data ) );
			return [
				'success' => false,
				'error' => 'Invalid response from external service',
			];
		}

		$this->logger->info( "Job submitted successfully: {$data['job_id']} for attachment {$attachment_id}" );

		return [
			'success' => true,
			'job_id' => sanitize_text_field( $data['job_id'] ),
			'status' => sanitize_text_field( $data['status'] ),
			'base_url' => isset( $data['base_url'] ) ? esc_url_raw( $data['base_url'] ) : null,
		];
	}

	/**
	 * Get job status from external service.
	 *
	 * @since 3.0.0
	 * @param string $job_id Job ID.
	 * @return array Response array with 'success', 'status', 'base_url', or 'error'.
	 */
	public function get_job_status( $job_id ) {
		$account_id = Settings::get_account_id();

		if ( empty( $account_id ) ) {
			return [
				'success' => false,
				'error' => 'Account ID not found',
			];
		}

		$endpoint = trailingslashit( $this->base_url ) . 'api/v1/jobs/' . urlencode( $job_id );
		$endpoint = add_query_arg( 'account_id', $account_id, $endpoint );
		
		$response = wp_remote_get( $endpoint, [
			'timeout' => 15,
			'headers' => [
				'Content-Type' => 'application/json',
			],
		] );

		if ( is_wp_error( $response ) ) {
			$error_message = $response->get_error_message();
			$this->logger->error( "Failed to get job status from external service: {$error_message}" );
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
			return [
				'success' => false,
				'error' => $data['error'],
			];
		}

		return [
			'success' => true,
			'status' => isset( $data['status'] ) ? sanitize_text_field( $data['status'] ) : 'unknown',
			'base_url' => isset( $data['base_url'] ) ? esc_url_raw( $data['base_url'] ) : null,
		];
	}

	/**
	 * Activate license key with external service.
	 *
	 * This is the activation request that sends license_key, account_id, and website domain.
	 * Should be called when license_key changes or is initially set.
	 * All subsequent requests use only account_id.
	 *
	 * @since 3.0.0
	 * @param string $license_key License key to activate.
	 * @return array Response array with 'success' and 'valid' or 'error'.
	 */
	public function activate_license( $license_key ) {
		$account_id = Settings::get_account_id();
		
		if ( empty( $account_id ) ) {
			return [
				'success' => false,
				'error' => 'Account ID not found',
			];
		}

		// Get website domain.
		$website_domain = wp_parse_url( home_url(), PHP_URL_HOST );
		if ( ! $website_domain ) {
			$website_domain = $_SERVER['HTTP_HOST'] ?? '';
		}

		$endpoint = trailingslashit( $this->base_url ) . 'v1/licenses/activate';
		
		$response = wp_remote_post( $endpoint, [
			'timeout' => 15,
			'headers' => [
				'Content-Type' => 'application/json',
			],
			'body' => wp_json_encode( [
				'license_key' => sanitize_text_field( $license_key ),
				'account_id'  => $account_id,
				'domain'     => sanitize_text_field( $website_domain ),
			] ),
		] );

		if ( is_wp_error( $response ) ) {
			$error_message = $response->get_error_message();
			$this->logger->error( "Failed to activate license: {$error_message}" );
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
			$this->logger->error( "License activation failed: {$error} (Status: {$status_code})" );
			return [
				'success' => false,
				'error' => $error,
			];
		}

		$this->logger->info( "License activated successfully for account {$account_id}" );

		return [
			'success' => true,
			'valid' => isset( $data['valid'] ) ? (bool) $data['valid'] : false,
		];
	}
}

