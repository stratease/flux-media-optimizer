# Flux Media Optimizer — Competitive Analysis (Media Optimization & CDN)

**Date:** 2026-09-24  
**Flux version scanned:** 4.4.0  
**Scope:** Media optimization and CDN/offload only. JS/CSS minify, accessibility, alt-text AI, and other non-media features are excluded.  
**Method:** Local source + plugin README/`readme.txt` scans. One exhaustive pass per plugin. Claims below are from this tree, not marketing sites.

| Product | Local folder | Version |
|---------|--------------|---------|
| Flux Media Optimizer | `wp-content/plugins/flux-media-optimizer` | 4.4.0 |
| Converter for Media | `wp-content/plugins/webp-converter-for-media` | 6.2.4 |
| Imagify | `wp-content/plugins/imagify` | 2.2.6 |
| Autoptimize | `wp-content/plugins/autoptimize` | 3.1.15.1 |
| Advanced Media Offloader | `wp-content/plugins/advanced-media-offloader` | 4.5.2 |
| Smush | `wp-content/plugins/wp-smushit` | 4.3.3 (WordPress.org / Pro-shimmed) |
| cf-images | `wp-content/plugins/cf-images` | 1.10.3 |
| EWWW Image Optimizer | `wp-content/plugins/ewww-image-optimizer` | 8.7.7 |

**Smush caveat:** This workspace has the WordPress.org build. CDN, local WebP/AVIF modules, and auto-resize implementations are shimmed (`core/cdn/` missing). Marketing and hook *call sites* remain; Pro behavior is inferred from those plus `readme.txt`.

---

## 1. Flux baseline (what we actually ship)

### 1.1 Optimization

| Area | Flux behavior |
|------|----------------|
| Image inputs | JPEG, PNG, GIF (static + animated), WebP, HEIC/HEIF (static + rare `msf1` sequences) |
| Image outputs | WebP and/or AVIF (both **on** by default) |
| Video inputs | MP4, AVI, MOV, WMV, FLV, WebM, OGG |
| Video outputs | AV1 (MP4) and WebM (both **on** by default) |
| Defaults | WebP Q75, AVIF Q55 / speed 5, AV1 CRF 30, WebM CRF 32 |
| Serving | Direct URL replacement via attachment filters (AVIF > WebP). Optional hybrid `<picture>` **off** by default |
| Processing | Local Imagick/FFmpeg default. Optional licensed Flux cloud (pull + webhook) |
| Originals | Kept on disk. Derivatives written beside originals (`file.jpg.webp`) or stored as CDN URLs in attachment meta |
| Not in pipeline | SVG, PDF compression, BMP, TIFF, ICO, Apple Live Photos |

### 1.2 CDN

- **Network (docs):** Google Cloud CDN behind `cdn.fluxplugins.com`.
- **Model:** Pull. SaaS fetches `pull_file_url` from site uploads; webhook writes `cdn_urls` into `_flux_media_optimizer_converted_files_by_size`.
- **What is offloaded:** Optimized image/video sizes + formats. Non-image MIME (including PDF) can be stored on CDN as `original` when cloud is on — **no PDF transcode**.
- **Local delete after offload:** Not implemented. Local uploads remain.
- **Cache-Control:** Not set in plugin (edge policy is off-plugin).

### 1.3 URL rewrite (critical)

Posts are **never** search-replaced. CDN/optimized URLs live in attachment meta. Serving is runtime:

- Always: `image_downsize`, `wp_get_attachment_url`, `wp_get_attachment_image_src`, `wp_calculate_image_srcset`, `rest_prepare_attachment`, `wp_get_attachment_image`, `post_thumbnail_html`.
- Hybrid only: `wp_content_img_tag`, `the_content`, `render_block`.

**Gap:** Hardcoded `<img src="https://site.com/uploads/...">` in `post_content` is **not** rewritten unless hybrid is on *and* `AttachmentIdResolver` can map the URL. Gutenberg HTML saved before CDN completion can keep local URLs until the block is re-opened/saved.

### 1.4 Removal

- **Deactivate:** Filters stop. Attachment-API URLs fall back to local. Hardcoded CDN URLs in content stay.
- **Uninstall:** Drops plugin tables, `_flux_media_optimizer_*` meta, and `uploads/flux-media-optimizer-converted`. Does **not** rewrite posts. Embedded CDN URLs can 404 if the CDN account is gone.

---

## 2. Competitor feature sets (media + CDN only)

### 2.1 Converter for Media (WebP Converter for Media)

**Positioning:** Same-URL, Accept-based next-gen delivery. Originals untouched.

