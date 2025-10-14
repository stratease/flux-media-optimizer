<?php
/**
 * Main plugin class.
 *
 * @package FluxMedia
 * @since 0.1.0
 */

namespace FluxMedia\App;

use FluxMedia\App\Services\Logger;
use FluxMedia\App\Services\WordPressProvider;
use FluxMedia\App\Services\Settings;
use FluxMedia\App\Services\ImageConverter;
use FluxMedia\App\Services\VideoConverter;
use FluxMedia\App\Services\FormatSupportDetector;
use FluxMedia\App\Services\ProcessorDetector;
use FluxMedia\App\Services\QuotaManager;
use FluxMedia\App\Http\Controllers\AdminController;
use FluxMedia\App\Http\Controllers\OptionsController;
use FluxMedia\App\Http\Controllers\StatusController;
use FluxMedia\App\Http\Controllers\ConversionsController;
use FluxMedia\App\Http\Controllers\LogsController;
use FluxMedia\App\Services\ConversionTracker;
use FluxMedia\App\Services\LogsService;
use FluxMedia\App\Services\Database;

/**
 * Main plugin class that initializes all components.
 *
 * @since 0.1.0
 */
class Plugin {

    /**
     * Logger instance.
     *
     * @since 0.1.0
     * @var Logger
     */
    private $logger;

    /**
     * WordPress provider instance.
     *
     * @since 0.1.0
     * @var WordPressProvider
     */
    private $wordpress_provider;

    /**
     * Settings instance.
     *
     * @since 0.1.0
     * @var Settings
     */
    private $settings;

    /**
     * Image converter instance.
     *
     * @since 0.1.0
     * @var ImageConverter
     */
    private $image_converter;

    /**
     * Video converter instance.
     *
     * @since 0.1.0
     * @var VideoConverter
     */
    private $video_converter;

    /**
     * Initialize the plugin.
     *
     * @since 0.1.0
     * @return void
     */
    public function init() {
        // Ensure database tables exist
        Database::maybe_update_database();
        
        // Initialize logger first
        $this->logger = new Logger();
        
        // Initialize settings
        $this->settings = new Settings();
        
        // Initialize converters
        $this->image_converter = new ImageConverter( $this->logger );
        $this->video_converter = new VideoConverter( $this->logger );
        
        // Initialize WordPress provider
        $this->wordpress_provider = new WordPressProvider( $this->image_converter, $this->video_converter );
        
        // Register WordPress hooks
        $this->wordpress_provider->register_hooks();
        
        // Initialize admin functionality
        $this->init_admin();
        
        // Initialize REST API
        $this->init_rest_api();
        
        $this->logger->info( 'Flux Media plugin initialized successfully' );
    }

    /**
     * Initialize admin functionality.
     *
     * @since 0.1.0
     * @return void
     */
    private function init_admin() {
        $admin_controller = new AdminController( $this->settings );
        $admin_controller->init();
    }

    /**
     * Initialize REST API.
     *
     * @since 0.1.0
     * @return void
     */
    private function init_rest_api() {
        add_action( 'rest_api_init', [ $this, 'register_rest_routes' ] );
    }

    /**
     * Register REST API routes.
     *
     * @since 0.1.0
     * @return void
     */
    public function register_rest_routes() {
        // Initialize detectors and services
        $processor_detector = new ProcessorDetector();
        $format_detector = new FormatSupportDetector( $processor_detector );
        $quota_manager = new QuotaManager( $this->logger );
        $conversion_tracker = new ConversionTracker( $this->logger, $quota_manager );
        $logs_service = new LogsService();

        // Register controllers
        $options_controller = new OptionsController( $this->settings );
        $status_controller = new StatusController( $format_detector, $processor_detector, $quota_manager );
        $conversions_controller = new ConversionsController( $conversion_tracker );
        $logs_controller = new LogsController( $logs_service );

        $options_controller->register_routes();
        $status_controller->register_routes();
        $conversions_controller->register_routes();
        $logs_controller->register_routes();
    }

    /**
     * Get the logger instance.
     *
     * @since 0.1.0
     * @return Logger
     */
    public function get_logger() {
        return $this->logger;
    }

    /**
     * Get the WordPress provider instance.
     *
     * @since 0.1.0
     * @return WordPressProvider
     */
    public function get_wordpress_provider() {
        return $this->wordpress_provider;
    }

    /**
     * Get the settings instance.
     *
     * @since 0.1.0
     * @return Settings
     */
    public function get_settings() {
        return $this->settings;
    }

    /**
     * Get the image converter instance.
     *
     * @since 0.1.0
     * @return ImageConverter
     */
    public function get_image_converter() {
        return $this->image_converter;
    }

    /**
     * Get the video converter instance.
     *
     * @since 0.1.0
     * @return VideoConverter
     */
    public function get_video_converter() {
        return $this->video_converter;
    }
}
