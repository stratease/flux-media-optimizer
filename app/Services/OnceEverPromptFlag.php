<?php
/**
 * Shared once-ever admin prompt option flag helper.
 *
 * @package FluxMedia
 * @since 4.3.1
 */

namespace FluxMedia\App\Services;

/**
 * Thin SSOT for arming, reading, and clearing once-ever prompt options.
 *
 * @since 4.3.1
 */
class OnceEverPromptFlag {

	/**
	 * Arm a once-ever prompt option (value 1, no autoload).
	 *
	 * @since 4.3.1
	 * @param string $option_name Option key.
	 * @return void
	 */
	public static function set( $option_name ) {
		update_option( $option_name, 1, false );
	}

	/**
	 * Whether the once-ever prompt option is currently set.
	 *
	 * @since 4.3.1
	 * @param string $option_name Option key.
	 * @return bool
	 */
	public static function is_set( $option_name ) {
		return (bool) get_option( $option_name, false );
	}

	/**
	 * Clear a once-ever prompt option.
	 *
	 * @since 4.3.1
	 * @param string $option_name Option key.
	 * @return void
	 */
	public static function clear( $option_name ) {
		delete_option( $option_name );
	}
}