| Topic | Finding |
|-------|---------|
| Outputs | Free: WebP via GD/Imagick. PRO: AVIF via `api-converter.mattplugins.com` (remote only) |
| Inputs | JPEG/PNG default; GIF optional; existing WebP → AVIF-only path. **Animated GIF rejected** |
| Defaults | Quality **85**, output **WebP only**, auto-convert on, extra feature `only_smaller` on, loader `htaccess` |
| Serving | Default `.htaccess`/`Accept` rewrite to `uploads-webpc/...jpg.webp`. Pass Thru HTML buffer or “Bypassing Nginx” suffix as alternatives. **No `<picture>`**, no `the_content` hook |
| CDN | **No owned CDN.** Optional Cloudflare cache-variant API + BunnyCDN plugin compatibility. Readme: images on a different CDN-only origin break the model |
| Originals | Kept. Derivatives in `wp-content/uploads-webpc/` |
| Removal | Default htaccess: deactivate → originals at same URL (safest in this set). Pass Thru/Bypassing: cached HTML can keep `webpc-passthru.php` or `-optimized` URLs |
| Unique workflow | Server-level format negotiation without changing markup or DB; convert theme/plugin/gallery dirs; WP-CLI regenerate |

### 2.2 Imagify

**Positioning:** Cloud compression API that **replaces files in place**. Optional next-gen sidecars. Not an Imagify-hosted asset CDN.

| Topic | Finding |
|-------|---------|
| Inputs | JPEG, PNG, GIF, WebP, **PDF**. No HEIC/SVG/BMP/TIFF/video |
| Outputs | Optimized original in place + optional `.webp` / `.avif` sidecars |
| Defaults | `optimization_level` **2** (Ultra / “Smart”). First-run reset: auto-optimize on, backup on, resize 2560px. Next-gen **display off**. Format default `webp` |
| CDN | Settings `cdn_url` maps **third-party** CDN hosts → local paths for `<picture>`. WP Rocket + **AS3CF (WP Offload Media)** push adapter. No Imagify edge network |
| Serving | In-place same URL. Optional Apache/Nginx rewrite or full-page `<picture>` buffer (`template_redirect`) |
| Originals | Overwritten. Optional `uploads/backup/`. Restore copies backup back |
| Removal | Local URLs stay valid (files remain optimized). Uninstall deletes options/tables only, not media |
| Unique workflow | PDF optimize; Smart multi-level re-optimize; custom folders; AS3CF-aware pull/push; Accept-header rewrite without HTML change |

### 2.3 Autoptimize (image + CDN slice)

**Positioning:** Runtime HTML rewrite to **ShortPixel pull CDN**. No local derivatives.

| Topic | Finding |
|-------|---------|
| Formats | `.png .gif .jpg .jpeg .webp .avif` via ShortPixel. No SVG/PDF/HEIC/video |
| Defaults | Image opt **off**, lazyload **off**, quality **glossy (2)**, AVIF **off** (`to_webp` when on) |
| CDN | `sp-ao.shortpixel.ai` pull/proxy: `…/client/{to_webp\|to_auto},{q_*},ret_img[,w_X]/{origin_url}`. Separate empty “CDN Base URL” for AO CSS/JS only (not `<img>`) |
| Serving | Output buffer + `autoptimize_html_after_minify`. **Never** updates `post_content` |
| Originals | Untouched on disk. Optimized bytes live on ShortPixel |
| Removal | DB safe. Cached HTML can keep ShortPixel URLs until purge |
| Unique workflow | One-checkbox proxy CDN + WebP/AVIF + srcset-aware resize + lazysizes + LCP preload metabox + CSS background rewrite |

### 2.4 Advanced Media Offloader

**Positioning:** Customer-owned S3-compatible offload. **No native compression.**

| Topic | Finding |
|-------|---------|
| Destinations | S3, R2, B2, DO Spaces, Wasabi, MinIO. **No GCS, Bunny, Azure** in this copy |
| Optimization | None. Uploads Imagify/EWWW/Modern Image Formats sidecars if present |
| URL rewrite | `wp_get_attachment_url`, srcset, `wp_content_img_tag`. **Requires configured Custom Domain** — empty domain = offload with **local URLs** |
| Retention | 0 keep local; 1 smart cleanup (keep original, delete sizes); 2 full migration (delete all local) |
| Removal | No `uninstall.php`. No bulk download/revert. Full migration + deactivate = local 404s |
| Unique workflow | Storage offload, object versioning, mirror delete, WP-CLI, regen thumbs from cloud, filter `advmo_should_offload_attachment` |

### 2.5 Smush (wp-smushit)

**Positioning:** WPMU DEV API in-place smush + (Pro) global image CDN.

| Topic | Finding |
|-------|---------|
| Inputs | JPEG, PNG, static GIF. **Animated GIF skipped.** No PDF/HEIC. SVG lazy-load only |
| Defaults | Lossy **0** (lossless/Basic), strip EXIF **on**, backup **on**, optimize original **on**, CDN **off**, lazyload **off**, next-gen CDN mode WebP when CDN on |
| CDN (Pro / readme) | Pull on `*.assetcdn.net` (CDN 2.0) / legacy `smushcdn.com`. **119 POPs** claimed. Query params: `lossy`, `strip`, `webp`, `avif`, `size=Wx0` auto-resize |
| Serving | In-place same URL for smush. CDN: full-page OB + REST + Elementor (runtime). **No `post_content` CDN writes** (PNG→JPEG *does* rewrite posts) |
| Originals | Overwritten + `.bak` backups + restore |
| Removal | CDN off → local URLs at render. Uninstall default `keep_data` true |
| Unique workflow | Directory smush (theme/plugin folders); CDN auto-resize; attachment URL→ID cache for HTML rewrite; lazyload defaults |

