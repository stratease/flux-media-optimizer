<?php
/**
 * Site-level processor capability union.
 *
 * @package FluxMedia
 * @since 4.3.1
 */

namespace FluxMedia\App\Services;

/**
 * ORs per-processor capability flags into site-level support booleans.
 *
 * @since 4.3.1
 */
class ProcessorCapabilityAggregator {

	/**
	 * Image capability keys aggregated across processors.
	 *
	 * @since 4.3.1
	 * @var string[]
	 */
	private const IMAGE_KEYS = [
		'webp_support',
		'avif_support',
		'animated_gif_support',
		'heic_support',
		'animated_heic_support',
	];

	/**
	 * Video capability keys aggregated across processors.
	 *
	 * @since 4.3.1
	 * @var string[]
	 */
	private const VIDEO_KEYS = [
		'av1_support',
		'webm_support',
	];

	/**
	 * Union image capability flags across processors.
	 *
	 * Empty processors yields all false.
	 *
	 * @since 4.3.1
	 * @param array<string, array<string, mixed>> $processors Processor status rows.
	 * @return array{webp_support: bool, avif_support: bool, animated_gif_support: bool, heic_support: bool, animated_heic_support: bool}
	 */
	public static function aggregate_image( array $processors ) {
		return self::or_flags( $processors, self::IMAGE_KEYS );
	}

	/**
	 * Union video capability flags across processors.
	 *
	 * Empty processors yields all false.
	 *
	 * @since 4.3.1
	 * @param array<string, array<string, mixed>> $processors Processor status rows.
	 * @return array{av1_support: bool, webm_support: bool}
	 */
	public static function aggregate_video( array $processors ) {
		return self::or_flags( $processors, self::VIDEO_KEYS );
	}

	/**
	 * OR boolean flags for the given keys across processor rows.
	 *
	 * @since 4.3.1
	 * @param array<string, array<string, mixed>> $processors Processor status rows.
	 * @param string[]                            $keys       Flag keys.
	 * @return array<string, bool>
	 */
	private static function or_flags( array $processors, array $keys ) {
		$flags = [];
		foreach ( $keys as $key ) {
			$flags[ $key ] = false;
		}

		foreach ( $processors as $processor ) {
			if ( ! is_array( $processor ) ) {
				continue;
			}

			foreach ( $keys as $key ) {
				if ( ! empty( $processor[ $key ] ) ) {
					$flags[ $key ] = true;
				}
			}
		}

		return $flags;
	}
}
