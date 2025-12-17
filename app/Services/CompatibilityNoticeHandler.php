<?php
/**
 * Admin notice handler for compatibility validation.
 *
 * Displays WordPress admin notices based on compatibility validation results.
 *
 * @package FluxMedia
 * @since 3.0.0
 */

namespace FluxMedia\App\Services;

/**
 * Compatibility notice handler class.
 *
 * @since 3.0.0
 */
class CompatibilityNoticeHandler {

	/**
	 * Compatibility validator instance.
	 *
	 * @since 3.0.0
	 * @var CompatibilityValidator
	 */
	private $validator;

	/**
	 * Dismissal transient name prefix.
	 *
	 * @since 3.0.0
	 * @var string
	 */
	private $dismissal_transient_prefix = 'flux_media_optimizer_compatibility_dismissed_';

	/**
	 * Constructor.
	 *
	 * @since 3.0.0
	 * @param CompatibilityValidator $validator Compatibility validator instance.
	 */
	public function __construct( CompatibilityValidator $validator ) {
		$this->validator = $validator;
	}

	/**
	 * Initialize notice handling.
	 *
	 * @since 3.0.0
	 * @return void
	 */
	public function init() {
		// Only show notices in admin area.
		if ( ! is_admin() ) {
			return;
		}

		// Register admin notice hook.
		add_action( 'admin_notices', [ $this, 'display_notice' ] );

		// Register AJAX handler for dismissing notices.
		add_action( 'wp_ajax_flux_media_optimizer_dismiss_compatibility_notice', [ $this, 'handle_dismiss_notice' ] );

		// Enqueue dismiss script.
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_dismiss_script' ] );
	}

	/**
	 * Display compatibility notices if needed.
	 *
	 * @since 3.0.0
	 * @return void
	 */
	public function display_notice() {
		// Only show to users with manage_options capability.
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$notices = $this->validator->get_notices();

		if ( empty( $notices ) ) {
			return;
		}

		// Display each notice.
		foreach ( $notices as $notice ) {
			// Generate unique hash from error_code and message.
			$error_code = isset( $notice['error_code'] ) ? $notice['error_code'] : $notice['code'];
			$notice_hash = $this->generate_notice_hash( $error_code, $notice['message'] );

			// Check if notice has been dismissed.
			$dismissed = $this->is_notice_dismissed( $notice_hash );
			if ( $dismissed ) {
				continue;
			}

			// Determine notice class based on type.
			$notice_class = $this->get_notice_class( $notice['type'] );

			// Build notice HTML.
			$message = $notice['message'];

			// Add action button/link if provided.
			$action_html = '';
			if ( $notice['action'] && ! empty( $notice['action']['url'] ) && ! empty( $notice['action']['label'] ) ) {
				$action_html = sprintf(
					' <a href="%s" target="_blank" rel="noopener noreferrer" class="button button-secondary" style="margin-left: 10px;">%s</a>',
					esc_url( $notice['action']['url'] ),
					esc_html( $notice['action']['label'] )
				);
			}

			// Add dismiss button for notices.
			$dismiss_url = wp_nonce_url(
				admin_url( 'admin-ajax.php?action=flux_media_optimizer_dismiss_compatibility_notice&hash=' . urlencode( $notice_hash ) ),
				'flux_media_optimizer_dismiss_compatibility_' . $notice_hash
			);
			$dismiss_html = sprintf(
				'<button type="button" class="notice-dismiss flux-media-optimizer-dismiss" data-dismiss-url="%s" data-hash="%s"><span class="screen-reader-text">%s</span></button>',
				esc_url( $dismiss_url ),
				esc_attr( $notice_hash ),
				esc_html__( 'Dismiss this notice', 'flux-media-optimizer' )
			);

			// Output notice.
			printf(
				'<div class="notice %s is-dismissible flux-media-optimizer-compatibility-notice" data-hash="%s">%s<p>%s%s</p></div>',
				esc_attr( $notice_class ),
				esc_attr( $notice_hash ),
				$dismiss_html,
				wp_kses_post( $message ),
				$action_html
			);
		}
	}