### 2.6 cf-images (Offload Media to Cloudflare Images)

**Positioning:** Push to **Cloudflare Images**; flexible-variant URLs at render.

| Topic | Finding |
|-------|---------|
| Types | WP images: JPEG/PNG/GIF/WebP/SVG. No PDF/video/HEIC. Polish **not** used |
| Delivery | `imagedelivery.net/{hash}/{id}/w={width}` or custom domain `…/cdn-cgi/imagedelivery`. Fallback full size `w=9999` |
| Alternate “CDN” mode | Fuzion (`getfuzion.io`) host-swap; **disables** Cloudflare Images module |
| Defaults | Auto-offload **off**, page parser **off**, full-offload **off**, auto-resize **off** |
| Originals | Kept unless Full Offload deletes them. Restore downloads CF blob and regenerates sizes |
| Removal | Uninstall clears credentials/options, **not** `_cloudflare_image_id`. Full-offload sites break if plugin is removed before Restore |
| Unique workflow | On-the-fly resize/crop without generating WP sizes; disable intermediate generation; custom domain; logged-in users served local (`no-offload-user`); many builder integrations |

### 2.7 EWWW Image Optimizer

**Positioning:** Local binaries + optional API + optional Easy IO (ExactDN) pull CDN.

| Topic | Finding |
|-------|---------|
| Types | JPEG/PNG/GIF/WebP; **SVG** (level 0 default); **PDF** (premium); optional BMP convert. **No HEIC/JXL/video transcode** |
| Animated GIF→WebP | Default off; `force_gif2webp` + cloud key |
| Defaults | JPG/PNG/GIF level **10** (lossless), metadata strip **on**, max 2560×2560, WebP **off**, Easy IO **off**, lazyload **off** |
| Easy IO | Per-site `*.exactdn.com` pull. Query args: `w`, `quality`, `webp`, `avif`, `strip`. Default “all the things” (CSS/JS/fonts). AVIF is Easy IO only (filter default Q60) |
| Local WebP | Sidecars + mutually exclusive Apache / JS / `<picture>` modes (blocked when Easy IO on) |
| Originals | In-place if smaller. Optional `wp-content/ewww/image-backup/` + 30-day cloud backup |
| Removal | Disable Easy IO → local URLs again (DB never stored CDN URLs). Uninstall removes htaccess markers only |
| Unique workflow | Local lossless without SaaS; WP-CLI; WebP delivery mode choice; S3 `remote_push` after optimize; Google Fonts via Easy IO/Bunny |

---

## 3. The seven questions (cross-plugin)

### A. How do they consolidate CDN/offload URLs in posts?

**Industry pattern: almost nobody writes CDN URLs into `post_content`.**

| Product | DB update of posts? | How the new URL appears |
|---------|---------------------|-------------------------|
| **Flux** | No | Attachment meta + WP filters. Hybrid `the_content` only if enabled. REST rewrites editor `source_url` on fetch |
| Converter for Media | No | **Same URL** as stored; `.htaccess`/`Accept` or HTML buffer (Pass Thru) |
| Imagify | No (except not for CDN) | Same local path after in-place optimize. `<picture>` buffer maps CDN host → disk for sidecars |
| Autoptimize | No | Full-page buffer → ShortPixel URLs |
| Advanced Media Offloader | No | `wp_get_attachment_url` / srcset / `wp_content_img_tag` if Custom Domain set |
| Smush | No for CDN; **yes for PNG→JPEG** | OB + REST + Elementor `transform_url()` |
| cf-images | No | Attachment filters + optional page parser + builder integrations |
| EWWW Easy IO | No | `the_content` + page buffer + `image_downsize` + srcset + many plugin filters |

**If a user pastes a local upload URL into a post, then offload/CDN runs:**

1. **Same-URL products** (Converter htaccess, Imagify/Smush/EWWW in-place optimize): the stored URL still works. Best UX for this exact scenario.
2. **Runtime rewrite products** (Flux, AMO, cf-images, Easy IO, Autoptimize, Smush CDN): the stored string stays local. Display of the CDN URL depends on whether a hook can resolve that `<img>` back to an attachment.
3. **Hardcoded absolute URLs without `wp-image-{id}` / attachment ID:** this is the **shared gap**. Flux hybrid off → **no** content rewrite at all. AMO only rewrites `wp_content_img_tag` when WP passes `$attachment_id`. cf-images page parser tries `attachment_url_to_postid`. EWWW/Autoptimize/Smush scan the full HTML buffer (widest coverage).

**Is this a Flux gap?** Yes, for two reasons:

