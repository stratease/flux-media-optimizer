<?php
/**
 * Conversion record model.
 *
 * @package FluxMedia
 * @since 1.0.0
 */

namespace FluxMedia\Models;

/**
 * Model representing a media conversion record.
 *
 * @since 1.0.0
 */
class ConversionRecord {

	/**
	 * Record ID.
	 *
	 * @since 1.0.0
	 * @var int
	 */
	public $id;

	/**
	 * WordPress attachment ID.
	 *
	 * @since 1.0.0
	 * @var int
	 */
	public $attachment_id;

	/**
	 * Original file path.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	public $original_path;

	/**
	 * Converted file path.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	public $converted_path;

	/**
	 * Target format.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	public $format;

	/**
	 * Conversion status.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	public $status;

	/**
	 * Size reduction percentage.
	 *
	 * @since 1.0.0
	 * @var float
	 */
	public $size_reduction;

	/**
	 * Processing time in seconds.
	 *
	 * @since 1.0.0
	 * @var int
	 */
	public $processing_time;

	/**
	 * Error message (if failed).
	 *
	 * @since 1.0.0
	 * @var string
	 */
	public $error_message;

	/**
	 * Creation timestamp.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	public $created_at;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 * @param array $data Record data.
	 */
	public function __construct( $data = [] ) {
		foreach ( $data as $key => $value ) {
			if ( property_exists( $this, $key ) ) {
				$this->$key = $value;
			}
		}
	}

	/**
	 * Check if conversion was successful.
	 *
	 * @since 1.0.0
	 * @return bool True if successful, false otherwise.
	 */
	public function is_successful() {
		return 'success' === $this->status;
	}

	/**
	 * Get formatted size reduction.
	 *
	 * @since 1.0.0
	 * @return string Formatted size reduction percentage.
	 */
	public function get_formatted_size_reduction() {
		return number_format( $this->size_reduction, 1 ) . '%';
	}

	/**
	 * Get formatted processing time.
	 *
	 * @since 1.0.0
	 * @return string Formatted processing time.
	 */
	public function get_formatted_processing_time() {
		if ( $this->processing_time < 60 ) {
			return $this->processing_time . 's';
		}

		$minutes = floor( $this->processing_time / 60 );
		$seconds = $this->processing_time % 60;

		return $minutes . 'm ' . $seconds . 's';
	}

	/**
	 * Get original file size.
	 *
	 * @since 1.0.0
	 * @return int|false File size in bytes or false on failure.
	 */
	public function get_original_file_size() {
		if ( ! file_exists( $this->original_path ) ) {
			return false;
		}

		return filesize( $this->original_path );
	}

	/**
	 * Get converted file size.
	 *
	 * @since 1.0.0
	 * @return int|false File size in bytes or false on failure.
	 */
	public function get_converted_file_size() {
		if ( ! file_exists( $this->converted_path ) ) {
			return false;
		}

		return filesize( $this->converted_path );
	}

	/**
	 * Get formatted original file size.
	 *
	 * @since 1.0.0
	 * @return string Formatted file size.
	 */
	public function get_formatted_original_size() {
		$size = $this->get_original_file_size();
		return $size ? size_format( $size ) : 'Unknown';
	}

	/**
	 * Get formatted converted file size.
	 *
	 * @since 1.0.0
	 * @return string Formatted file size.
	 */
	public function get_formatted_converted_size() {
		$size = $this->get_converted_file_size();
		return $size ? size_format( $size ) : 'Unknown';
	}

	/**
	 * Get attachment title.
	 *
	 * @since 1.0.0
	 * @return string Attachment title.
	 */
	public function get_attachment_title() {
		$attachment = get_post( $this->attachment_id );
		return $attachment ? $attachment->post_title : 'Unknown';
	}

	/**
	 * Get attachment URL.
	 *
	 * @since 1.0.0
	 * @return string|false Attachment URL or false on failure.
	 */
	public function get_attachment_url() {
		return wp_get_attachment_url( $this->attachment_id );
	}
}
