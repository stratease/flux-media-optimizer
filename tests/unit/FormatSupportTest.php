<?php
/**
 * Unit tests for format support validation.
 *
 * @package FluxMedia\Tests\Unit
 * @since 0.1.0
 */

namespace FluxMedia\Tests\Unit;

use FluxMedia\App\Services\FormatSupportDetector;
use FluxMedia\App\Services\ProcessorDetector;
use FluxMedia\App\Services\ProcessorTypes;
use PHPUnit\Framework\TestCase;

/**
 * Format support validation tests.
 *
 * @since 0.1.0
 */
class FormatSupportTest extends TestCase {

    /**
     * Format support detector instance.
     *
     * @since 0.1.0
     * @var FormatSupportDetector
     */
    private $format_detector;

    /**
     * Processor detector instance.
     *
     * @since 0.1.0
     * @var ProcessorDetector
     */
    private $processor_detector;

    /**
     * Set up test environment.
     *
     * @since 0.1.0
     * @return void
     */
    protected function setUp(): void {
        $this->processor_detector = new ProcessorDetector();
        $this->format_detector = new FormatSupportDetector( $this->processor_detector );
    }

    /**
     * Test WebP format support availability.
     *
     * @since 0.1.0
     * @return void
     */
    public function testWebPSupportAvailability() {
        // Arrange
        $webp_supported = $this->format_detector->supports_webp();
        
        // Get low-level detection results
        $gd_webp_support = $this->get_gd_webp_support();
        $imagick_webp_support = $this->get_imagick_webp_support();
        
        // Act & Assert
        if ( $webp_supported ) {
            echo "WebP support: AVAILABLE\n";
            echo "  GD WebP support: " . ( $gd_webp_support ? 'YES' : 'NO' ) . "\n";
            echo "  Imagick WebP support: " . ( $imagick_webp_support ? 'YES' : 'NO' ) . "\n";
            $this->assertTrue( $webp_supported, 'WebP format should be supported' );
            
            // Validate that at least one processor supports WebP
            $this->assertTrue( 
                $gd_webp_support || $imagick_webp_support, 
                'At least one processor should support WebP if format is supported' 
            );
        } else {
            echo "WebP support: NOT AVAILABLE\n";
            echo "  GD WebP support: " . ( $gd_webp_support ? 'YES' : 'NO' ) . "\n";
            echo "  Imagick WebP support: " . ( $imagick_webp_support ? 'YES' : 'NO' ) . "\n";
            $this->assertFalse( $webp_supported, 'WebP format should not be supported' );
            
            // Validate that no processor supports WebP
            $this->assertFalse( 
                $gd_webp_support || $imagick_webp_support, 
                'No processor should support WebP if format is not supported' 
            );
        }
    }

    /**
     * Test AVIF format support availability.
     *
     * @since 0.1.0
     * @return void
     */
    public function testAVIFSupportAvailability() {
        // Arrange
        $avif_supported = $this->format_detector->supports_avif();
        
        // Get low-level detection results
        $gd_avif_support = $this->get_gd_avif_support();
        $imagick_avif_support = $this->get_imagick_avif_support();
        
        // Act & Assert
        if ( $avif_supported ) {
            echo "AVIF support: AVAILABLE\n";
            echo "  GD AVIF support: " . ( $gd_avif_support ? 'YES' : 'NO' ) . "\n";
            echo "  Imagick AVIF support: " . ( $imagick_avif_support ? 'YES' : 'NO' ) . "\n";
            $this->assertTrue( $avif_supported, 'AVIF format should be supported' );
            
            // Validate that at least one processor supports AVIF
            $this->assertTrue( 
                $gd_avif_support || $imagick_avif_support, 
                'At least one processor should support AVIF if format is supported' 
            );
        } else {
            echo "AVIF support: NOT AVAILABLE\n";
            echo "  GD AVIF support: " . ( $gd_avif_support ? 'YES' : 'NO' ) . "\n";
            echo "  Imagick AVIF support: " . ( $imagick_avif_support ? 'YES' : 'NO' ) . "\n";
            $this->assertFalse( $avif_supported, 'AVIF format should not be supported' );
            
            // Validate that no processor supports AVIF
            $this->assertFalse( 
                $gd_avif_support || $imagick_avif_support, 
                'No processor should support AVIF if format is not supported' 
            );
        }
    }