1. Hybrid (the only content rewriter) is **off** by default, and even on it is `<picture>` conversion — not a general CDN URL swap.
2. Flux has **no full-page output buffer** and **no page-builder integrations**. Competitors that sell CDN (Easy IO, Smush CDN, Autoptimize+ShortPixel, cf-images parser) all parse HTML.

Flux is **not** uniquely broken: AMO and default cf-images share the “filters only” model. Flux *is* behind the CDN specialists on **hardcoded / builder / CSS-background** coverage.

**Recommended Flux work (priority order):**

1. Always-on runtime rewrite of content images (not gated on hybrid), using `the_content` + `wp_content_img_tag` + `render_block`.
2. Full-page output buffer (Smush/EWWW/Autoptimize/cf-images pattern) for themes and leftover HTML.
3. Attachment URL→ID cache (Smush) so hardcoded local *or* CDN URLs resolve after offload.
4. Optional one-time “rewrite stored URLs” tool (rare among competitors; would be a differentiator *if* paired with a safe revert).
5. Elementor / page-builder hooks (Smush, EWWW, cf-images already have these).

### B. Media types and technologies Flux does not cover (or they do not)

| Type / tech | Flux | Who has it | Verdict |
|-------------|------|------------|---------|
| **Video transcode (AV1/WebM)** | Yes | Nobody else in this set | **Flux lead** |
| **HEIC/HEIF** (incl. iPhone / sequences → animated WebP) | Yes | AMO only *offloads* HEIC if WP stored it; no converter | **Flux lead** |
| **Animated GIF → animated WebP** | Yes (Imagick) | EWWW optional cloud + `force_gif2webp`. Converter **rejects**. Smush **skips** | **Flux lead** |
| **Animated AVIF output** | Limited / static | None really | Neutral |
| **In-place JPEG/PNG/GIF compression** (keep mime) | No — convert-only | Imagify, Smush, EWWW, Autoptimize/ShortPixel | **Flux gap** |
| **PDF optimization** | CDN store only, no compress | Imagify, EWWW premium | **Flux gap** |
| **SVG optimization** | No | EWWW (`svg_level`) | **Flux gap** (smaller) |
| **Lossless user mode** | Internal Imagick flag only | Imagify, Autoptimize, Smush, EWWW | **Flux gap** |
| **only_smaller** (drop derivative if larger) | No | Converter default | **Flux gap** |
| **Max-dimension resize** | No product setting (uses WP sizes) | Imagify 2560, EWWW 2560, Converter PRO | **Flux gap** |
| **Lazy load** | Hybrid picture `loading=lazy` only | Autoptimize, Smush, EWWW | **Flux gap** |
| **Directory / theme / plugin images** | No | Converter dirs, Imagify custom folders, Smush directory smush | **Flux gap** |
| **PNG→JPEG** | No | Smush, EWWW | Nice-to-have |
| **On-the-fly CDN resize/crop** | No — pre-generates WP sizes | cf-images flexible variants, Easy IO, Smush CDN `?size=`, ShortPixel `w_`/`h_` | **Flux CDN gap** |
| **Accept-header same-URL WebP/AVIF** | No | Converter, Imagify rewrite, EWWW htaccess | **Flux gap** (uninstall safety + cache friendliness) |
| **Customer S3/R2 offload** | No | AMO, Imagify+AS3CF, EWWW remote_push | Different product; see §5 |
| **CSS/JS/font CDN** | No | Easy IO “all the things”, Autoptimize CDN Base URL | Out of scope unless Flux expands CDN |

### C. Is their CDN faster or more effective than Flux (Google Cloud CDN)?

Source scan **cannot benchmark TTFB**. Architecture comparison only:

| CDN | Operator / edge | Model | Extra capability vs Flux |
|-----|-----------------|-------|--------------------------|
| **Flux** | Google Cloud CDN (`cdn.fluxplugins.com`) | Pull → store optimized derivatives | Pre-generated WebP/AVIF/AV1/WebM. No on-the-fly transform API in plugin |
| Converter | None owned | Origin + CF cache variants | Negotiation at origin/CF; no global store |
| Imagify | None owned | User Rocket/AS3CF | Compression API only (`app.imagify.io`) |
| Autoptimize | ShortPixel (`sp-ao.shortpixel.ai`) | Pull/proxy of **origin URL** | On-the-fly quality + WebP/AVIF + resize in the URL |
| AMO | **Customer** CloudFront / CF / R2 public domain | Push to bucket | Effectiveness = customer’s CDN. Can be Cloudflare *or* Google *or* worse |
| Smush Pro | WPMU DEV `assetcdn.net` (119 POPs claimed) | Pull + query transforms | Auto-resize + format flags at edge |
| cf-images | **Cloudflare Images** (`imagedelivery.net`) | Push + flexible variants | Largest public edge map in this set; crop/resize per request; no extra WP thumbnails if “disable generation” |
| EWWW Easy IO | Exactly WWW `*.exactdn.com` (Photon-style) | Pull + query transforms | Auto-scale to content width; AVIF at edge; CSS/JS too |

