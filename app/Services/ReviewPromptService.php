<?php
/**
 * Once-ever review prompt eligibility and consumed flag.
 *
 * @package FluxMedia
 * @since 4.3.1
 */

namespace FluxMedia\App\Services;

/**
 * Owns review-prompt milestones and forever-consumed option for Overview Dialog.
 *
 * @since 4.3.1
 */
class ReviewPromptService {

	/**
	 * Option name set after the review prompt has been shown once.
	 *
	 * @since 4.3.1
	 * @var string
	 */
	public const OPTION_CONSUMED = 'flux_media_optimizer_review_prompt_consumed';

	/**
	 * Minimum distinct optimized attachments required to unlock the prompt.
	 *
	 * @since 4.3.1
	 * @var int
	 */
	public const MIN_DISTINCT_ATTACHMENTS = 3;

	/**
	 * Minimum total download-bandwidth savings (bytes) required to unlock.
	 *
	 * @since 4.3.1
	 * @var int
	 */
	public const MIN_SAVINGS_BYTES = 52428800; // 50 MB.

	/**
	 * WordPress.org reviews URL (SSOT via PluginSupportUrls).
	 *
	 * @since 4.3.1
	 * @var string
	 */
	public const REVIEW_URL = PluginSupportUrls::REVIEWS_URL;

	/**
	 * WordPress.org support forum URL (SSOT via PluginSupportUrls).
	 *
	 * @since 4.3.1
	 * @var string
	 */
	public const SUPPORT_URL = PluginSupportUrls::SUPPORT_FORUM_URL;

	/**
	 * Whether the review prompt has already been consumed.
	 *
	 * @since 4.3.1
	 * @return bool
	 */
	public static function is_consumed() {
		return OnceEverPromptFlag::is_set( self::OPTION_CONSUMED );
	}

	/**
	 * Mark the review prompt as consumed forever after Overview opens it.
	 *
	 * @since 4.3.1
	 * @return void
	 */
	public static function mark_viewed() {
		OnceEverPromptFlag::set( self::OPTION_CONSUMED );
	}

	/**
	 * Whether attachment and savings milestones are met.
	 *
	 * @since 4.3.1
	 * @param int $distinct_attachments Distinct optimized attachment count.
	 * @param int $savings_bytes        Total savings in bytes.
	 * @return bool
	 */
	public static function milestones_met( $distinct_attachments, $savings_bytes ) {
		return (int) $distinct_attachments >= self::MIN_DISTINCT_ATTACHMENTS
			&& (int) $savings_bytes >= self::MIN_SAVINGS_BYTES;
	}

	/**
	 * Whether the review Dialog should open for a normal (non-force) Overview load.
	 *
	 * @since 4.3.1
	 * @param bool $welcome_showing     Whether welcome is open this load.
	 * @param int  $distinct_attachments Distinct optimized attachments.
	 * @param int  $savings_bytes        Total savings bytes.
	 * @param int  $failed_conversions   Failed optimization count.
	 * @return bool
	 */
	public static function should_show( $welcome_showing, $distinct_attachments, $savings_bytes, $failed_conversions ) {
		if ( self::is_consumed() ) {
			return false;
		}

		if ( $welcome_showing ) {
			return false;
		}

		if ( (int) $failed_conversions > 0 ) {
			return false;
		}

		return self::milestones_met( $distinct_attachments, $savings_bytes );
	}
}
