<?php
/**
 * WordPress.org support and review URL SSOT.
 *
 * @package FluxMedia
 * @since 4.3.1
 */

namespace FluxMedia\App\Services;

/**
 * Centralizes plugin directory support and review permalinks (no UTMs).
 *
 * @since 4.3.1
 */
class PluginSupportUrls {

	/**
	 * WordPress.org plugin support forum URL.
	 *
	 * @since 4.3.1
	 * @var string
	 */
	public const SUPPORT_FORUM_URL = 'https://wordpress.org/support/plugin/flux-media-optimizer/';

	/**
	 * WordPress.org new review form URL.
	 *
	 * @since 4.3.1
	 * @var string
	 */
	public const REVIEWS_URL = 'https://wordpress.org/support/plugin/flux-media-optimizer/reviews/#new-post';
}