**Effectiveness (not raw POP count):**

- **Cloudflare Images** is the strongest *productized* CDN here: transform-at-edge, optional no-thumbnail disk, custom domain, restore path. Cloudflare’s anycast network is generally denser than a single Google Cloud CDN hostname, but Google’s backbone is excellent for cache hits. **Neither is “faster” without measurement.**
- **On-the-fly CDNs** (Easy IO, Smush, ShortPixel, CF Images) win on **bytes to the visitor**: they can emit the exact display width. Flux always serves a pre-baked WP size (or full). That is often a larger real-world win than shaving 10 ms of edge RTT.
- **Pull CDNs that embed the origin URL** (ShortPixel, Easy IO, Smush) fail on **localhost / private networks** and depend on origin being publicly fetchable — same class of constraint as Flux pull.
- **Push + delete-local** (AMO full migration, cf-images full offload) wins **origin bandwidth and disk**, loses **plugin-removal safety**.
- Flux advantage: video on the same CDN; Google network; local files remain so origin is a fallback.

**Honest conclusion:** Flux’s Google CDN is competitive for **cached, pre-generated** assets. It is **less effective** than CF Images / Easy IO / Smush CDN for **responsive, on-the-fly sizing and format negotiation**. That is the CDN product gap, not the backbone brand.

### D. Are their default optimization settings better or more intelligent?

| Product | Default stance | Intelligence |
|---------|----------------|--------------|
| **Flux** | Next-gen **on** (WebP+AVIF, AV1+WebM). Hybrid **off**. Bulk **off**. Cloud **off**. Fixed Q75/Q55 | Modern formats by default. No lossless. No only_smaller. No content-aware quality. AVIF Q55 is reasonable; WebP 75 matches EWWW’s WebP filter default |
| Converter | WebP only, Q85, **only_smaller**, GIF off | Conservative quality; refuses worse-than-original files — smarter than Flux on file size regressions |
| Imagify | Ultra (level 2) after reset; next-gen **display off** | “Smart” is API-side, not local heuristics. Aggressive compression, cautious display |
| Autoptimize | Everything **off** | Safe install; user must opt in |
| AMO | Auto-offload **on**, retain local | Offload-first, no quality opinions |
| Smush | Lossless, strip EXIF, backup on, CDN off | Safest visual default; weaker Core Web Vitals out of the box |
| cf-images | All modules **off** | Safe; CF decides format (marketing) but **no `format=auto` in PHP URLs** |
| EWWW | Lossless level 10, WebP/Easy IO **off** | Safest visual default; next-gen is opt-in |

**Flux vs field:**

- **More aggressive / modern** than Smush/EWWW/Autoptimize/Converter on *formats enabled at install* — good for LCP if serving works.
- **Less intelligent** than Converter (`only_smaller`) and Imagify/EWWW (in-place + backup + quality *or* lossless choice).
- **Hybrid off** is the weakest default relative to the URL-consolidation problem: Flux enables AVIF URLs via filters but does not fix hardcoded content.
- Video defaults (AV1+WebM on) are unique; may surprise hosts without FFmpeg (Overview chips already surface this).

**Recommended default tweaks:**

1. Keep next-gen formats on (differentiation).
2. Add `only_smaller`: skip/delete a derivative when it exceeds the same-size original (Converter).
3. Expose lossless / “visually lossless” as a named preset alongside current Q75/Q55 (do not silently change numbers without a preset story).
4. Consider a max-width clamp aligned with WP `big_image_size_threshold` (2560) — Imagify/EWWW.
5. Turn on **non-hybrid** content URL rewrite by default (see A); keep `<picture>` experimental.

### E. Originals vs optimized derivatives?

| Product | Originals | What visitors get |
|---------|-----------|-------------------|
| **Flux** | Always kept locally. Cloud may also store `original` on CDN | AVIF > WebP > original via filters. REST `source_url` prefers WebP |
| Converter | Untouched | Negotiated WebP/AVIF at **same URL** |
| Imagify | Overwritten; optional backup folder | Optimized original URL; optional picture/rewrite to sidecar |
| Autoptimize | Untouched | ShortPixel derivative; origin fetched by CDN |
| AMO | Policy 0/1/2 | Cloud object (same bytes, not recompressed) |
| Smush | Overwritten; `.bak` | Optimized local or CDN transform of origin |
| cf-images | Kept or deleted (full offload) | CF flexible-variant URL |
| EWWW | Overwritten if smaller; backups optional | Optimized local and/or ExactDN transform |

Flux is in the **sidecar / keep-original** camp (with Converter), not the overwrite camp. That is the right default for a convert-to-AVIF product: uninstall and you still have JPEGs.

**Gaps vs overwrite camp:** no “restore original” UI (less needed). **Gaps vs offload camp:** no “delete local after CDN verified” for disk-constrained hosts.

### F. Can customers remove the plugin or CDN without broken media?

