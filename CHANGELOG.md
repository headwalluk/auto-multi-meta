# Changelog

All notable changes to Auto Multi-Meta are documented in this file.

The format follows [Keep a Changelog](https://keepachangelog.com/en/1.0.0/).

---

## [0.3.0] — 2026-03-26

### Added

- **The SEO Framework support** — detects TSF via `THE_SEO_FRAMEWORK_PRESENT`, reads/writes post meta (`_genesis_description`) and term meta (serialized `autodescription-term-settings` array with read-merge-write)
- **View button** in Term Manager and Post Manager — opens the term archive or post permalink in a new tab
- **Test Connection detail** — success message now shows provider name, model name, and the AI's response text

### Changed

- **Settings form tab persistence** — active tab hash is preserved through form submission via `_wp_http_referer`, so saving on the Taxonomies or Post Types tab returns to the same tab
- **Admin notices and settings page** updated to reference The SEO Framework alongside Yoast SEO and RankMath

---

## [0.2.0] — 2026-03-26

### Changed

- **Plugin bootstrap refactored** — main plugin file moved to global scope; singleton replaced with `auto_multi_meta_get_plugin()` global accessor
- **Hook registration centralised** — all `add_action()` / `add_filter()` calls moved into `Plugin::run()`; removed `register()` methods from `Admin_Hooks` and `Batch_Processor`
- **Constructor injection removed** — `Admin_Hooks` and `Batch_Processor` no longer accept `Plugin` as a constructor parameter; access via `auto_multi_meta_get_plugin()` instead
- **Admin templates converted to code-first** — all three templates (`settings-page.php`, `term-manager.php`, `post-manager.php`) rewritten from inline HTML to `printf()`/`echo` per coding standards
- **Template variables prefixed** — all template-scoped variables now use `$auto_multi_meta_` prefix for wordpress.org review compliance; `phpcs:disable` suppressions removed
- **`load_plugin_textdomain()` removed** — not needed for plugins hosted on wordpress.org since WP 4.6
- **`Domain Path` header removed** — no longer applicable

### Added

- `functions-private.php` with `auto_multi_meta_get_plugin()` global accessor function
- `readme.txt` in wordpress.org format with proper headers (Tested up to, Stable tag, License)
- `docs/` directory with detailed documentation (`configuration.md`, `usage.md`)
- `languages/` directory placeholder

### Removed

- Singleton pattern from `Plugin` class (private constructor, static instance, `get_instance()`)
- `Admin_Hooks::register()` and `Batch_Processor::register()` methods

---

## [0.1.0] — 2026-03-26

Initial release.

### Added

- **AI provider abstraction** — unified client supporting OpenAI, Anthropic, and OpenRouter
- **Context builder** — gathers term/post context for prompt generation; augments with loopback HTML fetch (page title, existing meta, headings) when available
- **Meta description generator** — full pipeline from context → AI → validation → storage
- **SEO plugin auto-detection** — detects Yoast SEO or RankMath and writes to the correct meta keys
- **Term Manager** — admin list table for taxonomy terms with per-row Generate / Preview / Force Regenerate actions and bulk generation
- **Post Manager** — admin list table for posts/pages/custom post types with the same action set
- **Background batch processing** — Action Scheduler integration (WP-Cron fallback) with configurable inter-call delays
- **Batch UI** — progress bar, live status updates, cancel support
- **Activity log** — records every generation attempt (type, provider, model, status, timestamp)
- **Admin notices** — warns when no API key or SEO plugin is configured; notifies on batch completion
- **Overwrite protection** — skips items with existing descriptions unless forced
- **Dry-run preview** — preview generated descriptions via AJAX without saving
- **Uninstall hook** — removes all plugin options and transients on deletion
- **I18n-ready** — all user-facing strings use `__()` / `_e()` with the `auto-multi-meta` text domain
- Edge case handling: HTML stripping from AI responses, multibyte-safe content truncation, empty taxonomy graceful fallback
