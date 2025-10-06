<?php
/**
 * FFmpeg video processor implementation.
 *
 * @package FluxMedia
 * @since 1.0.0
 */

namespace FluxMedia\Processors;

use FluxMedia\Interfaces\VideoProcessorInterface;
use FluxMedia\Utils\Logger;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

/**
 * FFmpeg-based video processor with high-quality conversion settings.
 *
 * @since 1.0.0
 */
class FFmpegProcessor implements VideoProcessorInterface {

	/**
	 * Logger instance.
	 *
	 * @since 1.0.0
	 * @var Logger
	 */
	private $logger;

	/**
	 * FFmpeg binary path.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private $ffmpeg_path;

	/**
	 * FFprobe binary path.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private $ffprobe_path;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 * @param Logger $logger Logger instance.
	 */
	public function __construct( Logger $logger ) {
		$this->logger = $logger;
		$this->ffmpeg_path = $this->find_ffmpeg_binary();
		$this->ffprobe_path = $this->find_ffprobe_binary();
	}

	/**
	 * Find FFmpeg binary path.
	 *
	 * @since 1.0.0
	 * @return string FFmpeg binary path.
	 */
	private function find_ffmpeg_binary() {
		// Check common locations.
		$possible_paths = [
			'ffmpeg',
			'/usr/bin/ffmpeg',
			'/usr/local/bin/ffmpeg',
			'/opt/homebrew/bin/ffmpeg',
		];

		foreach ( $possible_paths as $path ) {
			if ( $this->is_executable( $path ) ) {
				return $path;
			}
		}

		return 'ffmpeg'; // Fallback to PATH.
	}

	/**
	 * Find FFprobe binary path.
	 *
	 * @since 1.0.0
	 * @return string FFprobe binary path.
	 */
	private function find_ffprobe_binary() {
		// Check common locations.
		$possible_paths = [
			'ffprobe',
			'/usr/bin/ffprobe',
			'/usr/local/bin/ffprobe',
			'/opt/homebrew/bin/ffprobe',
		];

		foreach ( $possible_paths as $path ) {
			if ( $this->is_executable( $path ) ) {
				return $path;
			}
		}

		return 'ffprobe'; // Fallback to PATH.
	}

	/**
	 * Check if binary is executable.
	 *
	 * @since 1.0.0
	 * @param string $path Binary path.
	 * @return bool True if executable, false otherwise.
	 */
	private function is_executable( $path ) {
		try {
			$process = new Process( [ $path, '-version' ] );
			$process->run();
			return $process->isSuccessful();
		} catch ( \Exception $e ) {
			return false;
		}
	}

	/**
	 * Get processor information.
	 *
	 * @since 1.0.0
	 * @return array Processor information.
	 */
	public function get_info() {
		$version_info = $this->get_version_info();
		$codec_info = $this->get_codec_info();
		
		return [
			'available' => true,
			'type' => 'ffmpeg',
			'version' => $version_info,
			'ffmpeg_path' => $this->ffmpeg_path,
			'ffprobe_path' => $this->ffprobe_path,
			'av1_support' => $this->supports_av1(),
			'webm_support' => $this->supports_webm(),
			'codecs' => $codec_info,
		];
	}

	/**
	 * Get FFmpeg version information.
	 *
	 * @since 1.0.0
	 * @return string Version information.
	 */
	private function get_version_info() {
		try {
			$process = new Process( [ $this->ffmpeg_path, '-version' ] );
			$process->run();
			
			if ( $process->isSuccessful() ) {
				$output = $process->getOutput();
				$lines = explode( "\n", $output );
				return $lines[0] ?? 'Unknown';
			}
		} catch ( \Exception $e ) {
			$this->logger->error( "Failed to get FFmpeg version: {$e->getMessage()}" );
		}

		return 'Unknown';
	}

	/**
	 * Get available codecs information.
	 *
	 * @since 1.0.0
	 * @return array Codec information.
	 */
	private function get_codec_info() {
		try {
			$process = new Process( [ $this->ffmpeg_path, '-codecs' ] );
			$process->run();
			
			if ( $process->isSuccessful() ) {
				$output = $process->getOutput();
				$lines = explode( "\n", $output );
				
				$codecs = [];
				foreach ( $lines as $line ) {
					if ( strpos( $line, 'libaom-av1' ) !== false ) {
						$codecs['av1'] = true;
					}
					if ( strpos( $line, 'libvpx' ) !== false ) {
						$codecs['webm'] = true;
					}
				}
				
				return $codecs;
			}
		} catch ( \Exception $e ) {
			$this->logger->error( "Failed to get codec information: {$e->getMessage()}" );
		}

		return [];
	}