| Product | Disable plugin | Disable CDN only | Worst case |
|---------|----------------|------------------|------------|
| **Flux** | Attachment-API URLs → local. Hardcoded CDN URLs stay | Same; meta still used while plugin on | Uninstall + CDN cancelled + CDN URLs baked into content/cache |
| Converter (htaccess) | **Safest** — same URL, originals | N/A | Pass Thru cache; leftover htaccess if deleted without deactivate |
| Imagify | Optimized files remain at old URLs | N/A (not their CDN) | Lost backups if user never enabled backup |
| Autoptimize | Fresh HTML → local | Quota/account death + cached ShortPixel HTML | Page cache |
| AMO retain-local | Local URLs work | Empty custom domain already used local URLs | **Full migration** + deactivate |
| Smush | Local optimized files work | CDN off → local at render | PNG→JPEG content already rewritten (still valid) |
| cf-images keep-local | Local files work | Disconnect leaves meta; URLs revert to local | **Full offload** without Restore |
| EWWW | Optimized files remain | Easy IO off → local | Cached ExactDN HTML |

**Flux is mid-pack on safety:** better than AMO/cf-images full-offload (we never delete origin), worse than Converter/Imagify same-URL, same class of cache risk as every runtime CDN rewriter.

**Missing Flux safety features (competitors have pieces of these):**

- Explicit “CDN disconnect” that keeps serving local derivatives.
- Download-from-CDN / restore-local (cf-images Restore, AMO lacks this — opportunity).
- Documented “purge page cache after disable” (Autoptimize).
- Do not persist CDN URLs into Gutenberg block HTML on save (or provide revert). Flux REST rewrite can *encourage* editors to save CDN URLs into blocks — that **increases** removal risk. Worth auditing `rest_prepare_attachment` vs what gets written back to `post_content`.

### G. Valuable workflows Flux cannot do today

Ranked by how often they appear as the *reason* someone picks a competitor:

1. **On-the-fly responsive CDN transforms** (CF Images, Easy IO, Smush CDN, ShortPixel) — one master file, infinite widths, less disk.
2. **Same-URL Accept negotiation** (Converter, Imagify/EWWW htaccess) — works with any hardcoded URL, any cache that respects `Vary: Accept`, trivial uninstall.
3. **In-place compression + backup/restore** (Imagify, Smush, EWWW) — shrink JPEG/PNG without requiring AVIF-capable browsers or extra files.
4. **Lazy load + LCP preload + skip-first-N** (Autoptimize, Smush, EWWW).
5. **Full-page HTML + builder URL rewrite** (all CDN specialists).
6. **Customer-owned bucket offload + delete-local** (AMO) — hosting bill / disk, not quality.
7. **PDF (and SVG) optimization** (Imagify, EWWW).
8. **Theme/plugin/custom-folder scan** (Converter, Imagify, Smush directory).
9. **only_smaller + quality presets** (Converter, Imagify Ultra/Aggressive/Normal).
10. **AS3CF / existing offload coexistence** (Imagify, EWWW) — Flux CDN + Offload Media together is an unsolved combo.
11. **Disable WP intermediate sizes** and let the CDN crop (cf-images) — huge disk win on image-heavy sites.
12. **WP-CLI / bulk restore / per-attachment restore** — Flux has convert-all CLI and Media Library status; lacks restore-original and CDN-disconnect CLI.

**Workflows Flux already uniquely owns in this set:** video AV1/WebM; HEIC (including animated HEIF → WebP); atomic multi-size commit; license-aware local↔cloud retry; attachment React savings island; Media Library Optimized/Pending/Failed filters.

---

## 4. Feature matrix

Legend: **Y** = in this local copy. **P** = paid / Pro / license. **~** = partial. **S** = Smush Pro inferred (org shim). **—** = no.

