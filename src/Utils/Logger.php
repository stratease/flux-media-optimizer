<?php
/**
 * Logger utility class.
 *
 * @package FluxMedia
 * @since 1.0.0
 */

namespace FluxMedia\Utils;

use Monolog\Logger as MonologLogger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Formatter\LineFormatter;

/**
 * Logger utility class using Monolog.
 *
 * @since 1.0.0
 */
class Logger {

	/**
	 * Monolog logger instance.
	 *
	 * @since 1.0.0
	 * @var MonologLogger
	 */
	private $logger;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->logger = new MonologLogger( 'flux-media' );
		$this->setup_handlers();
	}

	/**
	 * Setup log handlers.
	 *
	 * @since 1.0.0
	 */
	private function setup_handlers() {
		$log_dir = WP_CONTENT_DIR . '/uploads/flux-media-logs';
		
		// Create log directory if it doesn't exist.
		if ( ! file_exists( $log_dir ) ) {
			wp_mkdir_p( $log_dir );
		}

		// Rotating file handler for general logs.
		$file_handler = new RotatingFileHandler( $log_dir . '/flux-media.log', 7, MonologLogger::INFO );
		$file_handler->setFormatter( new LineFormatter( "[%datetime%] %channel%.%level_name%: %message% %context%\n" ) );
		$this->logger->pushHandler( $file_handler );

		// Error handler for errors only.
		$error_handler = new RotatingFileHandler( $log_dir . '/flux-media-error.log', 7, MonologLogger::ERROR );
		$error_handler->setFormatter( new LineFormatter( "[%datetime%] %channel%.%level_name%: %message% %context%\n" ) );
		$this->logger->pushHandler( $error_handler );
	}

	/**
	 * Log debug message.
	 *
	 * @since 1.0.0
	 * @param string $message Log message.
	 * @param array  $context Additional context.
	 */
	public function debug( $message, $context = [] ) {
		$this->logger->debug( $message, $context );
	}

	/**
	 * Log info message.
	 *
	 * @since 1.0.0
	 * @param string $message Log message.
	 * @param array  $context Additional context.
	 */
	public function info( $message, $context = [] ) {
		$this->logger->info( $message, $context );
	}

	/**
	 * Log warning message.
	 *
	 * @since 1.0.0
	 * @param string $message Log message.
	 * @param array  $context Additional context.
	 */
	public function warning( $message, $context = [] ) {
		$this->logger->warning( $message, $context );
	}

	/**
	 * Log error message.
	 *
	 * @since 1.0.0
	 * @param string $message Log message.
	 * @param array  $context Additional context.
	 */
	public function error( $message, $context = [] ) {
		$this->logger->error( $message, $context );
	}

	/**
	 * Log critical message.
	 *
	 * @since 1.0.0
	 * @param string $message Log message.
	 * @param array  $context Additional context.
	 */
	public function critical( $message, $context = [] ) {
		$this->logger->critical( $message, $context );
	}
}
