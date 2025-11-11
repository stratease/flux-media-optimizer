<?php
/**
 * Custom autoloader for FFmpeg classes from vendor-prefixed directory.
 *
 * @package FluxMedia
 * @since 0.1.0
 */

namespace FluxMedia\App\Services;

/**
 * Custom autoloader for FFmpeg classes.
 *
 * @since 0.1.0
 */
class FFmpegAutoloader {

	/**
	 * Initialize the autoloader.
	 *
	 * @since 0.1.0
	 */
	public static function init() {
		if ( ! defined( 'FLUX_MEDIA_PLUGIN_DIR' ) ) {
			return;
		}

		// Register the autoloader
		spl_autoload_register( [ __CLASS__, 'autoload' ] );
	}

	/**
	 * Autoload FFmpeg classes.
	 *
	 * Handles the complex directory structure of the php-ffmpeg library
	 * where some classes are in the root FFMpeg/ directory and others
	 * are in subdirectories like FFMpeg/Media/, FFMpeg/Exception/, etc.
	 *
	 * @since 0.1.0
	 * @param string $class_name The class name to load.
	 */
	public static function autoload( $class_name ) {

		if(self::load_class( $class_name , 'FluxMedia\\FFMpeg\\', FLUX_MEDIA_PLUGIN_DIR . 'vendor-prefixed/php-ffmpeg/php-ffmpeg/src/' )) {
            return;
        }

        if(self::load_class( $class_name , 'FluxMedia\\', FLUX_MEDIA_PLUGIN_DIR . 'vendor-prefixed/alchemy/binary-driver/src/' )) {
            return;
        }

        if(self::load_class( $class_name , 'FluxMedia\\', FLUX_MEDIA_PLUGIN_DIR . 'vendor-prefixed/neutron/temporary-filesystem/src/' )) {
            return;
        }
	}

    public static function load_class( $class_name, $prefix, $base_dir ) {
        // Only handle FluxMedia\FFMpeg namespace
		if ( strpos( $class_name, $prefix ) !== 0 ) {
			return false;
		}

		// Convert namespace to file path
		$relative_path = str_replace( $prefix, '', $class_name );
		$relative_path = str_replace( '\\', '/', $relative_path );
		
		// Handle special case where class name matches directory name
		// e.g., FluxMedia\FFMpeg\FFMpeg -> FFMpeg/FFMpeg.php
		if ( in_array($relative_path, ['FFMpeg', 'Alchemy'], true) ) {
			$file_path = $base_dir . $relative_path . '/' . basename( $relative_path ) . '.php';
		} else {
			// For other classes, try both direct path and FFMpeg/ subdirectory
			$file_path = $base_dir . $relative_path . '.php';
			if ( ! file_exists( $file_path ) ) {
				$file_path = $base_dir . 'FFMpeg/' . $relative_path . '.php';
			}
		}

		// Check if file exists and require it
		if ( file_exists( $file_path ) ) {
			require_once $file_path;

            return true;
		}
        

        return false;
    }
}
