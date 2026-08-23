<?php
/**
 * Attachment details mount renderer for Media Library fields.
 *
 * @package FluxMedia\App\Services
 * @since 4.3.0
 */

namespace FluxMedia\App\Services;

/**
 * Emits a compact skeleton mount point for the attachment React island.
 *
 * Classic edit and media modals both use attachment_fields_to_edit → AttachmentCompat
 * with a full-width custom tr (colspan=2). Mount HTML is SSOT via build_mount_html().
 *
 * @since 4.3.0
 */
class AttachmentDetailsMountRenderer {

	/**
	 * Build the React island mount HTML for an attachment.
	 *
	 * Shared SSOT for classic form fields and media-modal AttachmentCompat markup.
	 *
	 * @since 4.3.0
	 * @param int $attachment_id Attachment post ID.
	 * @return string Mount markup.
	 */
	public function build_mount_html( $attachment_id ) {
		$attachment_id = (int) $attachment_id;
		$mount_id      = 'flux-media-optimizer-attachment-' . $attachment_id;

		$html  = '<div class="flux-media-optimizer-attachment-root" data-flux-media-attachment-root="1" data-flux-media-attachment-id="' . esc_attr( (string) $attachment_id ) . '">';
		$html .= '<div id="' . esc_attr( $mount_id ) . '" class="flux-media-optimizer-attachment-app" data-flux-media-attachment-app="1" data-flux-media-attachment-id="' . esc_attr( (string) $attachment_id ) . '">';
		$html .= $this->get_skeleton_html();
		$html .= '</div>';
		$html .= '</div>';

		return $html;
	}

	/**
	 * Build a full-width compat table row wrapping the React mount.
	 *
	 * Uses core's documented `tr` field escape hatch so the panel is not squeezed
	 * into a labeled th/td.field pair. Core applies `tr` before show_in_* checks.
	 *
	 * @since 4.3.0
	 * @param int $attachment_id Attachment post ID.
	 * @return string Table row markup for get_compat_media_markup().
	 */
	public function build_compat_tr( $attachment_id ) {
		$mount = $this->build_mount_html( (int) $attachment_id );

		return "\t\t<tr class=\"compat-field-flux_media_optimizer\">\n"
			. "\t\t\t<td colspan=\"2\" class=\"field flux-media-optimizer-compat-field\">"
			. $mount
			. "</td>\n\t\t</tr>\n";
	}

	/**
	 * Add Flux Media Optimizer fields to the attachment edit form.
	 *
	 * Emits a React island mount with a compact PHP skeleton. Payload loads asynchronously.
	 * Field is prepended so AttachmentCompat renders it first (directly under Copy URL).
	 *
	 * @since 4.3.0
	 * @param array    $form_fields Attachment form fields.
	 * @param \WP_Post $post        The attachment post object.
	 * @return array Modified form fields.
	 */
	public function modify_attachment_fields( $form_fields, $post ) {
		try {
			if ( ! $post || ! isset( $post->ID ) ) {
				return $form_fields;
			}

			if ( ! current_user_can( 'edit_post', (int) $post->ID ) ) {
				return $form_fields;
			}

			if ( ! is_array( $form_fields ) ) {
				$form_fields = [];
			}

			$field = [
				'label'         => '',
				'input'         => 'html',
				'html'          => $this->build_mount_html( (int) $post->ID ),
				// Core uses `tr` when set; show_in_* remain for intent/docs (tr bypasses them).
				'tr'            => $this->build_compat_tr( (int) $post->ID ),
				'show_in_modal' => true,
				'show_in_edit'  => true,
			];

			// Prepend so Flux is the first compat row (under Copy URL, before taxonomies).
			$form_fields = [ 'flux_media_optimizer' => $field ] + $form_fields;
		} catch ( \Exception $e ) {
			\FluxMedia\FluxPlugins\Common\Logger\Logger::get_instance()->error(
				'Error in AttachmentDetailsMountRenderer::modify_attachment_fields: ' . $e->getMessage()
			);
		} catch ( \Error $e ) {
			\FluxMedia\FluxPlugins\Common\Logger\Logger::get_instance()->error(
				'Fatal error in AttachmentDetailsMountRenderer::modify_attachment_fields: ' . $e->getMessage()
			);
		}

		return $form_fields;
	}

	/**
	 * Compact static skeleton shown until React replaces the mount.
	 *
	 * @since 4.3.0
	 * @return string
	 */
	private function get_skeleton_html() {
		$html  = '<div class="flux-media-optimizer-attachment-skeleton" data-flux-media-attachment-skeleton="1" aria-hidden="true">';
		$html .= '<div class="flux-media-optimizer-attachment-skeleton__header"></div>';
		$html .= '<div class="flux-media-optimizer-attachment-skeleton__row"></div>';
		$html .= '<div class="flux-media-optimizer-attachment-skeleton__row"></div>';
		$html .= '<div class="flux-media-optimizer-attachment-skeleton__row flux-media-optimizer-attachment-skeleton__row--short"></div>';
		$html .= '</div>';
		return $html;
	}
}