    /**
     * Test AV1 video format support availability.
     *
     * @since 0.1.0
     * @return void
     */
    public function testAV1SupportAvailability() {
        // Arrange
        $av1_supported = $this->format_detector->supports_av1();
        
        // Get low-level detection results
        $ffmpeg_available = $this->get_ffmpeg_availability();
        $ffmpeg_av1_support = $this->get_ffmpeg_av1_support();
        
        // Act & Assert
        if ( $av1_supported ) {
            echo "AV1 support: AVAILABLE\n";
            echo "  FFmpeg available: " . ( $ffmpeg_available ? 'YES' : 'NO' ) . "\n";
            echo "  FFmpeg AV1 support: " . ( $ffmpeg_av1_support ? 'YES' : 'NO' ) . "\n";
            $this->assertTrue( $av1_supported, 'AV1 format should be supported' );
            
            // Validate that FFmpeg supports AV1
            $this->assertTrue( 
                $ffmpeg_available && $ffmpeg_av1_support, 
                'FFmpeg should be available and support AV1 if format is supported' 
            );
        } else {
            echo "AV1 support: NOT AVAILABLE\n";
            echo "  FFmpeg available: " . ( $ffmpeg_available ? 'YES' : 'NO' ) . "\n";
            echo "  FFmpeg AV1 support: " . ( $ffmpeg_av1_support ? 'YES' : 'NO' ) . "\n";
            $this->assertFalse( $av1_supported, 'AV1 format should not be supported' );
            
            // Validate that FFmpeg doesn't support AV1
            $this->assertFalse( 
                $ffmpeg_available && $ffmpeg_av1_support, 
                'FFmpeg should not support AV1 if format is not supported' 
            );
        }
    }

    /**
     * Test WebM video format support availability.
     *
     * @since 0.1.0
     * @return void
     */
    public function testWebMSupportAvailability() {
        // Arrange
        $webm_supported = $this->format_detector->supports_webm();
        
        // Get low-level detection results
        $ffmpeg_available = $this->get_ffmpeg_availability();
        $ffmpeg_webm_support = $this->get_ffmpeg_webm_support();
        
        // Act & Assert
        if ( $webm_supported ) {
            echo "WebM support: AVAILABLE\n";
            echo "  FFmpeg available: " . ( $ffmpeg_available ? 'YES' : 'NO' ) . "\n";
            echo "  FFmpeg WebM support: " . ( $ffmpeg_webm_support ? 'YES' : 'NO' ) . "\n";
            $this->assertTrue( $webm_supported, 'WebM format should be supported' );
            
            // Validate that FFmpeg supports WebM
            $this->assertTrue( 
                $ffmpeg_available && $ffmpeg_webm_support, 
                'FFmpeg should be available and support WebM if format is supported' 
            );
        } else {
            echo "WebM support: NOT AVAILABLE\n";
            echo "  FFmpeg available: " . ( $ffmpeg_available ? 'YES' : 'NO' ) . "\n";
            echo "  FFmpeg WebM support: " . ( $ffmpeg_webm_support ? 'YES' : 'NO' ) . "\n";
            $this->assertFalse( $webm_supported, 'WebM format should not be supported' );
            
            // Validate that FFmpeg doesn't support WebM
            $this->assertFalse( 
                $ffmpeg_available && $ffmpeg_webm_support, 
                'FFmpeg should not support WebM if format is not supported' 
            );
        }
    }

    /**
     * Test processor detection and capabilities.
     *
     * @since 0.1.0
     * @return void
     */
    public function testProcessorDetection() {
        // Arrange
        $image_processors = $this->processor_detector->get_available_image_processors();
        $video_processors = $this->processor_detector->get_available_video_processors();
        
        // Act & Assert
        echo "Available image processors: " . implode( ', ', array_keys( $image_processors ) ) . "\n";
        echo "Available video processors: " . implode( ', ', array_keys( $video_processors ) ) . "\n";
        
        // Test best processor selection
        $best_webp_processor = $this->processor_detector->get_best_image_processor( 'webp' );
        $best_avif_processor = $this->processor_detector->get_best_image_processor( 'avif' );
        $best_av1_processor = $this->processor_detector->get_best_video_processor( 'av1' );
        $best_webm_processor = $this->processor_detector->get_best_video_processor( 'webm' );
        
        echo "Best WebP processor: " . ( $best_webp_processor ?? 'None' ) . "\n";
        echo "Best AVIF processor: " . ( $best_avif_processor ?? 'None' ) . "\n";
        echo "Best AV1 processor: " . ( $best_av1_processor ?? 'None' ) . "\n";
        echo "Best WebM processor: " . ( $best_webm_processor ?? 'None' ) . "\n";
        
        $this->assertIsArray( $image_processors, 'Image processors should be an array' );
        $this->assertIsArray( $video_processors, 'Video processors should be an array' );
    }

    /**
     * Test detailed format support information.
     *
     * @since 0.1.0
     * @return void
     */
    public function testDetailedFormatSupportInfo() {
        // Arrange
        $format_info = $this->format_detector->get_format_support_info();
        
        // Act & Assert
        $this->assertIsArray( $format_info, 'Format support info should be an array' );
        $this->assertArrayHasKey( 'webp', $format_info, 'Format info should include WebP' );
        $this->assertArrayHasKey( 'avif', $format_info, 'Format info should include AVIF' );
        $this->assertArrayHasKey( 'av1', $format_info, 'Format info should include AV1' );
        $this->assertArrayHasKey( 'webm', $format_info, 'Format info should include WebM' );
        
        // Output detailed information
        foreach ( $format_info as $format => $info ) {
            echo "{$format}: " . ( $info['supported'] ? 'SUPPORTED' : 'NOT SUPPORTED' ) . "\n";
            foreach ( $info as $key => $value ) {
                if ( $key !== 'supported' ) {
                    echo "  {$key}: " . ( is_bool( $value ) ? ( $value ? 'YES' : 'NO' ) : $value ) . "\n";
                }
            }
        }
    }

