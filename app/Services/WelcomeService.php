<?php
/**
 * First-activation welcome modal flag.
 *
 * @package FluxMedia
 * @since 4.3.1
 */

namespace FluxMedia\App\Services;

/**
 * Owns the once-ever show-welcome option for the admin Overview modal.
 *
 * @since 4.3.1
 */
class WelcomeService {

	/**
	 * Option name for the pending welcome modal flag.
	 *
	 * @since 4.3.1
	 * @var string
	 */
	public const OPTION_SHOW_WELCOME = 'flux_media_optimizer_show_welcome';

	/**
	 * CDN buy URL for the welcome upsell (includes UTM tracking).
	 *
	 * @since 4.3.1
	 * @var string
	 */
	public const WELCOME_UPSELL_URL = 'https://fluxplugins.com/buy?utm_source=flux-media-optimizer&utm_medium=plugin&utm_campaign=cdn-upsell&utm_content=welcome-modal';

	/**
	 * Mark that the welcome modal should show once after activation.
	 *
	 * @since 4.3.1
	 * @return void
	 */
	public static function arm_for_activation() {
		OnceEverPromptFlag::set( self::OPTION_SHOW_WELCOME );
	}

	/**
	 * Whether the welcome modal should open from the stored option.
	 *
	 * @since 4.3.1
	 * @return bool
	 */
	public static function should_show_from_option() {
		return OnceEverPromptFlag::is_set( self::OPTION_SHOW_WELCOME );
	}

	/**
	 * Clear the once-ever welcome flag after Overview has loaded it.
	 *
	 * @since 4.3.1
	 * @return void
	 */
	public static function mark_viewed() {
		OnceEverPromptFlag::clear( self::OPTION_SHOW_WELCOME );
	}
}