	/**
	 * Convert video to AV1 format.
	 *
	 * @since 1.0.0
	 * @param string $source_path Source video path.
	 * @param string $destination_path Destination path.
	 * @param array  $options Conversion options.
	 * @return bool True on success, false on failure.
	 */
	public function convert_to_av1( $source_path, $destination_path, $options = [] ) {
		if ( ! $this->supports_av1() ) {
			$this->logger->error( 'FFmpeg does not support AV1 encoding' );
			return false;
		}

		$crf = $options['crf'] ?? 28;
		$preset = $options['preset'] ?? 'medium';
		$cpu_used = $options['cpu_used'] ?? 4;
		$threads = $options['threads'] ?? 0;

		$command = [
			$this->ffmpeg_path,
			'-i', $source_path,
			'-c:v', 'libaom-av1',
			'-crf', (string) $crf,
			'-preset', $preset,
			'-cpu-used', (string) $cpu_used,
			'-c:a', 'libopus',
			'-b:a', '128k',
			'-movflags', '+faststart',
		];

		if ( $threads > 0 ) {
			$command[] = '-threads';
			$command[] = (string) $threads;
		}

		$command[] = '-y'; // Overwrite output file.
		$command[] = $destination_path;

		try {
			$process = new Process( $command );
			$process->setTimeout( 3600 ); // 1 hour timeout.
			$process->run();

			if ( $process->isSuccessful() ) {
				$this->logger->info( "AV1 conversion completed successfully: {$source_path}" );
				return true;
			} else {
				$this->logger->error( "AV1 conversion failed: {$process->getErrorOutput()}" );
				return false;
			}
		} catch ( ProcessFailedException $e ) {
			$this->logger->error( "AV1 conversion process failed: {$e->getMessage()}" );
			return false;
		}
	}

	/**
	 * Convert video to WebM format.
	 *
	 * @since 1.0.0
	 * @param string $source_path Source video path.
	 * @param string $destination_path Destination path.
	 * @param array  $options Conversion options.
	 * @return bool True on success, false on failure.
	 */
	public function convert_to_webm( $source_path, $destination_path, $options = [] ) {
		if ( ! $this->supports_webm() ) {
			$this->logger->error( 'FFmpeg does not support WebM encoding' );
			return false;
		}

		$crf = $options['crf'] ?? 30;
		$preset = $options['preset'] ?? 'medium';
		$threads = $options['threads'] ?? 0;

		$command = [
			$this->ffmpeg_path,
			'-i', $source_path,
			'-c:v', 'libvpx-vp9',
			'-crf', (string) $crf,
			'-preset', $preset,
			'-c:a', 'libopus',
			'-b:a', '128k',
			'-movflags', '+faststart',
		];

		if ( $threads > 0 ) {
			$command[] = '-threads';
			$command[] = (string) $threads;
		}

		$command[] = '-y'; // Overwrite output file.
		$command[] = $destination_path;

		try {
			$process = new Process( $command );
			$process->setTimeout( 3600 ); // 1 hour timeout.
			$process->run();

			if ( $process->isSuccessful() ) {
				$this->logger->info( "WebM conversion completed successfully: {$source_path}" );
				return true;
			} else {
				$this->logger->error( "WebM conversion failed: {$process->getErrorOutput()}" );
				return false;
			}
		} catch ( ProcessFailedException $e ) {
			$this->logger->error( "WebM conversion process failed: {$e->getMessage()}" );
			return false;
		}
	}

	/**
	 * Get video metadata.
	 *
	 * @since 1.0.0
	 * @param string $file_path Video file path.
	 * @return array|false Video metadata or false on failure.
	 */
	public function get_metadata( $file_path ) {
		$command = [
			$this->ffprobe_path,
			'-v', 'quiet',
			'-print_format', 'json',
			'-show_format',
			'-show_streams',
			$file_path,
		];

		try {
			$process = new Process( $command );
			$process->run();

			if ( $process->isSuccessful() ) {
				$output = $process->getOutput();
				$metadata = json_decode( $output, true );
				
				if ( $metadata ) {
					return [
						'duration' => (float) ( $metadata['format']['duration'] ?? 0 ),
						'bitrate' => (int) ( $metadata['format']['bit_rate'] ?? 0 ),
						'size' => (int) ( $metadata['format']['size'] ?? 0 ),
						'width' => (int) ( $metadata['streams'][0]['width'] ?? 0 ),
						'height' => (int) ( $metadata['streams'][0]['height'] ?? 0 ),
						'codec' => $metadata['streams'][0]['codec_name'] ?? 'unknown',
					];
				}
			}
		} catch ( \Exception $e ) {
			$this->logger->error( "Failed to get video metadata: {$e->getMessage()}" );
		}

		return false;
	}

	/**
	 * Check if processor supports AV1.
	 *
	 * @since 1.0.0
	 * @return bool True if AV1 is supported, false otherwise.
	 */
	public function supports_av1() {
		try {
			$process = new Process( [ $this->ffmpeg_path, '-encoders' ] );
			$process->run();
			
			if ( $process->isSuccessful() ) {
				$output = $process->getOutput();
				return strpos( $output, 'libaom-av1' ) !== false;
			}
		} catch ( \Exception $e ) {
			$this->logger->error( "Failed to check AV1 support: {$e->getMessage()}" );
		}

		return false;
	}

	/**
	 * Check if processor supports WebM.
	 *
	 * @since 1.0.0
	 * @return bool True if WebM is supported, false otherwise.
	 */
	public function supports_webm() {
		try {
			$process = new Process( [ $this->ffmpeg_path, '-encoders' ] );
			$process->run();
			
			if ( $process->isSuccessful() ) {
				$output = $process->getOutput();
				return strpos( $output, 'libvpx-vp9' ) !== false;
			}
		} catch ( \Exception $e ) {
			$this->logger->error( "Failed to check WebM support: {$e->getMessage()}" );
		}

		return false;
	}
}