	/**
	 * Get WordPress notice class based on notice type.
	 *
	 * @since 3.0.0
	 * @param string $type Notice type (error, warning, info, reminder).
	 * @return string WordPress notice class.
	 */
	private function get_notice_class( $type ) {
		switch ( $type ) {
			case 'error':
				return 'notice-error';
			case 'warning':
				return 'notice-warning';
			case 'info':
			case 'reminder':
			default:
				return 'notice-info';
		}
	}

	/**
	 * Enqueue dismiss script following WordPress guidelines.
	 *
	 * Uses wp_enqueue_script() to properly enqueue the compiled JavaScript file
	 * with jQuery dependency per WordPress Plugin Guidelines #13.
	 *
	 * @since 3.0.0
	 * @param string $hook Current admin page hook.
	 * @return void
	 */
	public function enqueue_dismiss_script( $hook = '' ) {
		// Only enqueue script if there are compatibility notices on the page.
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$notices = $this->validator->get_notices();
		if ( empty( $notices ) ) {
			return;
		}

		// Check if any notices are actually displayed (not dismissed).
		$has_visible_notices = false;
		foreach ( $notices as $notice ) {
			$error_code = isset( $notice['error_code'] ) ? $notice['error_code'] : $notice['code'];
			$notice_hash = $this->generate_notice_hash( $error_code, $notice['message'] );
			if ( ! $this->is_notice_dismissed( $notice_hash ) ) {
				$has_visible_notices = true;
				break;
			}
		}

		if ( ! $has_visible_notices ) {
			return;
		}

		// Determine script path based on SCRIPT_DEBUG (development vs production).
		$script_debug = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG;
		$script_path = $script_debug
			? 'assets/js/src/admin/compatibility-dismiss.js' // Source file for development.
			: 'assets/js/dist/compatibility-dismiss.bundle.js'; // Built file for production.

		// Enqueue script with jQuery as dependency (WordPress Plugin Guidelines #13).
		wp_enqueue_script(
			'flux-media-optimizer-compatibility-dismiss',
			FLUX_MEDIA_OPTIMIZER_PLUGIN_URL . $script_path,
			[ 'jquery' ], // jQuery dependency - WordPress default library.
			FLUX_MEDIA_OPTIMIZER_VERSION,
			true // Load in footer.
		);
	}

	/**
	 * Handle AJAX request to dismiss notice.
	 *
	 * @since 3.0.0
	 * @return void
	 */
	public function handle_dismiss_notice() {
		// Verify nonce and get hash.
		$hash = isset( $_GET['hash'] ) ? sanitize_text_field( $_GET['hash'] ) : '';
		if ( empty( $hash ) ) {
			wp_send_json_error( [ 'message' => __( 'Invalid notice hash', 'flux-media-optimizer' ) ] );
		}

		check_ajax_referer( 'flux_media_optimizer_dismiss_compatibility_' . $hash, '_wpnonce' );

		// Verify user capability.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'Insufficient permissions', 'flux-media-optimizer' ) ] );
		}

		// Dismiss notice.
		$this->dismiss_notice( $hash );

		wp_send_json_success( [ 'message' => __( 'Notice dismissed', 'flux-media-optimizer' ) ] );
	}

	/**
	 * Generate a unique hash for a notice based on error_code and message.
	 *
	 * @since 3.0.0
	 * @param string $error_code Error code.
	 * @param string $message    Message.
	 * @return string Hashed notice identifier.
	 */
	private function generate_notice_hash( $error_code, $message ) {
		$combined = $error_code . '|' . $message;
		return md5( $combined );
	}

	/**
	 * Check if a notice has been dismissed.
	 *
	 * @since 3.0.0
	 * @param string $hash Notice hash.
	 * @return bool True if dismissed, false otherwise.
	 */
	private function is_notice_dismissed( $hash ) {
		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			return false;
		}

		$transient_name = $this->dismissal_transient_prefix . $user_id . '_' . $hash;
		return (bool) get_transient( $transient_name );
	}

	/**
	 * Dismiss a notice.
	 *
	 * @since 3.0.0
	 * @param string $hash Notice hash.
	 * @return void
	 */
	private function dismiss_notice( $hash ) {
		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			return;
		}

		$transient_name = $this->dismissal_transient_prefix . $user_id . '_' . $hash;
		// Store for 30 days (2592000 seconds).
		set_transient( $transient_name, true, 30 * DAY_IN_SECONDS );
	}
}

