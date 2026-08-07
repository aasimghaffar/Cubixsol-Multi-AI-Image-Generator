=== Cubixsol Multi AI Image Generator ===
Contributors: cubixsol
Tags: ai, image generation, featured image, stock photos, seo automation
Requires at least: 6.2
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.0.7
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Consolidate 9 premier AI engines and 5 stock libraries into a single, high-performance WordPress workspace.

== Description ==

**Stop wasting 15 minutes sourcing images for every post. Elevate your publishing workflow to a 10-second task.**

Cubixsol Multi AI Image Generator bridges the gap between state-of-the-art AI imagery and your WordPress Media Library. Describe your visual concept, choose an artistic style preset, and watch a professionally sized, SEO-optimized asset deploy instantly into your layout. No external tabs, no tedious downloads, and zero manual metadata entry.

🎬 **See it in action:**

https://www.youtube.com/watch?v=jB2pt5U4pGA

### ⚡ Production-Ready Out of the Box
Experience the core workflow instantly. The built-in **Pollinations.ai** engine functions immediately upon activation—requiring no user accounts, no API keys, and no billing setup. When your enterprise needs scale, seamlessly input credentials for 8 premium external engines to cycle between models with a single click.

### 🎨 9 AI Image Engines. 1 Unified Interface. Zero Vendor Lock-in.
Do not restrict your operations to a single AI provider. Avoid the vulnerabilities of price hikes, model deprecations, or vendor downtime. Cubixsol treats models as interchangeable assets:

* **Pollinations.ai** — 100% free, keyless, and instant generation.
* **OpenAI (GPT Image)** — Utilizing GPT Image 2 for hyper-realistic compliance with complex prompts.
* **Google Gemini** — Powered by Gemini 2.5 Flash Image; rapid processing with accessible free-tier pricing.
* **Grok / xAI** — Access the latest xAI visual intelligence via an OpenAI-compatible pipeline.
* **Stability AI** — Harness the comprehensive artistic and stylization catalog of Stable Diffusion.
* **FLUX (Together AI)** — Leverage Black Forest Labs FLUX.1 for unmatched photorealism and anatomical accuracy.
* **Leonardo.Ai** — Utilize daily creative platform tokens directly inside WordPress.
* **Ideogram** — The gold standard for generating sharp, legible typography embedded in imagery.
* **DeepAI** — A stable, highly economical utility engine built on low-cost credits.

🔄 **Intelligent Failover Protection:** If a chosen engine encounters an API limit or service outage mid-generation, our automatic fallback mechanism silently retries the prompt using your drag-and-drop priority matrix. Your editorial flow remains completely uninterrupted.

### 🛠️ 4 Native Sourcing Workflows (Inside wp-admin)

1.  **Gutenberg & Classic Sidebar Panel:** Generate contextually accurate imagery right alongside your copy. Pick from 23 fine-tuned style presets and bind the winner as the post's Featured Image instantly.
2.  **The AI Image Workspace:** A sandbox environment dedicated to creative research. Generate, expand, and review high-resolution results inside an agile lightbox. Crucially, **nothing hits your server storage until you choose to click Save**.
3.  **Federated Stock Media Search:** Access millions of assets across **Openverse** (keyless), **Pexels**, **Pixabay**, **Unsplash**, and **Giphy** from a singular screen. Imports capture maximum resolution and inject automated photographer attributions.
4.  **Bulk Featured Image Automation:** Target designated post types to instantly identify coverage gaps. Review dynamically generated prompts, apply batch inline adjustments, and deploy a low-overhead queue that respects server capacity and API budgets.

### 🔍 Search Engine Optimization Automated
Establish system-wide filename and alt-text rules using programmatic placeholders like `{title}`, `{prompt}`, and `{style}`. Every generated asset lands in your database completely optimized for Google Image Search, completely eliminating unindexed filenames like `IMG_4382.png`.

### 💰 Granular Budget Control
* **Definitive Key Verification:** The built-in testing interface monitors live endpoints to return absolute valid, invalid, or clear billing-check statuses—ensuring zero unexpected failures during high-stakes publishing.
* **Site-Wide Generation Caps:** Protect your overhead by restricting global daily generation limits, preventing runaway costs from heavy editorial users.
* **Transparency Dashboard:** Track real-time consumption against daily maximums, review overall success metrics, and audit an analytical generation history sorted by engine, prompt, and system outcome.

