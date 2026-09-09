=== Flux Media Optimizer – Image & Video Optimization by Flux Plugins ===
Contributors: edaniels
Tags: media optimizer, video compression, webp, avif, cdn
Requires at least: 5.8
Tested up to: 7.1
Requires PHP: 8.1
Stable tag: 4.3.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Optimize WordPress images & videos locally with WebP/AVIF/WebM. No credits. Optional cloud processing and CDN.

== Description ==

### The Complete Media Performance Solution for WordPress

Automatically reduce image and video sizes by up to 70% and improve Core Web Vitals — no setup required.

Flux Media Optimizer is the all-in-one media optimizer plugin for WordPress – optimize images, compress videos, and deliver everything through a global CDN for lightning-fast page loads worldwide.

Transform your WordPress site's media performance with Flux Media Optimizer. Compress images, convert to next-gen formats (WebP & AVIF), optimize videos with modern formats (AV1, WebM), and serve media through a global CDN, all while maintaining the visual quality your visitors expect.

**All core features are available in the free version.** Gain additional benefits including offloaded processing, global CDN delivery, and advanced compression algorithms when you [purchase a license](https://fluxplugins.com/media-optimizer/?utm_source=flux-media-optimizer&utm_medium=wporg&utm_campaign=product-page&utm_content=readme-purchase).

### Key Features

* **Modern image formats** – WebP and/or AVIF based on your Settings (both enabled by default)
* **Video optimization** – Convert library videos to modern formats with quality and size controls
* **Automatic & bulk processing** – Optimize on upload or process the existing media library in one pass
* **Savings you can see** – Overview totals and per-attachment comparisons show how much weight you removed
* **Media Library status** – Optimization column and filters (Optimized, Pending, Failed, Disabled, Unprocessed)
* **Per-file controls** – Disable, convert, or re-convert individual attachments
* **GIF & HEIC/HEIF** – Animated GIFs and supported iPhone-style stills when your server can decode them
* **Works with WordPress** – Galleries, responsive images, Gutenberg, and WooCommerce product media
* **No usage credits** – Optimize within your server's (or optional cloud) capacity — no per-image metering in the free plugin
* **Optional Flux Suite cloud & CDN** – Offload heavy jobs and serve optimized assets globally when licensed

### Take Media Performance Further with Flux Suite

Every local optimization feature stays free — no license or per-image credits required. Add Flux Suite when you want faster delivery and powerful processing without relying on your web server.

* **Reduce server load** – Move demanding image and video conversions to Flux cloud, avoiding local CPU, memory, and timeout limits
* **Deliver media faster worldwide** – Serve optimized files through a global CDN powered by Google Cloud
* **Achieve better compression** – Use advanced cloud optimization to create smaller files while preserving visual quality
* **Unlock the complete Flux Suite** – One subscription includes premium hosted features across Flux plugins, including AI alt text, accessibility tools, and WordPress admin productivity features

Cloud processing and CDN delivery are optional. Enable or disable them anytime while keeping local optimization available.

[Explore Flux Suite](https://fluxplugins.com/media-optimizer/?utm_source=flux-media-optimizer&utm_medium=wporg&utm_campaign=product-page&utm_content=readme-suite-upsell)

### File size savings (what you'll see)

Optimized files are typically much smaller to **download** than the originals. That cuts page weight and helps Core Web Vitals — it is **not** the same as freeing disk space, because originals stay on the server as fallbacks.

Typical download-size reductions (content-dependent; your results vary):

* **WebP (lossy)** – often about 25–34% smaller than comparable JPEG
* **WebP (lossless)** – often about 26% smaller than comparable PNG
* **AVIF** – often up to ~50–60% smaller than comparable JPEG or PNG
* **Overall library** – many sites see roughly **50–70%** less media transfer weight after optimization

Where the plugin shows this:

* **Overview** – aggregate original vs optimized bytes and savings percentage
* **Attachment details** – per-size original vs WebP/AVIF (or video formats) with savings badges
* **Media Library** – status so you can find Optimized, Pending, and Failed items

### Perfect for

* Bloggers and content creators who want faster sites
* E-commerce stores needing better Core Web Vitals scores
* Anyone serious about website performance and SEO

Ready to improve media performance? Install Flux Media Optimizer for local image and video optimization — cloud and CDN stay optional.

### Technical details and server requirements

* **Local libraries** – Image formats depend on GD/Imagick on the server. Video and some animated pipelines need **FFmpeg**. The Overview page reports what your host can do.
* **Serving** – By default the plugin replaces media URLs with optimized versions. An optional experimental hybrid `<picture>` mode exists in Settings (off by default).
* **HEIC/HEIF** – Static files need Imagick HEIC decode (**libheif 1.18.2+** recommended for modern iPhone HDR/gain-map photos). Rare animated HEIF sequences need FFmpeg + WebP enabled for animated WebP; otherwise first-frame static output. Apple Live Photos (HEIC + MOV) are not merged as one asset.
* **Cloud / CDN** – Optional; requires a valid Flux Suite license, explicit enablement, and secure webhooks. Turn off anytime to return to local-only processing.

== Frequently Asked Questions ==

= Does this plugin work with any WordPress theme? =

Yes. It hooks into WordPress core media/image APIs, so themes and plugins that use standard WordPress image functions work without theme changes.

= Will my original images and videos be deleted? =

No. Originals stay on your server as fallbacks. The plugin creates optimized copies (or cloud/CDN URLs when that service is enabled). You can keep serving or restoring from the originals.

= Will this break my existing images or videos? =

No. Optimized versions are added alongside originals. Untouched originals remain available for unsupported browsers or if you disable optimization for a file.

= How much will I save? Disk space vs download size? =

**Download / page weight:** Optimized outputs are commonly **50–70%** smaller to transfer than the source files. Format-level ranges are summarized under “File size savings” in the Description. Overview and attachment panels show **your** byte totals and percentages.

**Disk space:** Originals are kept, so server storage is not reduced by default. Savings are about faster pages and less bandwidth for visitors — not freeing upload-directory capacity.

= Will optimization use a lot of server CPU? =

Conversion (especially **video**) is CPU-intensive while jobs run. Work is queued in the background so front-end browsing stays responsive, but a busy bulk run can still load the server. If CPU is limited, enable optional **Flux Suite cloud processing** so heavy conversion runs off-site, or process the library in smaller batches.

= Do I need FFmpeg? =

**Local video** optimization and some animated HEIF → animated WebP paths need FFmpeg on the server. **Image-only** WebP/AVIF conversion uses GD/Imagick and does not require FFmpeg. Overview shows whether FFmpeg is detected. Without FFmpeg, use Flux Suite cloud processing for video (when licensed and enabled), or stick to images locally.

= Does my server need AVIF support? =

Local AVIF **output** needs a library stack that can encode AVIF (typically a capable Imagick/libheif build). The plugin detects capabilities and only offers formats your server can produce. If local AVIF is missing, keep WebP local, or use optional Flux Suite cloud processing to generate formats on Flux infrastructure.

= What about privacy? Do my media files leave my server? =

**Default:** processing is local — media files are not uploaded to Flux for optimization.

**Optional cloud/CDN:** only after you activate a license **and** enable the external service. Then media (and related metadata) may be sent for processing and CDN delivery. License validation and compatibility checks may send limited site/plugin metadata (see External services below). Newsletter signup sends email only if an admin opts in on the settings form.

Full policy: https://fluxplugins.com/privacy-policy/

= Can I cancel or turn off cloud / CDN later? =

Yes. Disable external/cloud processing in plugin settings anytime to return to **local-only** optimization; new work stays on your server. CDN URLs stop being used for new delivery according to your settings once cloud is off (existing CDN-hosted assets may remain on Flux until cleaned up per service terms).

Billing or license cancellation for a paid Flux Suite subscription is managed on [fluxplugins.com](https://fluxplugins.com/) (the plugin cannot cancel a store subscription by itself).

= How does the CDN behave? =

With a valid license and cloud/CDN enabled, optimized (and optionally other) media can be stored on Flux's global CDN and served over HTTPS from allowlisted hosts. Core local optimization does **not** require the CDN. CDN is opt-in, intended for faster worldwide delivery and to pair with offloaded processing — not a replacement for keeping originals locally unless you choose that workflow.

= External services =

This plugin can optionally use the Flux Plugins API for license validation and for **optional** cloud media processing / CDN delivery. Local image and video optimization works without any external service, license, or outbound media upload.

* Service: Flux Plugins API. Default base URL: `https://api.fluxplugins.com`.
* When requests occur: license activate/validate; shared-library compatibility checks; and when you explicitly enable optional Flux cloud processing with a valid license (uploads, conversion jobs, webhooks, CDN URLs).
* Data sent may include: license key (when provided), account ID (UUID), site URL/domain, plugin version, and media files/metadata only when optional cloud processing is enabled.
* Optional cloud processing and CDN are **opt-in**. Enable the setting with a valid license; turn off anytime for local-only processing.

Optional newsletter subscription (settings screen):

* Service endpoint: `https://fluxplugins.com/wp-admin/admin-ajax.php?action=tnp&na=s`
* When: only when an administrator submits the Stay Updated form with email and privacy acceptance
* Data sent: email address and newsletter consent flag
* Privacy policy: https://fluxplugins.com/privacy-policy/
* Terms of use: https://fluxplugins.com/terms-of-service/

Privacy policy: https://fluxplugins.com/privacy-policy/

Terms of use: https://fluxplugins.com/terms-of-service/

= Advanced: can I override the API URL or timeouts? =

Yes, via `wp-config.php` constants when needed (staging, custom endpoints): `FLUX_MEDIA_OPTIMIZER_EXTERNAL_SERVICE_URL` / `FLUX_PLUGINS_COMMON_EXTERNAL_SERVICE_URL` (common wins if both are set), and the matching `*_EXTERNAL_SERVICE_TIMEOUT` constants. Most sites never need these.

= Does this work with existing images and videos? =

Yes. Use settings to bulk-convert the media library. Pages that hard-coded old file URLs may need a refresh or re-save so they pick up optimized URLs.

= Can I see which media files are optimized? =

Yes. Media Library list view: Optimization column plus filters for Optimized, Pending, Failed, Disabled, and Unprocessed. Attachment details show per-size savings. Overview shows library-wide totals.

= What if my server doesn't support WebP or AVIF? =

The plugin detects server capabilities and only creates supported formats. For missing encoders, enable optional Flux Suite cloud processing (licensed) so conversion runs on Flux servers, or use whichever local formats are available (often WebP when AVIF is missing).

= Does this work with animated GIFs? =

Yes. With Imagick available, animated GIFs can be converted while preserving animation. Static GIFs are handled as still images.

= Does this work with HEIC/HEIF (including iPhone photos)? =

Yes when the server can decode the file. Static HEIC/HEIF needs Imagick HEIC support; libheif 1.18.2+ is recommended for modern iOS HDR/gain-map photos. Output follows your WebP/AVIF settings. Rare animated HEIF sequences need FFmpeg and WebP enabled for animated WebP; otherwise you get static first-frame output. Apple Live Photos (HEIC + paired MOV) are not supported as a single combined asset. Undecodable files show as Failed in the Media Library.

= Can I control the quality of the optimized images? =

Yes. Separate WebP and AVIF quality settings let you balance size and visual quality.

= Does this work with WooCommerce? =

Yes. Product images go through the media library, so on-upload and bulk optimization apply like other attachments.

= What video formats are supported? =

Locally (with FFmpeg): convert to AV1 in an MP4 container and/or WebM, with bitrate/quality controls. Jobs run in the background. Without FFmpeg, use optional cloud processing when licensed, or optimize images only.

== Screenshots ==

1. See exactly how much media weight you saved.
2. Optimize the entire media library.
3. Every file shows its optimization status.
4. Local mode is free; cloud processing is optional.
5. Offload CPU-heavy video/image processing when you need it.
6. Serve optimized media globally with Flux CDN.

== Changelog ==

= 4.3.1 =
* Tested up to WordPress 7.1.
* Feature: Welcome modal on first activation shows local image and video processing availability, site-level missing capability chips (formats no local processor can provide), and a subtle CDN/cloud upsell when unlicensed.
* Feature: Once-ever review prompt on Overview after meaningful download-bandwidth savings, with WordPress.org review and support links; suppressed while welcome is showing or optimizations have failed.
* Feature: Need help links on Overview failed-optimizations notice and attachment details error alerts.
* Deprecated: VideoConverter::get_conversion_stats() stub; use ConversionTracker for live conversion statistics.

= 4.3.0 =
* Feature: Local HEIC/HEIF conversion with Imagick decode (libheif 1.18.2+ recommended for modern iOS photos), optional animated HEIF-to-WebP via FFmpeg, and separate HEIC capability reporting.
* Feature: Unified Action Scheduler retries for local and cloud failures (3 automatic attempts with progressive backoff), with processing routed by current license/settings and Flux logging on failures.
* Feature: Redesigned attachment details panel with async REST loading, compact skeleton placeholder, MUI Grid responsive layout that stays within the WordPress container, size accordion, format URLs, and a license-aware CDN upsell.
* Feature: Attachment details poll every 15 seconds while Pending (including locally deferred video conversions) and refresh Convert/Disable/Enable actions in place without a full page reload.
* Feature: Attachment details show only formats enabled and supported by the active media converter (WebP/AVIF for images, AV1/WebM for videos), with per-size savings badges compared to the same-size original.
* Feature: Attachment details and conversion controls use WordPress `edit_post` attachment capabilities.
* Fix: External processing activation requires a valid license; uninstall removes the correct option keys while preserving shared suite account ID; safer database table-drop SQL; external operations builder hardening.
* Fix: Image conversion publishes files and metadata/statistics together (no partial meta on multi-size failure); skipped retries no longer consume the retry budget; plugin/common external API URL constants stay aligned.

= 4.2.0 =
* Feature: Media Library shows optimization status and filter options (Optimized, Pending, Failed, Disabled, Unprocessed). Conversion stats API now reports failed external job counts. New constants added: FLUX_MEDIA_OPTIMIZER_STALE_JOB_THRESHOLD, FLUX_MEDIA_OPTIMIZER_FAILED_JOB_RETRY_LIMIT, FLUX_MEDIA_OPTIMIZER_CLEANUP_BATCH_SIZE.
* Feature: Daily cleanup cron recovers stale external jobs, automatically retries failed jobs with a set limit, and cleans expired admin notices.

= 4.1.6 =
* Security: Harden external webhook endpoint (account ID verification, job-state checks, CDN host allowlist, rate limiting).
* Security: Register webhook route only when external service is enabled and license is valid.
* Fix: Remove duplicate admin AJAX handler registration for attachment actions.
* Security: Avoid exposing raw exception messages in plugin REST error responses.
* Removed legacy plugin logs REST API; suite logs use flux-plugins-common.

= 4.1.5 =
* Guard against php 8.0 - we support php 8.1>.
* Removed some unused code and minor cleanup.
* Tested up to WordPress 7.0.

= 4.1.4 =
* Updating dependency to be compatible with php 8.

= 4.1.2 =
* Updated build scripts and added link on settings page.


== Upgrade Notice ==

= 4.3.1 =
Tested with WordPress 7.1. Adds a one-time welcome modal after activation and a once-ever review prompt after meaningful savings, plus Need help links on failure notices.

= 4.3.0 =
Major update: HEIC/HEIF conversion, unified Action Scheduler retries, and a redesigned async attachment details panel with responsive layout, 15-second Pending polling, conditional format columns, video support, and per-size savings.

= 4.2.0 =
Adds Media Library optimization status visibility and daily cleanup for stale external jobs with bounded retries. Local features remain fully available without a license.

= 4.0.0 =
Major update with improved bulk optimization processing, fixed Action Scheduler bulk operations, and core system decoupling for future plugin integrations. Bulk optimization issues have been resolved for more reliable processing of existing media libraries.

= 3.0.0 =
Major update with optional CDN integration, enhanced video optimization, and improved architecture. All existing functionality continues to work as before. CDN features require explicit opt-in.

= 1.0.0 =
Initial release of Flux Media Optimizer by Flux Plugins with comprehensive media optimization features. Perfect for improving your site's Core Web Vitals and SEO performance.

== Privacy ==

**Default Behavior:**
By default, all image and video processing happens locally on your server. Your media files never leave your WordPress installation unless you explicitly opt-in to external processing services.

**Optional External Service:**
This plugin includes an optional external service integration that provides:
* **External File Processing**: Offloads heavy image and video conversion tasks to external servers, reducing load on your server
* **CDN Integration**: Stores all media files on a global CDN for faster delivery worldwide. Images and videos are processed and optimized, while other file types (PDFs, documents, etc.) are stored directly for CDN delivery

**What Data is Sent:**
When the external service is enabled (requires explicit user activation and a license key), the following data is sent to the external service:
* All media files (images, videos, PDFs, documents, etc.) that you upload
* Images and videos are processed and optimized; other file types are stored directly on the CDN
* Attachment metadata (file names, sizes, formats)
* License key for authentication
* Account ID (UUID) for service identification

**When Data is Sent:**
Data is only sent when:
* The external service is explicitly enabled by the user in plugin settings
* A valid license key is provided and activated
* Media files are uploaded or conversion is requested

**Service Provider:**
The external service is provided by Flux Plugins:
* **Terms of Service**: https://fluxplugins.com/terms-of-service/
* **Privacy Policy**: https://fluxplugins.com/privacy-policy/

**Important Notes:**
* External service is completely optional - all core functionality works locally without it
* External service requires explicit user consent and license activation
* You can disable external service at any time to return to local-only processing
* By default, the plugin uses local processing only

== Privacy Policy ==

Flux Media Optimizer is committed to protecting your privacy. By default, all image and video processing happens locally on your server - your media files never leave your WordPress installation.

**View our full privacy policy**: [https://fluxplugins.com/privacy-policy/](https://fluxplugins.com/privacy-policy/)

**Key points:**
* Local processing by default - no external data sharing
* Email collection for marketing purposes only with opt-in consent
* Full compliance with WordPress.org guidelines and privacy regulations
