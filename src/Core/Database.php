<?php
/**
 * Database management class.
 *
 * @package FluxMedia
 * @since 1.0.0
 */

namespace FluxMedia\Core;

/**
 * Database management for Flux Media plugin.
 *
 * @since 1.0.0
 */
class Database {

	/**
	 * Database version.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	const DB_VERSION = '1.0.0';

	/**
	 * Create database tables.
	 *
	 * @since 1.0.0
	 */
	public static function create_tables() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		// Conversions table.
		$conversions_table = self::get_table_name( 'conversions' );
		$conversions_sql = "CREATE TABLE {$conversions_table} (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			attachment_id bigint(20) NOT NULL,
			original_path varchar(500) NOT NULL,
			converted_path varchar(500) NOT NULL DEFAULT '',
			format varchar(20) NOT NULL,
			status varchar(20) NOT NULL DEFAULT 'pending',
			size_reduction decimal(5,2) NOT NULL DEFAULT 0.00,
			processing_time int(11) NOT NULL DEFAULT 0,
			error_message text,
			created_at datetime NOT NULL,
			PRIMARY KEY (id),
			KEY attachment_id (attachment_id),
			KEY format (format),
			KEY status (status),
			KEY created_at (created_at)
		) {$charset_collate};";

		// Settings table.
		$settings_table = self::get_table_name( 'settings' );
		$settings_sql = "CREATE TABLE {$settings_table} (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			setting_key varchar(100) NOT NULL,
			setting_value longtext,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY (id),
			UNIQUE KEY setting_key (setting_key)
		) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $conversions_sql );
		dbDelta( $settings_sql );

		// Update database version.
		update_option( 'flux_media_db_version', self::DB_VERSION );
	}

	/**
	 * Get table name with prefix.
	 *
	 * @since 1.0.0
	 * @param string $table Table name without prefix.
	 * @return string Full table name.
	 */
	public static function get_table_name( $table ) {
		global $wpdb;
		return $wpdb->prefix . 'flux_media_' . $table;
	}

	/**
	 * Check if database is up to date.
	 *
	 * @since 1.0.0
	 * @return bool True if up to date, false otherwise.
	 */
	public static function is_up_to_date() {
		$current_version = get_option( 'flux_media_db_version', '0.0.0' );
		return version_compare( $current_version, self::DB_VERSION, '>=' );
	}

	/**
	 * Drop database tables.
	 *
	 * @since 1.0.0
	 */
	public static function drop_tables() {
		global $wpdb;

		$tables = [
			self::get_table_name( 'conversions' ),
			self::get_table_name( 'settings' ),
		];

		foreach ( $tables as $table ) {
			$wpdb->query( "DROP TABLE IF EXISTS {$table}" );
		}

		delete_option( 'flux_media_db_version' );
	}
}
