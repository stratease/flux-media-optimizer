<?php
/**
 * Unit tests for ProcessorCapabilityAggregator.
 *
 * @package FluxMedia\Tests\Unit
 * @since 4.3.1
 */

namespace FluxMedia\Tests\Unit;

use FluxMedia\App\Services\ProcessorCapabilityAggregator;
use PHPUnit\Framework\TestCase;

/**
 * ProcessorCapabilityAggregator unit tests.
 *
 * @since 4.3.1
 */
class ProcessorCapabilityAggregatorTest extends TestCase {

	/**
	 * Empty image processors yield all false flags.
	 *
	 * @since 4.3.1
	 * @return void
	 */
	public function testAggregateImageEmptyProcessorsAllFalse() {
		$flags = ProcessorCapabilityAggregator::aggregate_image( [] );

		$this->assertFalse( $flags['webp_support'] );
		$this->assertFalse( $flags['avif_support'] );
		$this->assertFalse( $flags['animated_gif_support'] );
		$this->assertFalse( $flags['heic_support'] );
		$this->assertFalse( $flags['animated_heic_support'] );
	}

	/**
	 * Animated GIF is true when any processor supports it.
	 *
	 * @since 4.3.1
	 * @return void
	 */
	public function testAggregateImageAnimatedGifOrAcrossProcessors() {
		$processors = [
			'imagick' => [
				'animated_gif_support' => true,
				'webp_support'         => false,
			],
			'gd'      => [
				'animated_gif_support' => false,
				'webp_support'         => true,
			],
		];

		$flags = ProcessorCapabilityAggregator::aggregate_image( $processors );

		$this->assertTrue( $flags['animated_gif_support'] );
		$this->assertTrue( $flags['webp_support'] );
		$this->assertFalse( $flags['avif_support'] );
	}

	/**
	 * Animated GIF is false when no processor supports it.
	 *
	 * @since 4.3.1
	 * @return void
	 */
	public function testAggregateImageAnimatedGifFalseWhenNoneSupport() {
		$processors = [
			'imagick' => [ 'animated_gif_support' => false ],
			'gd'      => [ 'animated_gif_support' => false ],
		];

		$flags = ProcessorCapabilityAggregator::aggregate_image( $processors );

		$this->assertFalse( $flags['animated_gif_support'] );
	}

	/**
	 * HEIC and animated HEIC OR independently.
	 *
	 * @since 4.3.1
	 * @return void
	 */
	public function testAggregateImageHeicFlagsIndependentOr() {
		$processors = [
			'imagick' => [
				'heic_support'          => true,
				'animated_heic_support' => false,
			],
			'gd'      => [
				'heic_support'          => false,
				'animated_heic_support' => true,
			],
		];

		$flags = ProcessorCapabilityAggregator::aggregate_image( $processors );

		$this->assertTrue( $flags['heic_support'] );
		$this->assertTrue( $flags['animated_heic_support'] );
	}

	/**
	 * Empty video processors yield all false flags.
	 *
	 * @since 4.3.1
	 * @return void
	 */
	public function testAggregateVideoEmptyProcessorsAllFalse() {
		$flags = ProcessorCapabilityAggregator::aggregate_video( [] );

		$this->assertFalse( $flags['av1_support'] );
		$this->assertFalse( $flags['webm_support'] );
	}

	/**
	 * Video AV1 and WebM OR across processors.
	 *
	 * @since 4.3.1
	 * @return void
	 */
	public function testAggregateVideoAv1AndWebmOr() {
		$processors = [
			'ffmpeg' => [
				'av1_support'  => true,
				'webm_support' => false,
			],
			'other'  => [
				'av1_support'  => false,
				'webm_support' => true,
			],
		];

		$flags = ProcessorCapabilityAggregator::aggregate_video( $processors );

		$this->assertTrue( $flags['av1_support'] );
		$this->assertTrue( $flags['webm_support'] );
	}
}
