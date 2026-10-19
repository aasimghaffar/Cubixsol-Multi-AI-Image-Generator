# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this is

**Cubixsol Multi AI Image Generator** — a WordPress plugin (PHP 7.4+, WP 6.2+, text domain `cubixsol-multi-ai-image-generator`, PHP namespace / prefix `AIISP` / `aiisp_`). It generates images through 9 AI engines and imports from 5 stock libraries, entirely inside wp-admin. Distributed on WordPress.org, so `readme.txt` follows the WP.org readme format and WordPress Coding Standards (note the `phpcs:ignore` justifications throughout).

## Repository layout — two copies of the plugin

- `cubixsol-multi-ai-image-generator/` — **the current, canonical plugin (v1.0.7)**. All recent commits touch only this folder. Make changes here.
- Repo root (`cubixsol-multi-ai-image-generator.php`, `includes/`, `assets/`, …) — a stale copy from the initial commit (header still says 1.0.1); its `readme.txt` is kept in sync with the subfolder's. Don't edit the root code unless explicitly asked.

When bumping a version, update all three: the plugin header `Version:`, the `AIISP_VERSION` constant (both in the bootstrap file), and `Stable tag:` + `== Changelog ==` in `readme.txt`.

## Commands

There is no build step, package manager, linter config or test suite — plain PHP + one jQuery admin script (`assets/js/aiisp-admin.js`) served as-is. To verify changes:

- Syntax-check PHP: `php -l path/to/file.php` (or loop over `includes/**/*.php`).
- Run it: the folder must sit in a WordPress install's `wp-content/plugins/` (the local XAMPP site) and be activated; all features live under wp-admin.

## Architecture

**Bootstrap** — `cubixsol-multi-ai-image-generator.php` defines `AIISP_*` constants, registers `AIISP\Autoloader`, and on `plugins_loaded` calls `aiisp()`, which returns the `AIISP\Plugin` singleton. `Plugin::boot()` only wires anything when `is_admin()` — there is zero front-end footprint, keep it that way.

**Autoloader convention** — `AIISP\Sub\Class_Name` → `includes/sub/class-class-name.php` (namespace segments lowercased to directories, underscores → hyphens). New classes must follow this or they won't load.

**Service access** — everything goes through the singleton's lazy accessors: `aiisp()->options()`, `->providers()`, `->stock()`, `->logger()`, `->usage()`. Don't instantiate these directly.

**Registries drive the UI** — `Providers\Provider_Registry` (AI engines) and `Providers\Stock\Stock_Registry` (stock sources) are the single source of truth. Settings cards, API-key fields, fallback list and size dropdown are all generated from them. Adding an engine = a class extending `Provider_Base` (implement `get_slug/get_label/get_description/get_supported_sizes/generate`, override `requires_api_key`, `get_key_url`, `verify_credentials`) + registering it in the registry constructor (or via the `aiisp_register_providers` action). No view edits needed.
- `generate()` returns `['url' => …]` or `['base64' => …, 'mime' => …]`, or a `WP_Error`.
- Use the base's `post_json()` / `get_json()` (centralised status/JSON/error-message handling with provider-specific hints) and `auth_probe()` for key tests. An inconclusive probe must return code `aiisp_test_inconclusive` — the AJAX layer shows it as an amber "unverified" state rather than a failure.

**Options** — one serialized option `aiisp_settings`. `Options::get_schema()` defines every field (type, default, sanitizer); a `{slug}_api_key` secret field is auto-added for every key-requiring engine/stock source. `save()` drops unknown fields, and an empty secret means "keep the saved key" unless `{field}_clear` is submitted. Extend via the `aiisp_options_schema` filter.

**Generation flow** (`Core\Ajax::run_generation`, shared by meta box, Image Workspace and bulk): prompt check → `edit_post` check → daily limit (`Core\Usage`, transient `aiisp_daily_usage`) → append style preset + Prompt Booster prefix/suffix → global `image_size` → iterate `get_execution_queue()` (active engine, then configured fallbacks) → first success is sideloaded by `Core\Media` (verifies bytes with `wp_check_filetype_and_ext`, applies `{title}/{prompt}/{style}` filename & alt patterns) → logged to `{prefix}aiisp_generation_log`. Preview mode (Image Workspace) skips the Media Library and returns a URL/data URI; `import_preview` saves it later.

**AJAX security pattern** — every `wp_ajax_aiisp_*` endpoint calls `guard($capability)` (nonce `aiisp_ajax_nonce` + capability) first, reads input only via `post_field($key, $sanitizer)`, and adds object-level `edit_post` checks. No `nopriv` endpoints. Follow this for new endpoints.

**Admin surfaces** — top-level menu `aiisp-settings` (tabbed: Dashboard, AI Engines, Media & SEO, Post Types, Bulk Generate — tabs are mapped in `includes/admin/views/settings-page.php`), the Image Workspace at Media → `aiisp-studio`, and the post-editor meta box (`Admin\Meta_Box`, which also owns the filterable `aiisp_style_presets` list). Views in `includes/admin/views/` are plain PHP templates; JS config comes from `wp_localize_script` in `Admin\Admin::enqueue_assets`.

**Lifecycle** — `Activator` creates the log table and merges default options; `Activator::maybe_upgrade` reruns this on `admin_init` when the stored `aiisp_version` differs (for FTP/auto updates). `uninstall.php` removes the options, transient and table.

## Conventions

- Every PHP file begins with the `if ( ! defined( 'ABSPATH' ) ) { exit; }` guard, and every directory has a silent `index.php`.
- All user-facing strings use the `cubixsol-multi-ai-image-generator` text domain; escape on output and sanitize on input per WPCS, and add a justification comment to any `phpcs:ignore`.
- Every external service must be listed in `readme.txt` under `== External services ==` (a WP.org requirement).
