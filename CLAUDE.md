# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Auto Multi-Meta is a WordPress plugin that generates SEO meta descriptions for taxonomy terms and posts using AI providers (OpenAI, Anthropic, OpenRouter). It writes descriptions to Yoast SEO, RankMath, or The SEO Framework meta fields.

**Requirements:** WordPress 6.4+, PHP 8.0+, an active SEO plugin (Yoast SEO, RankMath, or The SEO Framework).

## Commands

```bash
phpcs                  # Check all files for coding standard violations
phpcs includes/        # Check specific directory
phpcbf                 # Auto-fix coding standard issues
wp plugin check auto-multi-meta  # Run WordPress plugin checker
wp amm status          # Show plugin config via WP-CLI
wp amm list terms      # List terms with meta description status
wp amm list posts      # List posts with meta description status
```

There are no automated tests, build steps, or package managers. Quality is enforced via phpcs with WordPress coding standards (`phpcs.xml`). Releases are built via GitHub Actions (`.github/workflows/release.yml`) on tag push.

## Architecture

### Bootstrap

The main plugin file (`auto-multi-meta.php`) is in **global scope** (no namespace). It loads `constants.php` and `functions-private.php`, requires all class files, then calls `auto_multi_meta_get_plugin()->run()`.

`auto_multi_meta_get_plugin()` (in `functions-private.php`) is the global accessor — it returns the Plugin instance via a global variable. All classes that need the plugin instance call this function rather than receiving it via constructor injection.

### Hook registration

**All** `add_action()` and `add_filter()` calls are centralised in `Plugin::run()`. Subsystem classes (`Admin_Hooks`, `Batch_Processor`, etc.) do not register their own hooks — `Plugin::run()` wires their methods to WordPress hooks directly. This gives a single place to see every hook the plugin uses.

### Namespace and classes

**Namespace:** `Auto_Multi_Meta` — all classes in `includes/` use this namespace.

**Class files:** `includes/class-{name}.php` (e.g. `class-admin-hooks.php` for `Admin_Hooks`).

Subsystem instances are lazy-loaded via getter methods on `Plugin` (e.g. `get_admin_hooks()`, `get_generator()`).

### Core flow

1. `Plugin::run()` registers all hooks
2. `Plugin::admin_init()` registers settings via the Settings API
3. `Admin_Hooks` handles menu registration, asset enqueuing, page rendering, and AJAX endpoints
4. On generation: `Generator` orchestrates `Context_Builder` → `AI_Factory` → `AI_Provider::generate()` → `Meta_Handler` storage → activity log
5. `Batch_Processor` handles background bulk generation via Action Scheduler (preferred) or WP-Cron fallback
6. `CLI` (loaded only when `WP_CLI` is defined) provides `wp amm status`, `list`, and `generate` commands

### Constants

All option keys, defaults, validation limits, meta keys, and batch config are defined in `constants.php` with `AMM_` prefix. Option keys use `AMM_OPT_`, defaults use `AMM_DEFAULT_`.

### Admin templates

All three templates (`settings-page.php`, `term-manager.php`, `post-manager.php`) in `admin-templates/` are **code-first** — output via `printf()`/`echo` only, no inline HTML mixed with PHP.

All template-scoped variables are prefixed with `$auto_multi_meta_` to pass wordpress.org review without phpcs suppressions.

## Key Coding Conventions

Full standards are in `.github/copilot-instructions.md`. Key rules:

### PHP patterns

- **No `declare(strict_types=1)`** — WordPress/WooCommerce incompatible
- **Single-Entry Single-Exit (SESE)** — functions should have one return statement at the end
- **No inline HTML in templates** — use `printf()`/`echo` exclusively, never mixed HTML+PHP
- **All magic values in `constants.php`** — no hardcoded strings or numbers in logic
- **Date/time as human-readable strings** (`Y-m-d H:i:s T`), not Unix timestamps
- **Boolean options:** Use `filter_var($val, FILTER_VALIDATE_BOOLEAN)`, not string comparison
- **Error handling:** Return `WP_Error` objects, never throw exceptions

### Security

- Sanitize all input, escape all output
- Verify nonces and check capabilities on every AJAX handler and form submission
- Use `wp_kses()` when outputting HTML that contains user-controlled URLs

### Naming and prefixes

- **Global functions:** `auto_multi_meta_` prefix (e.g. `auto_multi_meta_get_plugin()`)
- **Global variables:** `$auto_multi_meta_` prefix (including template-scoped variables in included files)
- **Constants:** `AMM_` prefix
- **CSS/JS handles and HTML IDs:** `amm-` prefix
- **AJAX actions:** `amm_` prefix
- **Text domain:** `auto-multi-meta`

### Git

- **Commit messages:** `type: description` format — `feat:`, `fix:`, `chore:`, `refactor:`, `docs:`, `style:`, `test:`

## Protected Directories

If a `pwpl/` directory exists, **do not modify** any files within it — it's a sealed licence management dependency.

## Project Files

- `dev-notes/` — Internal project tracker and development notes (not shipped in distribution)
- `docs/` — User-facing documentation (not shipped in distribution)
- `.github/copilot-instructions.md` — Full coding standards (portable across WordPress plugin projects)
- `.distignore` — Files excluded from distribution builds
- `readme.txt` — WordPress.org plugin directory readme
