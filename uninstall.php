<?php
/**
 * Uninstall Flux Media Optimizer without loading the full plugin bootstrap.
 *
 * WordPress includes this file instead of the main plugin file, so Action
 * Scheduler and FFmpeg are not loaded. Do not call WP_Filesystem() here.
 *
 * @package FluxMedia
 * @since 4.4.1
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

$flux_media_optimizer_dir = dirname( __FILE__ ) . '/';

if ( file_exists( $flux_media_optimizer_dir . 'vendor/autoload.php' ) ) {
	require_once $flux_media_optimizer_dir . 'vendor/autoload.php';
}

if ( file_exists( $flux_media_optimizer_dir . 'vendor-prefixed/autoload.php' ) ) {
	require_once $flux_media_optimizer_dir . 'vendor-prefixed/autoload.php';
}

if ( class_exists( \FluxMedia\App\Services\UninstallService::class ) ) {
	\FluxMedia\App\Services\UninstallService::run();
}
