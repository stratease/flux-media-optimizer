<?php
/**
 * Conversion tracking service.
 *
 * @package FluxMedia
 * @since 1.0.0
 */

namespace FluxMedia\Services;

use FluxMedia\Core\Database;
use FluxMedia\Models\ConversionRecord;

/**
 * Service for tracking media conversions.
 *
 * @since 1.0.0
 */
class ConversionTracker {

	/**
	 * Database instance.
	 *
	 * @since 1.0.0
	 * @var Database
	 */
	private $database;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->database = new Database();
	}

	/**
	 * Record a successful conversion.
	 *
	 * @since 1.0.0
	 * @param int    $attachment_id WordPress attachment ID.
	 * @param string $original_path Original file path.
	 * @param string $converted_path Converted file path.
	 * @param string $format Target format (webp, avif, av1, webm).
	 * @param float  $size_reduction Size reduction percentage.
	 * @param int    $processing_time Processing time in seconds.
	 * @return bool True on success, false on failure.
	 */
	public function record_success( $attachment_id, $original_path, $converted_path, $format, $size_reduction, $processing_time ) {
		global $wpdb;

		$table_name = $this->database->get_table_name( 'conversions' );

		$result = $wpdb->insert(
			$table_name,
			[
				'attachment_id' => $attachment_id,
				'original_path' => $original_path,
				'converted_path' => $converted_path,
				'format' => $format,
				'status' => 'success',
				'size_reduction' => $size_reduction,
				'processing_time' => $processing_time,
				'created_at' => current_time( 'mysql' ),
			],
			[
				'%d',
				'%s',
				'%s',
				'%s',
				'%s',
				'%f',
				'%d',
				'%s',
			]
		);

		return false !== $result;
	}

	/**
	 * Record a failed conversion.
	 *
	 * @since 1.0.0
	 * @param int    $attachment_id WordPress attachment ID.
	 * @param string $original_path Original file path.
	 * @param string $format Target format (webp, avif, av1, webm).
	 * @param string $error_message Error message.
	 * @return bool True on success, false on failure.
	 */
	public function record_failure( $attachment_id, $original_path, $format, $error_message ) {
		global $wpdb;

		$table_name = $this->database->get_table_name( 'conversions' );

		$result = $wpdb->insert(
			$table_name,
			[
				'attachment_id' => $attachment_id,
				'original_path' => $original_path,
				'converted_path' => '',
				'format' => $format,
				'status' => 'failed',
				'size_reduction' => 0.0,
				'processing_time' => 0,
				'error_message' => $error_message,
				'created_at' => current_time( 'mysql' ),
			],
			[
				'%d',
				'%s',
				'%s',
				'%s',
				'%s',
				'%f',
				'%d',
				'%s',
				'%s',
			]
		);

		return false !== $result;
	}

	/**
	 * Get conversion statistics.
	 *
	 * @since 1.0.0
	 * @param array $filters Optional filters.
	 * @return array Conversion statistics.
	 */
	public function get_statistics( $filters = [] ) {
		global $wpdb;

		$table_name = $this->database->get_table_name( 'conversions' );
		$where_clause = '1=1';
		$where_values = [];

		// Apply filters.
		if ( ! empty( $filters['format'] ) ) {
			$where_clause .= ' AND format = %s';
			$where_values[] = $filters['format'];
		}

		if ( ! empty( $filters['status'] ) ) {
			$where_clause .= ' AND status = %s';
			$where_values[] = $filters['status'];
		}

		if ( ! empty( $filters['date_from'] ) ) {
			$where_clause .= ' AND created_at >= %s';
			$where_values[] = $filters['date_from'];
		}

		if ( ! empty( $filters['date_to'] ) ) {
			$where_clause .= ' AND created_at <= %s';
			$where_values[] = $filters['date_to'];
		}

		// Get total conversions.
		$total_query = "SELECT COUNT(*) FROM {$table_name} WHERE {$where_clause}";
		if ( ! empty( $where_values ) ) {
			$total_query = $wpdb->prepare( $total_query, $where_values );
		}
		$total_conversions = (int) $wpdb->get_var( $total_query );

		// Get successful conversions.
		$success_query = "SELECT COUNT(*) FROM {$table_name} WHERE {$where_clause} AND status = 'success'";
		if ( ! empty( $where_values ) ) {
			$success_query = $wpdb->prepare( $success_query, $where_values );
		}
		$successful_conversions = (int) $wpdb->get_var( $success_query );

		// Get failed conversions.
		$failed_conversions = $total_conversions - $successful_conversions;

		// Get average size reduction.
		$avg_reduction_query = "SELECT AVG(size_reduction) FROM {$table_name} WHERE {$where_clause} AND status = 'success'";
		if ( ! empty( $where_values ) ) {
			$avg_reduction_query = $wpdb->prepare( $avg_reduction_query, $where_values );
		}
		$average_size_reduction = (float) $wpdb->get_var( $avg_reduction_query );

		// Get total space saved.
		$space_saved_query = "SELECT SUM(size_reduction) FROM {$table_name} WHERE {$where_clause} AND status = 'success'";
		if ( ! empty( $where_values ) ) {
			$space_saved_query = $wpdb->prepare( $space_saved_query, $where_values );
		}
		$total_space_saved = (float) $wpdb->get_var( $space_saved_query );

		// Get conversions by format.
		$format_query = "SELECT format, COUNT(*) as count FROM {$table_name} WHERE {$where_clause} GROUP BY format";
		if ( ! empty( $where_values ) ) {
			$format_query = $wpdb->prepare( $format_query, $where_values );
		}
		$conversions_by_format = $wpdb->get_results( $format_query, ARRAY_A );

		return [
			'total_conversions' => $total_conversions,
			'successful_conversions' => $successful_conversions,
			'failed_conversions' => $failed_conversions,
			'success_rate' => $total_conversions > 0 ? ( $successful_conversions / $total_conversions ) * 100 : 0,
			'average_size_reduction' => $average_size_reduction,
			'total_space_saved' => $total_space_saved,
			'conversions_by_format' => $conversions_by_format,
		];
	}

	/**
	 * Get recent conversions.
	 *
	 * @since 1.0.0
	 * @param int $limit Number of records to retrieve.
	 * @return array Recent conversion records.
	 */
	public function get_recent_conversions( $limit = 10 ) {
		global $wpdb;

		$table_name = $this->database->get_table_name( 'conversions' );

		$query = $wpdb->prepare(
			"SELECT * FROM {$table_name} ORDER BY created_at DESC LIMIT %d",
			$limit
		);

		$results = $wpdb->get_results( $query, ARRAY_A );

		return array_map( function( $row ) {
			return new ConversionRecord( $row );
		}, $results );
	}

	/**
	 * Clean up old conversion records.
	 *
	 * @since 1.0.0
	 * @param int $days Number of days to keep records.
	 * @return int Number of records deleted.
	 */
	public function cleanup_old_records( $days = 30 ) {
		global $wpdb;

		$table_name = $this->database->get_table_name( 'conversions' );
		$cutoff_date = date( 'Y-m-d H:i:s', strtotime( "-{$days} days" ) );

		$deleted = $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$table_name} WHERE created_at < %s",
				$cutoff_date
			)
		);

		return (int) $deleted;
	}
}
