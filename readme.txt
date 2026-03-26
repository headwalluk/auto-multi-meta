=== Auto Multi-Meta ===
Contributors: headwall
Tags: seo, meta description, ai, openai, anthropic
Requires at least: 6.4
Tested up to: 6.9
Requires PHP: 8.0
Stable tag: 0.4.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

AI-generated SEO meta descriptions for taxonomy terms and posts via OpenAI, Anthropic, or OpenRouter.

== Description ==

Auto Multi-Meta automatically generates SEO meta descriptions for taxonomy term archives and post types using AI. It detects Yoast SEO or RankMath and writes to the correct meta fields.

**Features:**

* AI-generated meta descriptions for taxonomy terms (categories, tags, product categories, custom taxonomies)
* AI-generated meta descriptions for posts, pages, and custom post types
* Support for OpenAI, Anthropic, and OpenRouter providers
* Auto-detects Yoast SEO or RankMath and writes to the correct meta keys
* Admin UI with traffic-light status indicators
* Dry-run preview before saving
* Bulk generation with background processing via Action Scheduler or WP-Cron
* Overwrite protection with optional force-regenerate
* Activity log showing recent generation history
* Configurable prompt templates with token replacement

**Requirements:**

* An active SEO plugin: Yoast SEO or RankMath
* An API key for at least one supported AI provider

== Installation ==

1. Upload the `auto-multi-meta` folder to `/wp-content/plugins/`
2. Activate the plugin in Plugins > Installed Plugins
3. Go to Tools > Auto Multi-Meta
4. On the Settings tab, choose your AI provider and enter your API key
5. Select which taxonomies and post types to enable on the respective tabs
6. Use the Term Manager or Post Manager to browse items and generate descriptions

== Frequently Asked Questions ==

= Which SEO plugins are supported? =

Yoast SEO and RankMath. The plugin auto-detects which is active and writes to the correct meta keys.

= Which AI providers are supported? =

OpenAI, Anthropic, and OpenRouter. OpenRouter provides access to many models at low cost.

= Can I preview before saving? =

Yes. Each item has a Preview button that generates a description without saving it.

= How does bulk generation work? =

The Batch tab queues all items missing descriptions and processes them in the background via Action Scheduler (if available) or WP-Cron, with a configurable delay between API calls to respect rate limits.

== Screenshots ==

1. Settings page with API configuration
2. Term manager with status indicators
3. Post manager with bulk generation
4. Activity log tab

== Changelog ==

= 0.4.1 =
* Fixed uninstall handler missing the site language option
* Added Clear Log button on the Activity Log tab
* Added character count colouring in manager tables (green/amber)
* Added confirmation dialogs before batch and bulk generation
* Updated all documentation to reflect recent features

= 0.4.0 =
* Added WP-CLI commands: wp amm status, list, generate (single and bulk)
* Added site language setting to append locale-aware spelling instructions to prompts
* Added AI response cleaning to strip markdown artifacts from model output
* Improved default prompt templates with explicit plain-text-only instructions
* Added GitHub Actions release workflow

= 0.3.0 =
* Added The SEO Framework (TSF) support for post and term meta descriptions
* Test Connection now shows provider, model, and AI response in the result
* Settings form preserves the active tab after saving (Taxonomies, Post Types, etc.)
* Added View button in Term Manager and Post Manager to open items on the front-end
* Updated admin notices and settings page to reference TSF alongside Yoast SEO and RankMath

= 0.2.0 =
* Refactored plugin bootstrap and hook registration architecture
* Converted all admin templates to code-first (printf/echo) pattern
* Prefixed all template variables for wordpress.org review compliance
* Added readme.txt, docs/ directory, and global plugin accessor function
* Removed singleton pattern and constructor injection from subsystem classes

= 0.1.0 =
* Initial release pending testing
* AI-generated meta descriptions for taxonomy terms and posts
* Support for OpenAI, Anthropic, and OpenRouter
* Yoast SEO and RankMath integration
* Background batch processing via Action Scheduler / WP-Cron
* Admin UI with term/post managers, preview, and activity log