| Capability | Flux | Converter | Imagify | Autoptimize | AMO | Smush | cf-images | EWWW |
|------------|------|-----------|---------|-------------|-----|-------|-----------|------|
| WebP output | Y | Y | Y | Y (CDN) | — | S / CDN | CF edge | Y |
| AVIF output | Y | P remote | Y | Optional `to_auto` | — | S / CDN | undocumented in PHP | Easy IO |
| Animated GIF → WebP | Y | — | ~ API | ~ as GIF | — | — | CF limits | P optional |
| HEIC in | Y | — | — | — | offload only | — | — | — |
| Video optimize | Y | — | — | — | offload only | — | — | posters only |
| In-place JPEG/PNG | — | — | Y | CDN | — | Y | Fuzion compress | Y |
| PDF optimize | — | — | Y | — | offload | — | — | P |
| SVG optimize | — | — | — | — | offload | lazy only | offload | Y |
| Keep originals | Y | Y | backup | Y | policy | backup | default | backup |
| Overwrite originals | — | — | Y | — | — | Y | — | Y |
| Delete local after CDN | — | — | — | — | Y | — | Y full | — |
| Same-URL Accept rewrite | — | Y | Y | — | — | — | — | Y |
| `<picture>` / hybrid | optional | — | optional | picture lazy | — | — | — | optional |
| Full-page HTML rewrite | — | Pass Thru | picture | Y | — | Y | optional | Y |
| `post_content` DB rewrite | — | — | — | — | — | PNG2JPG | — | — |
| Pull CDN (vendor) | Y P | — | — | ShortPixel | — | S | — | Easy IO P |
| Push to object storage | pull-to-Flux | — | via AS3CF | — | Y | — | CF Images | remote_push |
| On-the-fly resize | — | — | — | Y | — | S | Y | Easy IO |
| Lazy load | ~ | — | — | Y | — | Y | — | Y |
| Max dimension setting | — | P | Y | CDN max | — | Y | CF w= | Y |
| only_smaller | — | Y | — | — | — | — | — | skip if larger |
| Directory / non-library | — | Y | Y | — | — | Y | — | Y |
| Page builder hooks | — | — | AMP | — | — | Y | Y | Y |
| WP-CLI | Y | Y | — | — | Y | — | Y | Y |
| Restore originals | N/A sidecar | delete derivatives | Y | N/A | — | Y | CF blob | Y |

---

## 5. Ranked improvements for Flux

Improvements are scoped to **media optimization and CDN**. Each item names the competitor proof and the Flux gap it closes.

### P0 — Close the post-URL gap (Question A)

These are the highest-ROI product gaps against every CDN competitor.

1. **Always-on content URL rewrite (not hybrid-gated)**  
   Rewrite `<img>` / srcset / Gutenberg image blocks whenever a converted or CDN URL exists. Proof: EWWW `the_content` @ 999999, cf-images `wp_content_img_tag`, AMO `PostContentImageTagObserver`.

2. **Full-page output buffer**  
   Catch theme builders, widgets, leftover HTML, CSS `url()` in inline style. Proof: Autoptimize, Smush `Transformation_Controller`, EWWW `filter_page_output`, cf-images page parser, Converter Pass Thru.

3. **Attachment URL ↔ ID map (local *and* CDN)**  
   Flux already has `AttachmentIdResolver` + `_flux_media_optimizer_file_urls`. Use it on the buffer the way Smush uses `Attachment_Url_Cache_Controller`. This is how hardcoded post URLs start showing CDN files *without* DB writes.

4. **Audit REST → block save**  
   `rest_prepare_attachment` rewriting `source_url` to WebP/CDN can persist CDN hosts into `post_content` when authors save. That is the one way Flux might *create* removal-breakage. Decide: rewrite at render only, or rewrite-on-save plus a revert tool.

5. **Page-builder integrations**  
   Elementor `elementor/frontend/the_content` (Smush, EWWW, cf-images). Minimum viable set: Elementor, WooCommerce product gallery.

### P1 — Make the CDN as *effective* as CF Images / Easy IO (Question C)

6. **On-the-fly (or cached) width/format URL API**  
   Serve `cdn…/id/w=768` (CF Images) or `?w=768&avif=1` (Easy IO) instead of only WP registered sizes. Biggest real-world byte win vs Google-vs-Cloudflare branding.

7. **CDN disconnect + local fallback switch**  
   One setting: stop rewriting to `cdn.fluxplugins.com`, keep local sidecars. Document cache purge. Proof: Easy IO disable, Smush CDN off.

8. **Optional delete-local after CDN verified** (off by default)  
   AMO retention + cf-images full offload. Pair with Restore-from-CDN (cf-images blob download). Do **not** make this default.

9. **Vary / cache guidance**  
   Flux does not set Cache-Control. Easy IO and Converter think about `Vary: Accept`. Publish edge cache policy; consider `?original` escape hatch (Converter).

### P2 — Optimization intelligence and formats (Questions B, D, E)

10. **`only_smaller` default on**  
    Converter `LargerFilesOperator`. Avoid AVIF that is bigger than the JPEG.

11. **In-place lossless/lossy compress of the original mime**  
    Imagify/Smush/EWWW reason-to-buy when AVIF is unwanted (email, older clients, PDF-less print). Can be local Imagick or cloud op.

12. **Named quality presets**  
    Map current numbers to Normal / Balanced / Smallest (Imagify 0/1/2, Autoptimize lossy/glossy/lossless, Smush Basic/Super/Ultra). Keep Q75/Q55 as Balanced.

13. **Max dimension clamp** (default 2560, align `big_image_size_threshold`)  
    Imagify, EWWW. Stops 8K camera uploads from becoming 8K AVIF.

14. **PDF optimization** (cloud-only is fine)  
    Imagify already does this on the same API-upload pattern Flux uses for images. High-value for brochure / WooCommerce sites.

15. **SVG optimize** (optional, EWWW-style, off by default).

16. **Lossless checkbox** for WebP (Imagick already has `lossless` internally).

### P3 — Workflows that win deals (Question G)

