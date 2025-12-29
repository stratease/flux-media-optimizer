=== Flux Media Optimizer by Flux Plugins ===
Contributors: edaniels
Tags: image optimization, video compression, webp, avif, flux plugins
Requires at least: 6.2
Tested up to: 6.8
Requires PHP: 8.0
Stable tag: 2.0.6
License: GPL-2.0+
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Compress images to AVIF/WebP and optimize videos for 50-70% faster loads. Boost Core Web Vitals and SEO with automatic optimization.

== Description ==

Transform your WordPress site's media performance. Flux Media Optimizer automatically converts your images to modern formats like WebP and AVIF, and optimizes your videos for maximum performance.

**Key Benefits:**
* 50-70% smaller file sizes without quality loss
* Faster page loads and improved Core Web Vitals scores
* Better SEO rankings from faster-loading pages
* Automatic optimization that works with any theme
* Zero configuration required - works out of the box

**How It Works:**
* Automatically converts images to WebP and AVIF formats on upload
* Optimizes videos with MP4/WebM conversion
* Processes existing media library with bulk conversion
* Preserves your original files as fallbacks
* Works seamlessly with Gutenberg blocks, galleries, and responsive images

**Perfect for:**
* Bloggers and content creators who want faster sites
* E-commerce stores needing better Core Web Vitals scores
* Anyone serious about website performance and SEO

Ready to supercharge your site's performance? Install Flux Media Optimizer today for instant image and video optimization.

== Frequently Asked Questions ==

= Does this plugin work with any WordPress theme? =

Yes, Flux Media Optimizer works with any WordPress theme. It integrates seamlessly with WordPress's image rendering system and doesn't require any theme modifications.

= Will this break my existing images or videos? =

No, Flux Media Optimizer creates new optimized versions while keeping your original media files as fallbacks. Your original images and videos remain untouched.

= Does this work with existing images and videos? =

Yes! Flux Media Optimizer can bulk process all your existing images and videos. Just go to the settings page and enable bulk conversion to optimize your entire media library.

= How much space will I save? =

Most users see 50-70% reduction in image file sizes and significant savings on videos. The exact savings depend on your original media.

= What if my server doesn't support WebP or AVIF? =

Flux Media Optimizer automatically detects your server's capabilities and only creates formats that are supported. If your server doesn't support modern formats, the plugin will gracefully fall back to your original images.

== Screenshots ==

1. Modern admin interface showing system status and conversion statistics
2. Settings page with quality controls and bulk conversion options
3. Before/after comparison showing dramatic file size reductions
4. Conversion statistics displaying total space saved and performance improvements
5. Attachment details showing optimization status and file size comparisons

== Changelog ==

= 2.0.5 =
* Security improvements: Added comprehensive input sanitization for all settings
* Security improvements: Fixed unescaped error messages in AJAX handlers
* Security improvements: Fixed database query safety in uninstall function
* Security improvements: Escaped JavaScript values in onclick handlers
* Code quality: Refactored settings sanitization to use declarative schema approach
* Code quality: Added REST API input validation and sanitization callbacks
* Internationalization: All error messages are now translatable

= 2.0.0 =
* Added animated GIF support with animation preservation
* Automatic detection and use of Imagick for animated GIF conversion (when available)
* Graceful fallback to GD for static GIFs and animated GIFs when Imagick is unavailable
* Enhanced image format detection for GIF files
* Improved processor selection logic for optimal format support

= 1.0.0 =
* Initial release
* Automatic WebP and AVIF image conversion
* Hybrid approach for maximum browser compatibility
* Bulk processing for existing media
* Modern React-based admin interface
* WordPress integration with Gutenberg blocks
* Quality control settings
* Conversion statistics and performance metrics
* Privacy-compliant architecture ready for optional external service integrations

== Upgrade Notice ==

= 1.0.0 =
Initial release of Flux Media Optimizer by Flux Plugins with comprehensive media optimization features. Perfect for improving your site's Core Web Vitals and SEO performance.

== Privacy ==

All image and video processing happens locally on your server by default. Your media files never leave your WordPress installation unless you explicitly opt-in to external processing services.

**Privacy Policy**: [https://fluxplugins.com/privacy-policy/](https://fluxplugins.com/privacy-policy/)