    /**
     * Test that at least one format is supported.
     *
     * @since 0.1.0
     * @return void
     */
    public function testAtLeastOneFormatSupported() {
        // Arrange
        $webp_supported = $this->format_detector->supports_webp();
        $avif_supported = $this->format_detector->supports_avif();
        $av1_supported = $this->format_detector->supports_av1();
        $webm_supported = $this->format_detector->supports_webm();
        
        // Act & Assert
        $any_supported = $webp_supported || $avif_supported || $av1_supported || $webm_supported;
        
        if ( $any_supported ) {
            echo "At least one format is supported: YES\n";
            $this->assertTrue( $any_supported, 'At least one format should be supported' );
        } else {
            echo "At least one format is supported: NO\n";
            $this->markTestSkipped( 'No formats are supported on this system' );
        }
    }

    /**
     * Get GD WebP support using low-level detection.
     *
     * @since 0.1.0
     * @return bool True if GD supports WebP, false otherwise.
     */
    private function get_gd_webp_support() {
        if ( ! extension_loaded( 'gd' ) ) {
            return false;
        }

        $gd_info = gd_info();
        return isset( $gd_info['WebP Support'] ) && $gd_info['WebP Support'];
    }

    /**
     * Get GD AVIF support using low-level detection.
     *
     * @since 0.1.0
     * @return bool True if GD supports AVIF, false otherwise.
     */
    private function get_gd_avif_support() {
        return extension_loaded( 'gd' ) && function_exists( 'imageavif' );
    }

    /**
     * Get Imagick WebP support using low-level detection.
     *
     * @since 0.1.0
     * @return bool True if Imagick supports WebP, false otherwise.
     */
    private function get_imagick_webp_support() {
        if ( ! extension_loaded( 'imagick' ) || ! class_exists( 'Imagick' ) ) {
            return false;
        }

        try {
            $imagick = new \Imagick();
            $formats = $imagick->queryFormats();
            return in_array( 'WEBP', $formats, true );
        } catch ( \Exception $e ) {
            return false;
        }
    }

    /**
     * Get Imagick AVIF support using low-level detection.
     *
     * @since 0.1.0
     * @return bool True if Imagick supports AVIF, false otherwise.
     */
    private function get_imagick_avif_support() {
        if ( ! extension_loaded( 'imagick' ) || ! class_exists( 'Imagick' ) ) {
            return false;
        }

        try {
            $imagick = new \Imagick();
            $formats = $imagick->queryFormats();
            return in_array( 'AVIF', $formats, true );
        } catch ( \Exception $e ) {
            return false;
        }
    }

    /**
     * Get FFmpeg availability using low-level detection.
     *
     * @since 0.1.0
     * @return bool True if FFmpeg is available, false otherwise.
     */
    private function get_ffmpeg_availability() {
        $possible_paths = [
            'ffmpeg',
            '/usr/bin/ffmpeg',
            '/usr/local/bin/ffmpeg',
            '/opt/homebrew/bin/ffmpeg',
        ];

        foreach ( $possible_paths as $path ) {
            if ( $this->is_executable( $path ) ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get FFmpeg AV1 support using low-level detection.
     *
     * @since 0.1.0
     * @return bool True if FFmpeg supports AV1, false otherwise.
     */
    private function get_ffmpeg_av1_support() {
        if ( ! $this->get_ffmpeg_availability() ) {
            return false;
        }

        return $this->ffmpeg_supports_codec( 'libaom-av1' );
    }

    /**
     * Get FFmpeg WebM support using low-level detection.
     *
     * @since 0.1.0
     * @return bool True if FFmpeg supports WebM, false otherwise.
     */
    private function get_ffmpeg_webm_support() {
        if ( ! $this->get_ffmpeg_availability() ) {
            return false;
        }

        return $this->ffmpeg_supports_codec( 'libvpx-vp9' );
    }

    /**
     * Check if binary is executable.
     *
     * @since 0.1.0
     * @param string $path Binary path.
     * @return bool True if executable, false otherwise.
     */
    private function is_executable( $path ) {
        try {
            $process = new \Symfony\Component\Process\Process( [ $path, '-version' ] );
            $process->run();
            return $process->isSuccessful();
        } catch ( \Exception $e ) {
            return false;
        }
    }

    /**
     * Check if FFmpeg supports a specific codec.
     *
     * @since 0.1.0
     * @param string $codec Codec name to check.
     * @return bool True if codec is supported, false otherwise.
     */
    private function ffmpeg_supports_codec( $codec ) {
        try {
            $process = new \Symfony\Component\Process\Process( [ 'ffmpeg', '-encoders' ] );
            $process->run();
            
            if ( $process->isSuccessful() ) {
                $output = $process->getOutput();
                return strpos( $output, $codec ) !== false;
            }
        } catch ( \Exception $e ) {
            // Process execution failed
        }

        return false;
    }
}
