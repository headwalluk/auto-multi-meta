# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Auto Multi-Meta is a WordPress plugin that generates SEO meta descriptions for taxonomy terms and posts using AI providers (OpenAI, Anthropic, OpenRouter). It writes descriptions to Yoast SEO or RankMath meta fields.

**Requirements:** WordPress 6.4+, PHP 8.0+, an active SEO plugin (Yoast or RankMath).

## Commands

```bash
phpcs                  # Check all files for coding standard violations
phpcs includes/        # Check specific directory
phpcbf                 # Auto-fix coding standard issues
```

There are no automated tests, build steps, or package managers. Quality is enforced via phpcs with WordPress coding standards (`phpcs.xml`).

## Architecture

**Entry point:** `auto-multi-meta.php` loads `constants.php`, requires all class files, then calls `Plugin::get_instance()->run()`.

**Namespace:** `Auto_Multi_Meta` — all classes live under this namespace.

**Core flow:**
1. `Plugin` (singleton) lazy-loads subsystems and registers hooks at `plugins_loaded`
2. `Admin_Hooks` registers the Tools menu page, enqueues assets, and defines AJAX endpoints
3. On generation request: `Generator` orchestrates `Context_Builder` → `AI_Factory` → `AI_Provider::generate()` → `Meta_Handler` storage → activity log
4. `Batch_Processor` handles background bulk generation via Action Scheduler (preferred) or WP-Cron fallback

**AI Provider abstraction:** `AI_Provider` (abstract) with three implementations: `AI_OpenAI`, `AI_Anthropic`, `AI_OpenRouter`. `AI_Factory` instantiates the correct one from settings. Add new providers by extending `AI_Provider` and updating the factory.

**Meta storage:** `Meta_Handler` detects the active SEO plugin and reads/writes to the correct meta keys (Yoast: `wpseo_desc` / `_yoast_wpseo_metadesc`; RankMath: `rank_math_description`).

**Admin UI:** Single tabbed page (Settings, Taxonomies, Post Types, Log, Batch) in `admin-templates/settings-page.php`. Term/Post managers use `WP_List_Table` subclasses with AJAX actions for generate, preview, and bulk operations.

**Constants:** All option keys, defaults, validation limits, meta keys, and batch config are defined in `constants.php` with `AMM_` prefix.

## Key Coding Conventions

These are from `.github/copilot-instructions.md` and enforced throughout the codebase:

- **No `declare(strict_types=1)`** — WordPress/WooCommerce incompatible
- **Single-Entry Single-Exit (SESE):** Functions should have one return statement at the end
- **No inline HTML:** Templates use `printf()`/`echo` exclusively, never mixed HTML+PHP
- **All magic values in `constants.php`** — option keys prefixed `OPT_`, defaults prefixed `DEF_`/`AMM_DEFAULT_`
- **Date/time as human-readable strings** (`Y-m-d H:i:s T`), not Unix timestamps
- **Boolean options:** Use `filter_var($val, FILTER_VALIDATE_BOOLEAN)`, not string comparison
- **Security:** Sanitize all input, escape all output, verify nonces, check capabilities on every AJAX handler
- **Error handling:** Return `WP_Error` objects, never throw exceptions
- **Text domain:** `auto-multi-meta` — all user-facing strings must be translatable
- **Commit messages:** `type: description` format (feat/fix/chore/refactor/docs/style/test)

## AJAX Endpoints

All registered in `Admin_Hooks`, all require `manage_options` capability and nonce verification:

- `amm_test_connection` — Test AI provider connectivity
- `amm_generate_single` — Generate for one term or post
- `amm_generate_bulk` — Sequential bulk generation
- `amm_preview` — Dry-run preview without saving
- `amm_start_batch` / `amm_batch_progress` / `amm_cancel_batch` — Background batch operations

## Protected Directories

If a `pwpl/` directory exists, **do not modify** any files within it — it's a sealed licence management dependency.
