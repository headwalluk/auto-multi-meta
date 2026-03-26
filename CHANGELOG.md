# Changelog

All notable changes to Auto Multi-Meta are documented in this file.

The format follows [Keep a Changelog](https://keepachangelog.com/en/1.0.0/).

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
