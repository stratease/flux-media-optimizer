<?php
/**
 * Database handler for Monolog logger.
 *
 * @package FluxMedia
 * @since 0.1.0
 */

namespace FluxMedia\App\Services;

use FluxMedia\Monolog\Handler\AbstractProcessingHandler;

/**
 * Database handler for storing logs in WordPress database.
 *
 * @since 0.1.0
 */
class DatabaseHandler extends AbstractProcessingHandler {

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
	 * @param int    $level  The minimum logging level at which this handler will be triggered.
	 * @param bool   $bubble Whether the messages that are handled can bubble up the stack or not.
	 */
	public function __construct( $level = \FluxMedia\Monolog\Logger::DEBUG, $bubble = true ) {
		global $wpdb;
		$this->table_name = $wpdb->prefix . 'flux_media_optimizer_logs';
		parent::__construct( $level, $bubble );
	}

	/**
	 * Write the log record to the database.
	 *
	 * @since 0.1.0
	 * @param array $record The log record to write.
	 */
	protected function write( array $record ): void {
		global $wpdb;

		// Check if logging is disabled
		$options = get_option( 'flux_media_optimizer_options', [] );
		if ( ! ( $options['enable_logging'] ?? true ) ) {
			return;
		}

		$wpdb->insert(
			$this->table_name,
			[
				'level'     => $record['level_name'],
				'message'   => $record['message'],
				'context'   => ! empty( $record['context'] ) ? wp_json_encode( $record['context'] ) : null,
				'created_at' => $record['datetime']->format( 'Y-m-d H:i:s' ),
			],
			[
				'%s',
				'%s',
				'%s',
				'%s',
			]
		);
	}
}
