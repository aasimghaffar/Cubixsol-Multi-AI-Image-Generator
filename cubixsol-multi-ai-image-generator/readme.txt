=== Cubixsol Multi AI Image Generator ===
Contributors: cubixsol
Tags: ai, image generation, featured image, stock photos
Requires at least: 6.2
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.0.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Generate stunning AI images with 9 engines, search free stock photos, and bulk-create featured images — without leaving WordPress.

== Description ==

**Stop leaving WordPress to find images.** Cubixsol Multi AI Image Generator puts professional image generation and stock photo search directly inside your post editor and Media Library — so every post gets a great image in seconds, not minutes.

Start generating **immediately and for free**: the built-in Pollinations.ai engine needs no account, no API key, and no credit card. When you're ready for more, plug in keys for eight premium engines and switch between them with one click.

### 🎨 Nine AI engines, one interface

* **Pollinations.ai** — 100% free and keyless; works the moment you activate the plugin
* **OpenAI (GPT Image)** — GPT Image 2, photorealistic with precise instruction following
* **Google Gemini** — Gemini 2.5 Flash Image, fast and high quality with a free tier
* **Grok / xAI** — xAI's image model via an OpenAI-compatible API
* **Stability AI** — the Stable Diffusion family's artistic range
* **FLUX (Together AI)** — Black Forest Labs FLUX.1 for exceptional realism
* **Leonardo.Ai** — popular creative platform with free daily tokens
* **Ideogram** — best-in-class readable text inside images
* **DeepAI** — simple API with low-cost credits

### ✅ Test keys before you rely on them

Every API key field has a **Test button** that validates your key against the live service — with honest results: confirmed valid, confirmed invalid, or clearly marked "unverifiable without a paid call" for services that don't support free verification. No silent failures at publish time.

### 🖼️ Image Workspace

A dedicated workspace under **Media → AI Image Workspace**:

* Generate images from a prompt and build up a grid of results as you go
* Search five stock libraries from the same screen
* Preview everything full-size in a lightbox before deciding
* **Nothing touches your Media Library until you click Save** — keep only the winners
* Saving shows the image's permanent URL with a one-click copy button

### 📸 Free stock photos, five libraries

Openverse works with zero setup; add free keys to unlock Pexels, Pixabay, Unsplash, and Giphy. Search from the post editor or the Studio, click, and the full-resolution image lands in your Media Library with photographer credit preserved.

### ⚡ Bulk featured images

Point it at any post type, and it finds every post missing a featured image. Review the list, tweak each auto-suggested prompt, pick a style, and watch it work through the queue one post at a time with live progress — safe for your server and your API budget.

### 🔍 SEO on autopilot

Define alt-text and filename patterns once with `{title}`, `{prompt}`, and `{style}` placeholders — every generated and imported image arrives pre-optimized for image search.

### 🛡️ Built the WordPress way

* Loads **only** in wp-admin, on its own screens — zero front-end weight
* Nonce and capability checks on every action; strict image verification on every import
* Daily generation limits protect paid API budgets
* Automatic engine fallback: if one service is down, the next configured engine takes over (you set the priority by drag and drop)
* Fully translatable, and extensible for developers: register your own engines (`aiisp_register_providers`), stock sources (`aiisp_register_stock_sources`), and style presets (`aiisp_style_presets`)

== External services ==

This plugin relies on third-party external services for its core features. Connections happen **only when a site administrator or editor actively triggers them** — generating an image, clicking a "Test" button next to an API key, searching a stock library, or importing a stock photo. Nothing is transmitted on normal page loads or by site visitors, and no data of any kind is ever sent to the plugin author.

For every AI image engine below, the data sent is: the image prompt typed by the user (plus any site-wide Prompt Booster prefix/suffix/negative text configured in settings), the requested image size or aspect ratio, and the site's own API key for that service where one is required. This is sent only when that engine is used for a generation (as the active engine or as an automatic fallback) or when its "Test" button is clicked to validate a key.

