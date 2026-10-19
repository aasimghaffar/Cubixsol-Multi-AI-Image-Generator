# Cubixsol Multi AI Image Generator

![Version](https://img.shields.io/badge/version-1.0.7-blue)
![WordPress](https://img.shields.io/badge/WordPress-6.2%2B-21759b)
![PHP](https://img.shields.io/badge/PHP-7.4%2B-777bb4)
![License](https://img.shields.io/badge/license-GPLv2%2B-green)

Generate AI images with **9 engines** and import photos from **5 stock libraries** without leaving WordPress. You can set featured images, run bulk generation, apply SEO-friendly filenames and alt text automatically, and fall back to another engine when one fails.

▶️ [Watch the demo](https://www.youtube.com/watch?v=jB2pt5U4pGA) · 🌐 [cubixsol.com/products](https://cubixsol.com/products/)

---

## Table of contents

- [Features](#features)
- [Supported services](#supported-services)
- [Requirements](#requirements)
- [Installation](#installation)
- [Configuration](#configuration)
- [Usage](#usage)
- [Permissions](#permissions)
- [Developer guide](#developer-guide)
- [Project structure](#project-structure)
- [Data & privacy](#data--privacy)
- [Uninstalling](#uninstalling)
- [Changelog](#changelog)
- [License](#license)

---

## Features

- **Works out of the box.** Pollinations.ai is free and needs no API key, so you can generate images as soon as the plugin is active.
- **9 AI engines, one interface.** Switch engines with one click and use your own API keys.
- **Automatic fallback.** If the active engine fails, the plugin retries with the next configured engine in your drag-and-drop order.
- **Four workflows:**
  1. **Editor panel**: generate an image beside your content (Block Editor and Classic Editor) and set it as the featured image.
  2. **AI Image Workspace** (*Media → AI Image Workspace*): preview and compare results; nothing is saved to the Media Library until you click Save.
  3. **Stock search**: search Openverse, Pexels, Pixabay, Unsplash and Giphy, then import with photographer credit.
  4. **Bulk Generate**: find posts with no featured image, edit the prompts and fill them in through a sequential queue that limits load on your server.
- **23 style presets**, a **Prompt Booster** (site-wide prefix and suffix) and a global **negative prompt**.
- **SEO automation.** Filenames and alt text are built from patterns using `{title}`, `{prompt}` and `{style}`.
- **Cost control.** Test API keys before saving, set a site-wide daily generation limit, and see usage stats and generation history on the dashboard.
- **No front-end load.** Every script and service loads only in wp-admin.

## Supported services

### AI engines

| Engine | Model / API | API key |
|---|---|---|
| Pollinations.ai | `image.pollinations.ai` | Not required (free) |
| OpenAI | `gpt-image-2` | [platform.openai.com](https://platform.openai.com/api-keys) |
| Google Gemini | `gemini-2.5-flash-image` | [aistudio.google.com](https://aistudio.google.com/apikey) |
| Grok (xAI) | `grok-2-image` | [console.x.ai](https://console.x.ai/) |
| Stability AI | Stable Image Core | [platform.stability.ai](https://platform.stability.ai/account/keys) |
| FLUX (Together AI) | `FLUX.1-schnell` | [api.together.ai](https://api.together.ai/settings/api-keys) |
| Leonardo.Ai | Leonardo REST API | [app.leonardo.ai](https://app.leonardo.ai/settings) |
| Ideogram | Ideogram v3 (Turbo) | [ideogram.ai](https://ideogram.ai/manage-api) |
| DeepAI | `text2img` | [deepai.org](https://deepai.org/dashboard/profile) |

### Stock libraries

| Source | API key |
|---|---|
| Openverse | Not required |
| Pexels | [pexels.com/api](https://www.pexels.com/api/) |
| Pixabay | [pixabay.com/api/docs](https://pixabay.com/api/docs/) |
| Unsplash | [unsplash.com/developers](https://unsplash.com/developers) |
| Giphy | [developers.giphy.com](https://developers.giphy.com/dashboard/) |

## Requirements

| Requirement | Minimum |
|---|---|
| WordPress | 6.2 (tested up to 7.0) |
| PHP | 7.4 |
| Server | Outbound HTTPS to the APIs you enable; a writable `wp-content/uploads` directory |

## Installation

### Option 1: WordPress plugin directory

1. In wp-admin, go to **Plugins → Add New Plugin**.
2. Search for **Cubixsol Multi AI Image Generator**.
3. Click **Install Now**, then **Activate**.

### Option 2: Upload a ZIP

1. Download the plugin `.zip`.
2. Go to **Plugins → Add New Plugin → Upload Plugin**, select the ZIP and click **Install Now**.
3. Click **Activate Plugin**.

### Option 3: Manual / FTP

1. Copy the `cubixsol-multi-ai-image-generator` folder into `wp-content/plugins/`.
2. Go to **Plugins** in wp-admin and activate **Cubixsol Multi AI Image Generator**.

### Option 4: From source (development)

```bash
cd wp-content/plugins
git clone <repository-url> cubixsol-multi-ai-image-generator-src
# The installable plugin is the cubixsol-multi-ai-image-generator/ subfolder:
cp -r cubixsol-multi-ai-image-generator-src/cubixsol-multi-ai-image-generator ./
```

No build step is needed. The plugin is plain PHP plus one admin JavaScript file.

On activation, the plugin creates the `{prefix}aiisp_generation_log` table and stores default settings in the `aiisp_settings` option.

## Configuration

Open **AI Image Workspace** in the admin sidebar (or click **Settings** on the plugin's row in the Plugins list).

| Tab | What to configure |
|---|---|
| **Dashboard** | Today's usage against the daily limit, success rates and generation history (which you can clear). |
| **AI Engines** | API keys for each engine and stock source, a **Test** button for each key, the active engine, the automatic fallback toggle and the drag-and-drop fallback order. |
| **Media & SEO** | Default image size, default style preset, daily limit, automatic featured images, Prompt Booster prefix/suffix, negative prompt, and filename and alt-text patterns. |
| **Post Types** | The post types that show the editor panel and appear in Bulk Generate (Posts and Pages by default). |
| **Bulk Generate** | Scan for posts with no featured image and run the queue. |

**Key test results**

- 🟢 **Accepted**: the provider confirmed the key.
- 🔴 **Rejected**: the key is invalid or the account isn't funded.
- 🟠 **Unverified**: the provider has no free way to check a key; it will be checked on your first generation.

> **Note:** Ideogram checks keys only on real requests, so its **Test** runs one minimal Turbo-speed generation. Rejected keys and unfunded accounts are refused before anything is billed.

**Filename / alt-text placeholders:** `{title}` (post title), `{prompt}` (the prompt) and `{style}` (the style preset).

## Usage

**Editor panel.** Open any post of an enabled post type and find the **Cubixsol Multi AI Image Generator** box. Enter a prompt, pick a style, click **Generate Image**, then **Set featured** (or turn on automatic featured images under *Media & SEO*). The featured image updates right away in both the Block Editor and the Classic Editor.

**AI Image Workspace.** Go to **Media → AI Image Workspace** to try prompts or search stock photos. Results appear as previews; click **Save** to add an image to the Media Library and copy its permanent URL.

**Bulk Generate.** Pick a post type and click **Scan for posts** to list posts without a featured image (up to 50 at a time). Edit any prompt (the post title is used by default), then click **Generate for selected posts**. Images are generated one at a time and set as each post's featured image.

## Permissions

| Action | Required capability |
|---|---|
| Settings, API keys, key tests, clearing history | `manage_options` (Administrator) |
| Generating images, stock search and import, the Image Workspace | `upload_files` |
| Setting a featured image or bulk-generating for a post | `edit_posts` plus `edit_post` on that specific post |

Every AJAX request checks a nonce and the user's capability. There are no endpoints for logged-out visitors.

## Developer guide

The plugin is built on registries, so the settings cards, key fields, fallback list and size dropdown update automatically when you register something new.

### Hooks

| Hook | Type | Purpose |
|---|---|---|
| `aiisp_register_providers` | action | Register a custom AI engine. |
| `aiisp_register_stock_sources` | action | Register a custom stock source. |
| `aiisp_style_presets` | filter | Add, remove or rename style presets (`slug => label`). |
| `aiisp_options_schema` | filter | Add settings fields that are saved and sanitized automatically. |

### Adding an AI engine

```php
use AIISP\Providers\Provider_Base;

class My_Engine extends Provider_Base {
	public function get_slug()            { return 'my_engine'; }
	public function get_label()           { return 'My Engine'; }
	public function get_description()     { return 'In-house image model.'; }
	public function get_supported_sizes() { return array( '1024x1024', '1792x1024' ); }

	public function generate( $prompt, $args = array() ) {
		if ( ! $this->is_configured() ) {
			return $this->missing_key_error();
		}

		$data = $this->post_json(
			'https://api.example.com/v1/images',
			array( 'prompt' => $prompt, 'width' => $args['width'], 'height' => $args['height'] ),
			array( 'Authorization' => 'Bearer ' . $this->get_api_key() )
		);

		if ( is_wp_error( $data ) ) {
			return $data;
		}

		// Return either [ 'url' => ... ] or [ 'base64' => ..., 'mime' => 'image/png' ].
		return array( 'url' => $data['image_url'] );
	}
}

add_action( 'aiisp_register_providers', function ( $registry ) {
	$registry->register( new My_Engine() );
} );
```

When an engine requires a key (the default), the plugin adds a `my_engine_api_key` setting and its form field automatically. To support the **Test** button, override `verify_credentials()`; the `auth_probe()` helper handles the common cases.

### Adding a style preset

```php
add_filter( 'aiisp_style_presets', function ( $presets ) {
	$presets['blueprint'] = __( 'Blueprint', 'my-textdomain' );
	return $presets;
} );
```

### Accessing services

```php
aiisp()->options()->get( 'image_size' );   // Settings
aiisp()->providers()->all();               // AI engines
aiisp()->stock()->all();                   // Stock sources
aiisp()->usage()->limit_reached();         // Daily limit
```

## Project structure

```
cubixsol-multi-ai-image-generator/
├── cubixsol-multi-ai-image-generator.php   # Bootstrap: constants, autoloader, aiisp()
├── uninstall.php                           # Full data cleanup on delete
├── readme.txt                              # WordPress.org readme
├── assets/
│   ├── css/aiisp-admin.css
│   ├── js/aiisp-admin.js                   # All admin UI behaviour (jQuery)
│   └── images/
├── includes/
│   ├── class-plugin.php                    # Singleton, service container, hook wiring
│   ├── class-autoloader.php                # AIISP\Foo_Bar → class-foo-bar.php
│   ├── class-options.php                   # Schema-driven settings
│   ├── class-logger.php                    # Generation history table
│   ├── class-activator.php / class-deactivator.php
│   ├── admin/                              # Menus, settings save, meta box, views/
│   ├── core/                               # AJAX endpoints, media sideloading, usage counter
│   └── providers/                          # AI engines + registry
│       └── stock/                          # Stock sources + registry
└── languages/
```

## Data & privacy

- The plugin contacts third-party services **only when a user acts**: generating, testing a key or searching stock. It sends no background telemetry and sends no data to Cubixsol.
- AI engines receive the prompt (including Prompt Booster text and the negative prompt), the image dimensions and your API key. Stock sources receive the search term and your API key.
- API keys are stored in the WordPress options table and are never displayed in form fields again after they are saved.
- Each provider's terms and privacy policy are listed in [`readme.txt`](readme.txt) under *External services*.

## Uninstalling

- **Deactivating** clears the daily usage counter and keeps your settings.
- **Deleting** the plugin from the Plugins screen removes the `aiisp_settings` and `aiisp_version` options, the usage transient and the `{prefix}aiisp_generation_log` table. Images already in your Media Library are **not** deleted.

## Changelog

### 1.0.7
- Added an advisory to the Pollinations.ai engine card and clearer messages when Pollinations fails (busy service, timeout or a non-image response).

### 1.0.6
- Added Settings and Image Workspace links to the plugin's row on the Plugins screen.
- Gemini key checks now report invalid keys correctly (HTTP 400 `API_KEY_INVALID`).
- FLUX now uses the `api.together.ai` host, so project-scoped keys work.

### 1.0.5
- The Classic Editor's featured-image box now updates without a page reload.

### 1.0.4
- Ideogram key verification now runs a minimal low-cost generation so it gives a definite result.

### 1.0.3
- OpenAI now uses `gpt-image-2` because DALL·E was removed from the API.

### 1.0.2
- Fixed `response_format` handling for OpenAI.

### 1.0.1
- Simplified the Image Workspace and fixed lightbox alignment.

### 1.0.0
- Initial release.

See [`readme.txt`](readme.txt) for the full changelog.

## License

GPLv2 or later. See [gnu.org/licenses/gpl-2.0.html](https://www.gnu.org/licenses/gpl-2.0.html).

© Cubixsol
