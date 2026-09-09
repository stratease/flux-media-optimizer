import { __ } from '@wordpress/i18n';

/**
 * Tooltip copy for animated GIF capability chips (same strings as Overview).
 *
 * @since 4.3.1
 * @param {boolean} supported Whether animated GIF conversion is supported.
 * @return {string} Tooltip text.
 */
export const getAnimatedGifTooltip = (supported) => {
  return supported
    ? __(
        'Imagick can preserve animation when converting animated GIFs to WebP/AVIF.',
        'flux-media-optimizer'
      )
    : __(
        'GD cannot preserve animation. Animated GIFs will lose animation when converted. Imagick is required for animated GIF support.',
        'flux-media-optimizer'
      );
};

/**
 * Tooltip copy for static HEIC capability chips.
 *
 * @since 4.3.1
 * @param {boolean} supported Whether static HEIC decode is supported.
 * @return {string} Tooltip text.
 */
export const getHeicTooltip = (supported) => {
  return supported
    ? __(
        'Imagick can decode static HEIC/HEIF (libheif 1.18.2+ recommended for iOS gain-map photos) and convert them to WebP/AVIF per your format settings. Typical iPhone stills use this path. Live Photos (HEIC + MOV) are not supported.',
        'flux-media-optimizer'
      )
    : __(
        'HEIC/HEIF decode requires Imagick with libheif 1.18.2+. GD cannot read HEIC files. WebP/AVIF chips above only cover output formats, not HEIC input.',
        'flux-media-optimizer'
      );
};

/**
 * Tooltip copy for animated HEIC capability chips.
 *
 * @since 4.3.1
 * @param {boolean} supported Whether animated HEIF sequences can become animated WebP.
 * @return {string} Tooltip text.
 */
export const getAnimatedHeicTooltip = (supported) => {
  return supported
    ? __(
        'Animated HEIF sequences (msf1) convert to animated WebP via FFmpeg (libwebp_anim) when WebP output is enabled. Not video or GIF. If WebP is disabled or FFmpeg is missing, sequences become static first-frame WebP/AVIF. AVIF is never animated for these sources.',
        'flux-media-optimizer'
      )
    : __(
        'Animated HEIF sequences need FFmpeg with libwebp_anim. Without it, sequences fall back to static first-frame conversion when WebP/AVIF are enabled. Static HEIC may still work when Imagick+libheif is available.',
        'flux-media-optimizer'
      );
};

/**
 * HEIC chip descriptors with Overview coupling rules.
 *
 * One HEIC chip when static and animated flags match; otherwise both chips.
 *
 * @since 4.3.1
 * @param {boolean} heicSupport Static HEIC support.
 * @param {boolean} animatedHeicSupport Animated HEIC support.
 * @return {Array<{capabilityKey: string, label: string, supported: boolean, tooltip: string}>}
 */
export const getHeicChipDescriptors = (heicSupport, animatedHeicSupport) => {
  const heicCoupled = heicSupport === animatedHeicSupport;

  if (heicCoupled) {
    return [
      {
        capabilityKey: 'heic',
        label: 'HEIC',
        supported: heicSupport,
        tooltip: getHeicTooltip(heicSupport),
      },
    ];
  }

  return [
    {
      capabilityKey: 'heic',
      label: 'HEIC',
      supported: heicSupport,
      tooltip: getHeicTooltip(heicSupport),
    },
    {
      capabilityKey: 'animated_heic',
      label: 'Animated HEIC',
      supported: animatedHeicSupport,
      tooltip: getAnimatedHeicTooltip(animatedHeicSupport),
    },
  ];
};

/**
 * Image capability chip descriptors from a flags object (processor or site-level).
 *
 * @since 4.3.1
 * @param {Object} flags Capability flags.
 * @return {Array<{capabilityKey: string, label: string, supported: boolean, tooltip?: string}>}
 */
export const getImageCapabilityDescriptors = (flags) => {
  const slice = flags || {};
  const gifSupported = slice.animated_gif_support === true;

  return [
    {
      capabilityKey: 'webp',
      label: 'WebP',
      supported: slice.webp_support === true,
    },
    {
      capabilityKey: 'avif',
      label: 'AVIF',
      supported: slice.avif_support === true,
    },
    {
      capabilityKey: 'animated_gif',
      label: 'Animated GIF',
      supported: gifSupported,
      tooltip: getAnimatedGifTooltip(gifSupported),
    },
    ...getHeicChipDescriptors(slice.heic_support === true, slice.animated_heic_support === true),
  ];
};

/**
 * Video capability chip descriptors from a flags object.
 *
 * @since 4.3.1
 * @param {Object} flags Capability flags.
 * @return {Array<{capabilityKey: string, label: string, supported: boolean}>}
 */
export const getVideoCapabilityDescriptors = (flags) => {
  const slice = flags || {};

  return [
    {
      capabilityKey: 'av1',
      label: 'AV1',
      supported: slice.av1_support === true,
    },
    {
      capabilityKey: 'webm',
      label: 'WebM',
      supported: slice.webm_support === true,
    },
  ];
};

/**
 * Unsupported capability descriptors for Welcome site-level chips.
 *
 * @since 4.3.1
 * @param {Object} statusSlice imageProcessor or videoProcessor payload.
 * @param {'image'|'video'} kind Media kind.
 * @return {Array<{capabilityKey: string, label: string, supported: boolean, tooltip?: string}>}
 */
export const getMissingCapabilityDescriptors = (statusSlice, kind) => {
  const descriptors =
    kind === 'video'
      ? getVideoCapabilityDescriptors(statusSlice)
      : getImageCapabilityDescriptors(statusSlice);

  return descriptors.filter((descriptor) => !descriptor.supported);
};