* **Pollinations.ai** — free AI image generation (no account or key). Sent: prompt and image size only. Terms: https://pollinations.ai/terms — Privacy: https://pollinations.ai/privacy
* **OpenAI (DALL-E 3)** — AI image generation. Terms: https://openai.com/policies/terms-of-use — Privacy: https://openai.com/policies/privacy-policy
* **Google Gemini API** — AI image generation. Terms: https://ai.google.dev/gemini-api/terms — Privacy: https://policies.google.com/privacy
* **xAI (Grok)** — AI image generation. Terms: https://x.ai/legal/terms-of-service — Privacy: https://x.ai/legal/privacy-policy
* **Stability AI** — AI image generation. Terms: https://stability.ai/terms-of-use — Privacy: https://stability.ai/privacy-policy
* **Together AI (FLUX)** — AI image generation via Black Forest Labs FLUX models. Terms: https://www.together.ai/terms-of-service — Privacy: https://www.together.ai/privacy
* **Leonardo.Ai** — AI image generation. Terms: https://leonardo.ai/terms-of-service — Privacy: https://leonardo.ai/privacy-policy
* **Ideogram** — AI image generation. Terms: https://ideogram.ai/legal/tos — Privacy: https://ideogram.ai/legal/privacy
* **DeepAI** — AI image generation. Terms: https://deepai.org/terms-of-service/terms-of-service — Privacy: https://deepai.org/privacy-policy

For every stock photo library below, the data sent is: the search term typed by the user, and the site's own API key for that service where one is required. This is sent only when the user searches that library, clicks its "Test" button, or imports one of its photos (the chosen photo file is then downloaded from the service into the site's Media Library).

* **Openverse** — openly-licensed stock photo search, operated by the WordPress project (no key required). Terms: https://docs.openverse.org/terms_of_service.html — Privacy: https://wordpress.org/about/privacy/
* **Pexels** — stock photo search. Terms: https://www.pexels.com/terms-of-service/ — Privacy: https://www.pexels.com/privacy-policy/
* **Pixabay** — stock photo search. Terms: https://pixabay.com/service/terms/ — Privacy: https://pixabay.com/service/privacy/
* **Unsplash** — stock photo search. Terms: https://unsplash.com/terms — Privacy: https://unsplash.com/privacy
* **Giphy** — GIF search. Terms: https://support.giphy.com/hc/en-us/articles/360020027752-GIPHY-User-Terms-of-Service — Privacy: https://support.giphy.com/hc/en-us/articles/360032872931-GIPHY-Privacy-Policy

== Installation ==

1. In your dashboard, go to **Plugins → Add New**, search for "Cubixsol Multi AI Image Generator", and click **Install Now** — or upload the plugin ZIP via **Plugins → Add New → Upload Plugin**.
2. Activate the plugin.
3. Open **AI Image Workspace** in the admin menu. Pollinations.ai works immediately — no key needed.
4. (Optional) On the **AI Engines** tab, paste API keys for the premium engines you want and click **Test** to confirm each one.
5. On the **Post Types** tab, choose where the generator appears. Then open any post — the Cubixsol Multi AI Image Generator box is in the sidebar — or head to **Media → AI Image Workspace** for the full workspace.

== Frequently Asked Questions ==

= Is it really free to use? =

Yes. Pollinations.ai is completely free, keyless, and unlimited. Premium engines are optional and use your own API accounts — several (Google Gemini, Leonardo.Ai, Together AI) include free tiers or daily free credits.

= Do I need any coding knowledge? =

None. Type a description, pick a style, click Generate. The image is sized, named, alt-tagged, and placed in your Media Library automatically.

= Are my API keys safe? =

Keys are stored in your own WordPress database, never displayed back in form fields after saving, and only ever sent to the one service they belong to. The plugin author never receives them.

= What does the Test button actually check? =

It sends a minimal request to the live API using the key you entered. Where a service offers a free verification endpoint, you get a definitive valid/invalid answer at no cost. Ideogram is the exception: it only validates keys on a real generation, so its Test button runs one minimal Turbo generation (a few cents, billed by Ideogram, and disclosed on the engine card) to give you a definitive answer.

= Will it slow down my website? =

No. The plugin loads nothing on your public site — all of its code runs only in wp-admin, and only on its own screens and the editors you enabled.

= What happens if an AI service goes down? =