### 🛡️ Clean, Enterprise-Grade Architecture
* **Zero Frontend Bloat:** Assets and scripts load exclusively within strict backend admin contexts. Your site visitors load zero extra bytes.
* **Hardened Security Protocols:** Strict nonce verification, user capability restrictions, and secure file validation govern every action. API keys are stored server-side and never echoed in input forms.
* **Uncompromising Data Lifecycle:** Features a complete, footprint-free uninstallation routine that purges all custom database configurations upon deletion.

### 🧩 Developer Extensibility
Built natively on object registries and deep hook footprints. Developers can easily extend core systems or seamlessly onboard proprietary generation setups by targeting `aiisp_register_providers`, `aiisp_register_stock_sources`, and `aiisp_style_presets`.

== External services ==

This plugin establishes secure outward connections to third-party services **only upon explicit administrative or editorial commands** (e.g., executing a generation, authenticating an API key, or conducting a live stock asset query). No passive background telemetry is implemented, and no website or user data is ever routed to the plugin author.

Data payloads transmitted to AI engines are limited exclusively to the operational text prompt (inclusive of site-wide boosters or negative constraints), target pixel coordinates/aspect ratios, and the site's API credential.

* **Pollinations.ai** — (Terms: https://pollinations.ai/terms | Privacy: https://pollinations.ai/privacy)
* **OpenAI** — (Terms: https://openai.com/policies/terms-of-use | Privacy: https://openai.com/policies/privacy-policy)
* **Google Gemini API** — (Terms: https://ai.google.dev/gemini-api/terms | Privacy: https://policies.google.com/privacy)
* **xAI (Grok)** — (Terms: https://x.ai/legal/terms-of-service | Privacy: https://x.ai/legal/privacy-policy)
* **Stability AI** — (Terms: https://stability.ai/terms-of-use | Privacy: https://stability.ai/privacy-policy)
* **Together AI (FLUX)** — (Terms: https://www.together.ai/terms-of-service | Privacy: https://www.together.ai/privacy)
* **Leonardo.Ai** — (Terms: https://leonardo.ai/terms-of-service | Privacy: https://leonardo.ai/privacy-policy)
* **Ideogram** — (Terms: https://ideogram.ai/legal/tos | Privacy: https://ideogram.ai/legal/privacy)
* **DeepAI** — (Terms: https://deepai.org/terms-of-service/terms-of-service | Privacy: https://deepai.org/privacy-policy)

Data payloads transmitted to stock indexes are limited to user-supplied search string variables and respective application API identifiers.

* **Openverse** — (Terms: https://docs.openverse.org/terms_of_service.html | Privacy: https://wordpress.org/about/privacy/)
* **Pexels** — (Terms: https://www.pexels.com/terms-of-service/ | Privacy: https://www.pexels.com/privacy-policy/)
* **Pixabay** — (Terms: https://pixabay.com/service/terms/ | Privacy: https://pixabay.com/service/privacy/)
* **Unsplash** — (Terms: https://unsplash.com/terms | Privacy: https://unsplash.com/privacy)
* **Giphy** — (Terms: https://support.giphy.com/hc/en-us/articles/360020027752 | Privacy: https://support.giphy.com/hc/en-us/articles/360032872931)

== Installation ==

1. From the WordPress administration panel, navigate to **Plugins → Add New**. Search for "Cubixsol Multi AI Image Generator" and click **Install Now** (alternatively, upload the compiled plugin file via **Upload Plugin**).
2. Click **Activate**.
3. Launch the dedicated **AI Image Workspace** from your dashboard menu. Core functionality through Pollinations.ai is operational immediately.
4. (Optional) Access the **AI Engines** management interface to input API credentials for desired premium engines, leveraging the integrated **Test** feature to confirm connectivity.
5. Set operational post types within the **Post Types** configuration tab to initialize the workflow sidebars.

== Frequently Asked Questions ==

= Are there ongoing subscription costs to run the plugin? =
No. The core plugin architecture is entirely free. The integration with Pollinations.ai remains keyless and unmetered. For premium engines, you utilize your own directly managed developer accounts—many of which feature generous free usage tiers (such as Google Gemini and Leonardo.Ai). You pay only for what you generate, directly to the provider, without third-party markups.

= Will utilizing this plugin affect frontend site speed? =
Absolutely not. The plugin is built following strict performance guidelines. Script initialization and execution occur exclusively within `wp-admin` workflows under specific context checks. No assets are queued or rendered on the public-facing side of your site.

= How secure are our API credentials? =
Your API keys are securely stored in your isolated site database via the standard WordPress Options API. They are never transmitted back to visual form fields after validation, nor are they ever shared with external tracking networks. Communication occurs solely between your host server and the specific engine endpoint.

= What does the API Test button evaluate? =
The test interface initiates a minimal query to the platform's authentication server. Where standard key validation routines exist, it returns a 0-cost structural check. For engines that validate exclusively during generation tasks (such as Ideogram), the system executes a microscopic, fraction-of-a-cent generation utilizing their most economical model to guarantee clear end-to-end functionality.

= How does the automatic failover system handle provider outages? =
If your active engine encounters a structural error or experiences timeout intervals, the plugin automatically falls back down your defined priority stack to process the request seamlessly. Every failover event is captured and indexed on the administration dashboard for architectural review.

= Can developers hook custom logic or models into this setup? =
Yes. The plugin is explicitly built to be extended. Engines, asset sources, and style lists are built on standard registries. By binding custom classes to `aiisp_register_providers`, teams can easily add proprietary models or customized internal API connections without modifying the core files.

== Screenshots ==

1. Operational Performance Dashboard — tracking site consumption analytics and engine fallback success records.
2. Unified AI Engine Dashboard — credential registration interface featuring live availability badges.
3. Fallback Priority Matrix — drag-and-drop structural controls for system failover protocols.
4. Stock Source Registries — integrated stock catalog options featuring native API test arrays.
5. Media & SEO Automation — global layout rules, style presets, and site production limits.
6. Core Prompt Boosters — unified prompt adjustments alongside pattern-based filename engines.
7. Post-Type Rules Matrix — targeted access settings for sidebars and bulk tools across the site.
8. Live Bulk Generation Queue — visual interface showing prompt adjustments and queue progress.
9. Interactive Image Workspace — custom generative sandbox environment showing full-size image validation before saving.
10. Integrated Stock Image Workspace — global search catalog covering all 5 stock libraries simultaneously.
11. Inline Editor Meta Box — standard layout sidebar rendering generative tools directly beside content fields.
12. Single-Click Deployment Interface — quick-action buttons for feature bindings and instant regenerations.
13. Document-Bound Stock Search — thumbnail asset integration including automated ingestion and attribution mapping.
14. Style Preset Catalog — comprehensive catalog showcasing all 23 built-in artistic style configurations.

== Changelog ==

= 1.0.7 =
* Added a clear advisory on the Pollinations.ai engine card explaining that free shared capacity can be briefly busy, with guidance to retry shortly or switch engines.
* Pollinations.ai failures now return actionable messages (busy service, timeout, or non-image response) instead of raw status codes, and point to Automatic Fallback.

= 1.0.6 =
* Added quick-access Settings and Image Workspace links directly on the Plugins screen row for one-click navigation.
* Plugin and author homepage links now route to the official Cubixsol products directory.
* Key verification now reads each provider's own error payload, so engines that report invalid credentials with a non-standard status (such as Google Gemini's HTTP 400 API_KEY_INVALID) return a definitive rejection instead of an inconclusive notice.
* Together AI (FLUX) calls now target the current api.together.ai host, restoring compatibility with project-scoped API keys issued by the Together console.

= 1.0.5 =
* Resolved structural synchronization bugs where the Classic Editor required hard page reloads to show successful featured image bindings. Layout fields now update instantly across all editor frameworks.

= 1.0.4 =
* Standardized Ideogram diagnostic patterns. The verification routine now processes an operational low-cost micro-generation task to reliably surfaces hidden engine errors, expired credit accounts, or malformed credentials.

= 1.0.3 =
* Deprecated legacy OpenAI structures in favor of `gpt-image-2` integration following provider API adjustments (May 12, 2026). Updated global dimension validation sets.

= 1.0.2 =
* Patched parsing exceptions involving `response_format` within OpenAI pipelines caused by structural mutations on the vendor endpoint.

= 1.0.1 =
* Streamlined the main workspace interface to optimize system memory under concurrent workflows. Removed legacy batch configurations and resolved element alignment problems inside the core lightbox layout.

= 1.0.0 =
* Production launch.
* 9 native AI engines integrated (including keyless Pollinations.ai, GPT Image 2, Gemini, Grok, and FLUX).
* 5 unified stock platform configurations.
* Programmatic SEO file management tools and robust error fallback engine layers.
