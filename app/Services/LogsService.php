<?php
/**
 * Logs service for retrieving logs from database.
 *
 * @package FluxMedia
 * @since 0.1.0
 */

namespace FluxMedia\App\Services;

/**
 * Handles log retrieval from the database.
 *
 * @since 0.1.0
 */
class LogsService {

	/**
	 * Database table name.
	 *
	 * @since 0.1.0
	 * @var string
	 */
	private $table_name;

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 */
	public function __construct() {
		global $wpdb;
		$this->table_name = $wpdb->prefix . 'flux_media_optimizer_logs';
	}

	/**
	 * Get logs with pagination and filtering.
	 *
	 * @since 0.1.0
	 * @param array $args Query arguments.
	 * @return array Logs data with pagination info.
	 */
	public function get_logs( $args = [] ) {
		global $wpdb;

		$defaults = [
			'page' => 1,
			'per_page' => 20,
			'level' => '',
			'search' => '',
			'orderby' => 'created_at',
			'order' => 'DESC',
		];

		$args = wp_parse_args( $args, $defaults );

		// Build WHERE clause
		$where_conditions = [];
		$where_values = [];

		if ( ! empty( $args['level'] ) ) {
			$where_conditions[] = 'level = %s';
			$where_values[] = $args['level'];
		}

		if ( ! empty( $args['search'] ) ) {
			$where_conditions[] = '(message LIKE %s OR context LIKE %s)';
			$search_term = '%' . $wpdb->esc_like( $args['search'] ) . '%';
			$where_values[] = $search_term;
			$where_values[] = $search_term;
		}

		$where_clause = ! empty( $where_conditions ) ? 'WHERE ' . implode( ' AND ', $where_conditions ) : '';

		// Get total count
		if ( ! empty( $where_values ) ) {
			$total = (int) $wpdb->get_var( $wpdb->prepare( 
				"SELECT COUNT(*) FROM `".esc_sql($this->table_name)."` {$where_clause}", 
				$where_values 
			) );
		} else {
			$total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM `".esc_sql($this->table_name)."`" );
		}

		// Calculate pagination
		$offset = ( $args['page'] - 1 ) * $args['per_page'];
		$total_pages = ceil( $total / $args['per_page'] );

		// Get logs
		$orderby = sanitize_sql_orderby( $args['orderby'] . ' ' . $args['order'] );
		$query_values = array_merge( $where_values, [ $args['per_page'], $offset ] );
		$logs = $wpdb->get_results( $wpdb->prepare( 
			"SELECT id, level, message, context, created_at FROM `".esc_sql($this->table_name)."` {$where_clause} ORDER BY {$orderby} LIMIT %d OFFSET %d", 
			$query_values 
		), ARRAY_A );

		// Process logs
		foreach ( $logs as &$log ) {
			$log['context'] = ! empty( $log['context'] ) ? json_decode( $log['context'], true ) : null;
		}

		return [
			'data' => $logs,
			'total' => $total,
			'page' => $args['page'],
			'per_page' => $args['per_page'],
			'total_pages' => $total_pages,
		];
	}

	/**
	 * Get log levels available in the database.
	 *
	 * @since 0.1.0
	 * @return array Array of log levels.
	 */
	public function get_log_levels() {
		global $wpdb;

		$levels = $wpdb->get_col( "SELECT DISTINCT level FROM `".esc_sql($this->table_name)."` ORDER BY level" );
		return $levels ?: [];
	}

	/**
	 * Clear old logs.
	 *
	 * @since 0.1.0
	 * @param int $days Number of days to keep logs.
	 * @return int Number of logs deleted.
	 */
	public function clear_old_logs( $days = 30 ) {
		global $wpdb;

		$cutoff_date = gmdate( 'Y-m-d H:i:s', strtotime( "-{$days} days" ) );
		
		$deleted = $wpdb->query( $wpdb->prepare(
			"DELETE FROM `".esc_sql($this->table_name)."` WHERE created_at < %s",
			$cutoff_date
		) );

		return $deleted ?: 0;
	}
}
