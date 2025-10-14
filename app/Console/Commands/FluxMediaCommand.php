<?php
/**
 * WP-CLI command for Flux Media plugin.
 *
 * @package FluxMedia
 * @since 0.1.0
 */

namespace FluxMedia\App\Console\Commands;

use WP_CLI;
use WP_CLI_Command;
use FluxMedia\App\Services\Logger;
use FluxMedia\App\Services\BulkConverter;
use FluxMedia\App\Services\Settings;

/**
 * WP-CLI command for managing Flux Media conversions.
 *
 * @since 0.1.0
 */
class FluxMediaCommand extends WP_CLI_Command {

    /**
     * Logger instance.
     *
     * @since 0.1.0
     * @var Logger
     */
    private $logger;

    /**
     * Bulk converter instance.
     *
     * @since 0.1.0
     * @var BulkConverter
     */
    private $bulk_converter;

    /**
     * Settings instance.
     *
     * @since 0.1.0
     * @var Settings
     */
    private $settings;

    /**
     * Constructor.
     *
     * @since 0.1.0
     */
    public function __construct() {
        $this->logger = new Logger();
        $this->settings = new Settings();
        $this->bulk_converter = new BulkConverter( $this->logger, $this->settings );
    }

    /**
     * Convert all unconverted media files.
     *
     * ## OPTIONS
     *
     * [--batch-size=<size>]
     * : Number of files to process in each batch.
     * ---
     * default: 10
     * ---
     *
     * ## EXAMPLES
     *
     *     wp flux-media convert-all
     *     wp flux-media convert-all --batch-size=20
     *
     * @since 0.1.0
     * @param array $args Positional arguments.
     * @param array $assoc_args Associative arguments.
     */
    public function convert_all( $args, $assoc_args ) {
        $batch_size = isset( $assoc_args['batch-size'] ) ? (int) $assoc_args['batch-size'] : 10;
        
        WP_CLI::log( "Starting bulk conversion with batch size: {$batch_size}" );
        
        $results = $this->bulk_converter->process_bulk_conversion( $batch_size );
        
        WP_CLI::success( sprintf(
            'Bulk conversion completed. Processed: %d, Converted: %d, Errors: %d',
            $results['processed'],
            $results['converted'],
            $results['errors']
        ) );
    }

    /**
     * Clear all Flux Media data and converted files.
     *
     * ## EXAMPLES
     *
     *     wp flux-media clear-all
     *
     * @since 0.1.0
     * @param array $args Positional arguments.
     * @param array $assoc_args Associative arguments.
     */
    public function clear_all( $args, $assoc_args ) {
        WP_CLI::confirm( 'Are you sure you want to clear all Flux Media data? This will delete all converted files and metadata.' );
        
        // Clear all post meta
        global $wpdb;
        $deleted_meta = $wpdb->query( "DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE '_flux_media_%'" );
        
        // Clear all converted files
        $upload_dir = wp_upload_dir();
        $flux_media_dir = $upload_dir['basedir'] . '/flux-media/';
        
        if ( is_dir( $flux_media_dir ) ) {
            $this->delete_directory( $flux_media_dir );
        }
        
        WP_CLI::success( "Cleared all Flux Media data. Deleted {$deleted_meta} meta entries and converted files." );
    }

    /**
     * Get conversion statistics.
     *
     * ## EXAMPLES
     *
     *     wp flux-media stats
     *
     * @since 0.1.0
     * @param array $args Positional arguments.
     * @param array $assoc_args Associative arguments.
     */
    public function stats( $args, $assoc_args ) {
        global $wpdb;
        
        // Get total converted files
        $total_converted = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = '_flux_media_converted_formats'" );
        
        // Get total file size savings
        $total_savings = $wpdb->get_var( "SELECT SUM(meta_value) FROM {$wpdb->postmeta} WHERE meta_key = '_flux_media_file_size_savings'" );
        
        WP_CLI::log( "Conversion Statistics:" );
        WP_CLI::log( "Total converted files: {$total_converted}" );
        WP_CLI::log( "Total file size savings: " . size_format( $total_savings ?: 0 ) );
    }

    /**
     * Recursively delete a directory.
     *
     * @since 0.1.0
     * @param string $dir Directory path.
     * @return void
     */
    private function delete_directory( $dir ) {
        if ( ! is_dir( $dir ) ) {
            return;
        }
        
        $files = array_diff( scandir( $dir ), [ '.', '..' ] );
        
        foreach ( $files as $file ) {
            $path = $dir . '/' . $file;
            is_dir( $path ) ? $this->delete_directory( $path ) : unlink( $path );
        }
        
        rmdir( $dir );
    }
}
