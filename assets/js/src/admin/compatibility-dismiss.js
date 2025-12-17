/**
 * Compatibility notice dismiss handler.
 *
 * Handles dismiss functionality for compatibility validation notices.
 *
 * @package FluxMedia
 * @since 3.0.0
 */

(function($) {
	'use strict';

	/**
	 * Initialize dismiss functionality when DOM is ready.
	 */
	$(document).ready(function() {
		// Use event delegation to handle all dismiss buttons.
		// Use our custom class to avoid conflicts with WordPress's built-in dismiss handler.
		$(document).on('click', '.flux-media-optimizer-compatibility-notice .flux-media-optimizer-dismiss', function(e) {
			e.preventDefault();
			e.stopPropagation();
			e.stopImmediatePropagation();

			var $button = $(this);
			var $notice = $button.closest('.flux-media-optimizer-compatibility-notice');
			var dismissUrl = $button.attr('data-dismiss-url');

			if (!dismissUrl || !$notice.length) {
				return false;
			}

			// Make the AJAX request using jQuery (WordPress standard).
			$.ajax({
				url: dismissUrl,
				type: 'GET',
				dataType: 'json',
				success: function(response) {
					if (response && response.success) {
						// Fade out and remove notice.
						$notice.fadeOut(300, function() {
							$(this).remove();
						});
					} else {
						console.error('Error dismissing notice:', response && response.data && response.data.message ? response.data.message : 'Unknown error');
					}
				},
				error: function(xhr, status, error) {
					console.error('Error dismissing notice:', error);
				}
			});

			return false;
		});
	});
})(jQuery);

