<?php
/**
 * Uninstall script for Flux Media Optimizer plugin.
 *
 * This file is executed when the plugin is uninstalled (deleted) from WordPress.
 * It cleans up all plugin data including custom tables, options, and files.
 *
 * @package FluxMedia
 * @since 0.1.0
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Load WordPress functions.
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/plugin.php';

/**
 * Clean up plugin data on uninstall.
 *
 * @since 0.1.0
 */
function flux_media_optimizer_uninstall() {
	global $wpdb;

	// Initialize WordPress filesystem.
	$wp_filesystem = null;
	if ( ! function_exists( 'WP_Filesystem' ) ) {
		require_once ABSPATH . 'wp-admin/includes/file.php';
	}
	WP_Filesystem();

	// Remove custom database tables.
	$tables = [
		$wpdb->prefix . 'flux_media_optimizer_conversions',
		$wpdb->prefix . 'flux_media_optimizer_logs',
		$wpdb->prefix . 'flux_media_optimizer_settings',
	];

	foreach ( $tables as $table ) {
		$wpdb->query( $wpdb->prepare( "DROP TABLE IF EXISTS %s", $table ) );
	}

	// Remove plugin options.
	$options = [
		'flux_media_optimizer_settings',
		'flux_media_optimizer_version',
		'flux_media_optimizer_activation_redirect',
	];

	foreach ( $options as $option ) {
		delete_option( $option );
		delete_site_option( $option );
	}

	// Remove post meta for all attachments.
	$wpdb->query(
		"DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE '_flux_media_optimizer_%'"
	);

	// Remove converted files from uploads directory using WordPress filesystem.
	$upload_dir = wp_upload_dir();
	$flux_media_optimizer_dir = $upload_dir['basedir'] . '/flux-media-optimizer-converted';

	if ( is_dir( $flux_media_optimizer_dir ) ) {

		// Use WordPress filesystem to remove directory and all contents.
		global $wp_filesystem;
		if ( $wp_filesystem && $wp_filesystem->is_dir( $flux_media_optimizer_dir ) ) {
			$wp_filesystem->rmdir( $flux_media_optimizer_dir, true );
		} else {
			// Fallback: Remove files individually using wp_delete_file().
			$files = glob( $flux_media_optimizer_dir . '/*' );
			foreach ( $files as $file ) {
				if ( is_file( $file ) ) {
					wp_delete_file( $file );
				}
			}
		}
	}

	// Clear any scheduled cron jobs.
	wp_clear_scheduled_hook( 'flux_media_optimizer_cleanup' );
	wp_clear_scheduled_hook( 'flux_media_optimizer_bulk_convert' );

	// Remove any transients.
	$wpdb->query(
		"DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_flux_media_optimizer_%' OR option_name LIKE '_transient_timeout_flux_media_optimizer_%'"
	);
}

// Run the uninstall function.
flux_media_optimizer_uninstall();
