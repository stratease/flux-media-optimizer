<?php
/**
 * Logger interface for consistent logging across the application.
 *
 * @package FluxMedia
 * @since 0.1.0
 */

namespace FluxMedia\App\Services;

/**
 * Logger interface for consistent logging across the application.
 *
 * @since 0.1.0
 */
interface LoggerInterface {

    /**
     * Log an error message.
     *
     * @since 0.1.0
     * @param string $message The error message.
     * @param array  $context Additional context data.
     * @return void
     */
    public function error( $message, $context = [] );

    /**
     * Log a warning message.
     *
     * @since 0.1.0
     * @param string $message The warning message.
     * @param array  $context Additional context data.
     * @return void
     */
    public function warning( $message, $context = [] );

    /**
     * Log an info message.
     *
     * @since 0.1.0
     * @param string $message The info message.
     * @param array  $context Additional context data.
     * @return void
     */
    public function info( $message, $context = [] );

    /**
     * Log a debug message.
     *
     * @since 0.1.0
     * @param string $message The debug message.
     * @param array  $context Additional context data.
     * @return void
     */
    public function debug( $message, $context = [] );
}