17. **Accept-header / same-URL serving mode**  
    Converter’s killer feature for “I pasted URLs everywhere.” Apache/Nginx rules *or* a `uploads` rewrite to Flux sidecars. Makes Questions A and F mostly disappear for local mode.

18. **Lazy load** (Autoptimize lazysizes / Smush / EWWW) with skip-first-N and exclusions. Hybrid `loading=lazy` is not a product.

19. **Scan theme/plugin/uploads extras** (Converter directories, Smush directory smush).

20. **Imagify/EWWW/AMO coexistence**  
    If the site already offloads to S3, Flux should pull from the public CDN URL (Imagify AS3CF adapter pattern) instead of assuming local `get_attached_file()`.

21. **Media Library restore / re-download from Flux CDN** and per-size “serve original.”

22. **Background CSS image rewrite** (Autoptimize, Smush parser regex).

### Do not copy (low value or anti-pattern)

- **DB search-replace of all post URLs as the primary mechanism.** The market rejected this (except Smush PNG2JPG). Runtime + optional one-time tool is enough.
- **Overwrite originals without backup.** Flux’s sidecar model is a feature. If in-place compress is added, require backup (Imagify/Smush default).
- **JS/CSS minify** (Autoptimize core). Out of scope.
- **Fuzion/Eluxo-style second CDN** (cf-images) that disables the primary product.

---

## 6. Suggested sequencing

| Phase | Ship | Closes |
|-------|------|--------|
| **1** | Content + block + buffer rewrite; URL↔ID cache; REST-save audit | Question A gap; hardcoded posts |
| **2** | `only_smaller`; quality presets; max dimension; lossless WebP | Question D intelligence |
| **3** | CDN disconnect; optional local delete + restore; on-the-fly width (even if only a few breakpoints) | Questions C, F |
| **4** | Same-URL htaccess mode; lazyload; directory scan | Question G workflows |
| **5** | PDF; in-place compress; offload-plugin compat | Remaining type/workflow gaps |

Phase 1 is the only work that directly answers “do posts show the new CDN URL?” today. Phases 2–3 make defaults and the Google CDN *offering* competitive with Easy IO / Cloudflare Images. Phases 4–5 are expansion.

---

## 7. Source map (agents)

Feature sets were produced from local trees:

- [Flux Media Optimizer](632516d4-8da0-4e65-8a1b-79dad771b868) — `WordPressProvider`, `Settings`, `ExternalApiClient`, `WebhookController`
- [Converter for Media](d37ac7bb-ac5b-48a8-a69b-9d7e3d7795e1) — `HtaccessLoader`, `PassthruLoader`, `ImagesQualityOption`
- [Imagify](4115ae19-73c1-421c-b5c4-63f2c5af3aa9) — `class-imagify-options.php`, `Picture/Display.php`, `CDN/CDN.php`
- [Autoptimize](26c80756-24a2-415c-b2fb-ee9ffa014c13) — `autoptimizeImages.php`, `autoptimizeConfig.php`
- [Advanced Media Offloader](ac717fc8-efc7-4271-acce-a801b5bb6a12) — `AttachmentUrlObserver`, `CloudAttachmentUploader`
- [Smush](5050c35d-d691-4769-a7c2-6db4ca5b9309) — `class-settings.php`, `class-transformation-controller.php` (CDN Pro missing)
- [cf-images](1925df3c-75eb-486a-924d-d79ae35b34d3) — `class-cloudflare-images.php`, `class-page-parser.php`
- [EWWW](d3505ec0-c17b-4bc0-959c-9d50c048746e) — `class-plugin.php`, `class-exactdn.php`

---

## 8. Short answers (executive)

1. **Posts:** Nobody in this set bulk-updates `post_content` for CDN. They rewrite at runtime or keep the same URL. Flux’s rewrite is **narrower** (no page buffer, hybrid off). Hardcoded post URLs are a **real Flux gap**, shared with AMO/default cf-images, already solved by EWWW/Autoptimize/Smush/CF parser.
2. **Types:** Flux leads on **video + HEIC + animated GIF→WebP**. Flux lags on **in-place compress, PDF, SVG, lazyload, directory scan, on-the-fly CDN resize**.
3. **CDN speed:** Cannot crown a winner from code. Cloudflare Images / Easy IO / Smush are **more effective** because they transform per request. Google Cloud CDN is not the weak point; **missing transforms** are.
4. **Defaults:** Flux is **bolder on formats**, **weaker on intelligence** (`only_smaller`, lossless, max-width, content rewrite).
5. **Originals:** Flux keeps them (good). Does not overwrite. Does not offer delete-local or restore-from-CDN.
6. **Removal:** Safe if content still uses attachment APIs and local files remain. Unsafe if CDN URLs were saved into posts/cache and the CDN is cancelled. Safer than full-offload AMO/CF; less safe than Converter same-URL.
7. **Workflows to steal first:** HTML-wide URL rewrite, on-the-fly CDN sizing, same-URL Accept mode, only_smaller, lazyload, PDF, offload-plugin compatibility.