With fallback enabled, the plugin automatically tries your next configured engine in the priority order you set by drag and drop. Every attempt is logged on the dashboard.

= Can I control costs on paid APIs? =

Yes. Set a daily generation limit (site-wide) under Media & SEO settings; the plugin stops before your budget does. The dashboard shows today's usage against the limit at a glance.

= Which image licenses do stock photos come with? =

Pexels, Pixabay, and Unsplash images use those platforms' free licenses (commercial use allowed, no attribution required). Openverse aggregates Creative Commons works — the license is shown with each result, and some CC licenses require attribution, so check before commercial use.

= Can developers extend it? =

Yes — engines, stock sources, and style presets are all registries with public hooks: `aiisp_register_providers`, `aiisp_register_stock_sources`, and `aiisp_style_presets`. Any class extending the provider base can become a fully integrated engine, including its settings card and key testing.

== Screenshots ==

1. Dashboard — daily usage against your limit, success rate, and the full generation history
2. AI Engines — provider cards with live readiness badges, masked key fields, and one-click key testing
3. More engines plus Automatic Fallback — drag and drop the priority order; engines without a key are skipped
4. Stock Photo Libraries — Openverse works with no key; add free keys for Pexels, Pixabay, Unsplash, and Giphy
5. Media & SEO settings — default size, style preset, daily generation limit, batch size, and featured-image automation
6. Prompt Booster and SEO Automation — site-wide prompt prefix/suffix/negative and alt-text/filename patterns
7. Post Types — choose exactly where the generator meta box and bulk tools appear
8. Bulk Featured Images running — per-post editable prompts, sequential queue, and live progress
9. AI Image Workspace (Media menu) — generate multiple variations from one prompt and preview before saving
10. AI Image Workspace, Stock Photos mode — search all five libraries from one screen
11. Post editor meta box — describe the image and pick from 23 style presets right in the sidebar
12. In-editor result — set as featured image, download, or regenerate in one click
13. In-editor stock photo search — credited thumbnails imported straight to the Media Library
14. Style preset catalogue — 23 presets available across the editor, workspace, and bulk tools

== Changelog ==

= 1.0.4 =
* Ideogram key testing is now definitive: the Test button performs one minimal Turbo generation (disclosed on the engine card), so invalid keys show a clear rejection, unfunded accounts show a clear no-credits notice, and results always match what generation reports

= 1.0.3 =
* Migrated the OpenAI engine to gpt-image-2 after OpenAI removed the DALL-E models from its API (May 12, 2026); supported sizes updated to the GPT Image set

= 1.0.2 =
* Fixed OpenAI (DALL-E 3) generation failing with "Unknown parameter: response_format" after an OpenAI API change; the plugin now accepts both response shapes the API can return

= 1.0.1 =
* Image Workspace now generates one image per click; the per-click image count selector and its related batch-size setting have been removed
* Fixed the close button alignment in the full-size image and save-confirmation popups

= 1.0.0 =
* Initial release
* 9 AI engines: Pollinations.ai (free, keyless), OpenAI (GPT Image), Google Gemini, Grok/xAI, Stability AI, FLUX (Together AI), Leonardo.Ai, Ideogram, DeepAI
* One-click API key testing with honest three-state results (valid / invalid / unverifiable)
* Stock photo search and import: Openverse (keyless), Pexels, Pixabay, Unsplash, Giphy
* Post editor meta box with 23 style presets and full-size lightbox preview
* Image Workspace under Media: batch generation, stock search, preview-before-save, copy-link popup
* Bulk featured image generation with live scan, editable prompts, and sequential progress
* Automatic engine fallback with drag-and-drop priority
* SEO automation: pattern-based alt text and filenames
* Dashboard with daily usage, statistics, and generation history

== Upgrade Notice ==

= 1.0.4 =
Makes Ideogram key testing definitive and consistent with generation errors.

= 1.0.3 =
Required update for OpenAI image generation: migrates from the retired DALL-E 3 model to GPT Image 2.

= 1.0.2 =
Fixes OpenAI (DALL-E 3) image generation after an OpenAI API change.

= 1.0.1 =
Simplifies the Image Workspace to one image per generation and fixes popup close-button alignment.
