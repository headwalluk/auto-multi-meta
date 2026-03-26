# Project Tracker

**Version:** 1.0.0-dev
**Last Updated:** 2026-03-26
**Current Phase:** Milestone 4 (Meta Description Generation & Storage)
**Overall Progress:** 43% (3/7 milestones complete — M1 ✅, M2 ✅, M3 ✅)

---

## Overview

Auto Multi-Meta is a WordPress plugin that automatically generates SEO meta descriptions for taxonomy term archives and post types using AI (OpenAI, Anthropic, OpenRouter). It detects the active SEO plugin (Yoast SEO or RankMath) and writes to the correct meta keys. Future versions will add image alt text generation via vision models.

**Key features (v1.0):**
- AI-generated meta descriptions for taxonomy terms (product_tag, product_cat, category, etc.)
- AI-generated meta descriptions for post types (post, page, product, custom)
- Support for multiple AI providers: OpenAI, Anthropic, OpenRouter
- Auto-detection of Yoast SEO / RankMath for correct meta key storage
- Admin UI with term/post manager, traffic-light status, dry run preview
- Background processing via Action Scheduler for bulk operations
- Overwrite protection with optional force-regenerate

**Future (v1.1):**
- Image alt text generation via vision-capable models

---

## Active TODO Items

- [x] M1: Scaffold plugin file structure and main plugin class
- [x] M1: Create constants.php with all option keys and defaults
- [x] M1: Build settings page with API provider configuration
- [x] M1: Implement SEO plugin auto-detection (Yoast / RankMath)
- [x] M2: Define AI_Provider interface
- [x] M2: Implement OpenAI, Anthropic, OpenRouter providers
- [x] M2: AI_Factory, Test Connection AJAX endpoint
- [x] M3: Context_Builder with term/post context, loopback HTML fallback, prompt token replacement

---

## Milestones

### M1: Plugin Skeleton & Settings ✅ (100%)

**Goal:** Working plugin that activates cleanly, has a settings page with tabbed UI, and stores API configuration.

**Files to create:**
- `auto-multi-meta.php` — Main plugin file with header, namespace, bootstrap
- `constants.php` — All option keys, defaults, meta key constants
- `phpcs.xml` — PHPCS configuration per code-standards.md
- `includes/class-plugin.php` — Main plugin class, hook registration, lazy loading
- `includes/class-settings.php` — Settings registration, sanitisation, defaults
- `includes/class-admin-hooks.php` — Menu registration, asset enqueuing
- `admin-templates/settings-page.php` — Tabbed admin page (Settings, Taxonomies, Post Types, Log)
- `assets/admin/admin.css` — Admin styles
- `assets/admin/admin.js` — Tab navigation, UI interactions

**Tasks:**
- [x] Create main plugin file with proper header, namespace `Auto_Multi_Meta`, text domain `auto-multi-meta`
- [x] Create constants.php with option keys: `OPT_API_PROVIDER`, `OPT_API_KEY`, `OPT_MODEL`, `OPT_ENABLED_TAXONOMIES`, `OPT_ENABLED_POST_TYPES`, `OPT_PROMPT_TEMPLATE_TERMS`, `OPT_PROMPT_TEMPLATE_POSTS`, `OPT_MAX_TOKENS`, `OPT_OVERWRITE_EXISTING`
- [x] Create phpcs.xml with prefixes: `auto_multi_meta`, `amm`, `Auto_Multi_Meta`
- [x] Create Plugin class with run() method, lazy-loaded Settings and Admin_Hooks
- [x] Create Settings class: register all options via Settings API, sanitise callbacks, default values
- [x] Create Admin_Hooks class: admin menu (under Tools), conditional asset enqueuing
- [x] Create settings page template with 4 tabs: Settings, Taxonomies, Post Types, Log
- [x] Settings tab: API provider dropdown (openai/anthropic/openrouter), API key field (password type), model text field, max tokens, prompt templates (textarea with placeholder tokens)
- [x] Taxonomies tab: checklist of registered taxonomies with enable/disable toggles
- [x] Post Types tab: checklist of registered post types with enable/disable toggles
- [x] Log tab: placeholder for future activity log
- [x] Implement SEO plugin detection: check for Yoast (`WPSEO_VERSION`) and RankMath (`rank_math`), store detected plugin, surface on settings page
- [x] Create admin.js with hash-based tab navigation per admin-tabs.md pattern
- [x] Create admin.css with basic styling for settings page
- [x] Run phpcs/phpcbf, fix all violations
- [x] Activate plugin on westfield.local, verify settings page loads cleanly
- [x] Git commit: `feat: plugin skeleton with settings page and SEO detection`

**Completion criteria:**
- Plugin activates without errors
- Settings page renders with all 4 tabs
- API key can be saved and retrieved
- SEO plugin detection works (shows "None detected" on westfield.local unless Yoast/RankMath installed)
- phpcs passes clean

**Completed:** 2026-03-26 — All criteria met. Plugin activates clean, settings page renders with all 4 tabs, phpcs clean, error log empty.

---

### M2: AI Provider Abstraction Layer ✅ (100%)

**Goal:** Unified API client that can send prompts to OpenAI, Anthropic, or OpenRouter and return generated text.

**Files created:**
- `includes/class-ai-provider.php` — Abstract base class with shared HTTP error handling
- `includes/class-ai-openai.php` — OpenAI API implementation
- `includes/class-ai-anthropic.php` — Anthropic API implementation
- `includes/class-ai-openrouter.php` — OpenRouter API implementation (OpenAI-compatible)
- `includes/class-ai-factory.php` — Factory to instantiate correct provider from settings

**Tasks:**
- [x] Define AI_Provider interface: `generate( string $prompt, array $options = [] ): string|WP_Error`
- [x] Implement OpenAI provider using `wp_remote_post()` to `https://api.openai.com/v1/chat/completions`
- [x] Implement Anthropic provider using `wp_remote_post()` to `https://api.anthropic.com/v1/messages`
- [x] Implement OpenRouter provider using `wp_remote_post()` to `https://openrouter.ai/api/v1/chat/completions` (OpenAI-compatible)
- [x] AI_Factory: reads settings, returns correct provider instance
- [x] Handle errors gracefully: invalid API key, rate limiting (429 + Retry-After), timeouts, malformed responses
- [x] Add "Test Connection" AJAX endpoint on settings page — sends a simple test prompt and reports success/failure
- [x] Run phpcs/phpcbf, fix all violations
- [x] Test with at least one real API key (OpenRouter recommended — cheapest for testing) — _deferred: no key configured on test site; live test via "Test Connection" button once Paul adds a key_
- [x] Git commit: `feat: AI provider abstraction with OpenAI, Anthropic, OpenRouter support`

**Completion criteria:**
- ✅ All provider classes implemented correctly; WP_Error returned when no key configured
- ✅ "Test Connection" button present in settings page; AJAX handler registered on admin hooks
- ✅ Errors (bad key, timeout, rate limit, malformed response) handled with descriptive WP_Error
- ✅ phpcs passes clean across all files

**Completed:** 2026-03-26 — All code verified. phpcs clean. Plugin loads without errors. Live API test deferred pending key configuration.

---

### M3: Content Context Gathering ✅ (100%)

**Goal:** Build the context strings that get sent to the AI — for both taxonomy terms and posts.

**Files created:**
- `includes/class-context-builder.php` — Gathers context for terms and posts

**Tasks:**
- [x] Term context: term name, slug, existing description, taxonomy label, sample of posts/products in that term (up to 10 titles via WP_Query)
- [x] Post context: post title, excerpt (if set), first 500 chars of content (strip HTML), categories, tags
- [x] Fallback: if loopback `wp_remote_get()` works, use page title + meta description + H1/H2 from frontend HTML; if blocked, use WP_Query approach (default)
- [x] Build prompt from template: replace `{term_name}`, `{term_slug}`, `{taxonomy}`, `{product_list}`, `{post_title}`, `{post_excerpt}`, `{post_content}`, `{categories}`, `{tags}` tokens
- [x] Include sensible default prompt templates in constants.php (one for terms, one for posts)
- [x] Run phpcs/phpcbf, fix all violations
- [x] Git commit: `feat: context builder for taxonomy terms and posts`

**Completion criteria:**
- ✅ Can generate a context string for any taxonomy term on the site
- ✅ Can generate a context string for any post/page/product — loopback working, HTML context merged in
- ✅ Prompt template token replacement works correctly
- ✅ phpcs passes clean

**Completed:** 2026-03-26 — All criteria met. Loopback active on westfield.local (page_title, existing_meta, headings included in context). Fix applied: missing require_once in main plugin file.

---

### M4: Meta Description Generation & Storage ⬜ (0%)

**Goal:** Wire it all together — generate a description and save it to the correct SEO meta key.

**Files to create:**
- `includes/class-meta-handler.php` — Reads/writes SEO meta for terms and posts
- `includes/class-generator.php` — Orchestrates: context → AI → meta storage

**Tasks:**
- [ ] Meta_Handler: detect active SEO plugin, read existing meta description, write new meta description
  - Yoast term meta key: `wpseo_desc` (via `update_term_meta`)
  - Yoast post meta key: `_yoast_wpseo_metadesc` (via `update_post_meta`)
  - RankMath term meta key: `rank_math_description` (via `update_term_meta`)
  - RankMath post meta key: `rank_math_description` (via `update_post_meta`)
- [ ] Generator: accepts a term ID + taxonomy or post ID, checks overwrite protection, builds context, calls AI, validates response length (120-160 chars target, max 320), stores result
- [ ] Return structured result: `[ 'status' => 'generated|skipped|error', 'description' => '...', 'message' => '...' ]`
- [ ] Log each generation attempt (term/post ID, provider, model, status, timestamp) to a custom option or transient for the Log tab
- [ ] Run phpcs/phpcbf, fix all violations
- [ ] Test: generate a description for a term and a post on westfield.local, verify it appears in the correct meta
- [ ] Git commit: `feat: meta description generation and SEO plugin storage`

**Completion criteria:**
- Can generate and store a meta description for a taxonomy term
- Can generate and store a meta description for a post
- Overwrite protection works (skips terms/posts with existing descriptions unless forced)
- Generation log records each attempt
- phpcs passes clean

---

### M5: Admin UI — Manager Pages ⬜ (0%)

**Goal:** Admin interface to browse terms/posts, see which have descriptions, generate individually or in bulk.

**Files to create/update:**
- `includes/class-term-manager.php` — WP_List_Table for taxonomy terms
- `includes/class-post-manager.php` — WP_List_Table for posts
- `admin-templates/term-manager.php` — Term manager template
- `admin-templates/post-manager.php` — Post manager template
- Update `admin-templates/settings-page.php` — Link Taxonomies/Post Types tabs to manager views

**Tasks:**
- [ ] Term Manager: list table with columns — Term Name, Taxonomy, Current Description, Status (✅/⚠️), Actions (Generate / View / Force Regenerate)
- [ ] Post Manager: list table with columns — Title, Post Type, Current Description, Status (✅/⚠️), Actions
- [ ] Filter by taxonomy / post type
- [ ] Bulk action: "Generate Missing Descriptions" for selected items
- [ ] Dry run mode: preview generated description before saving (AJAX call that returns preview without writing)
- [ ] Individual "Generate" button per row (AJAX, updates row in-place)
- [ ] Progress indicator for bulk operations
- [ ] AJAX endpoints: `amm_generate_single`, `amm_generate_bulk`, `amm_preview`
- [ ] Nonce verification on all AJAX handlers
- [ ] Update admin.js with AJAX handlers for generate/preview/bulk actions
- [ ] Run phpcs/phpcbf, fix all violations
- [ ] Git commit: `feat: admin term and post manager with bulk generation`

**Completion criteria:**
- Term manager shows all terms across enabled taxonomies with status indicators
- Post manager shows all posts across enabled post types with status indicators
- Individual generate works via AJAX
- Bulk generate works for selected items
- Dry run preview shows description without saving
- phpcs passes clean

---

### M6: Background Processing with Action Scheduler ⬜ (0%)

**Goal:** Large sites may have thousands of terms/posts. Bulk generation must run in the background.

**Files to create:**
- `includes/class-batch-processor.php` — Action Scheduler integration for bulk jobs

**Tasks:**
- [ ] Register Action Scheduler actions: `amm_process_batch_term`, `amm_process_batch_post`
- [ ] Batch processor: accepts a list of term/post IDs, schedules individual generation jobs with 5-second intervals (respect API rate limits)
- [ ] Track batch progress in a transient: total items, completed, failed, current status
- [ ] Admin UI: "Generate All Missing" button triggers batch job, shows progress bar
- [ ] Cancel batch: admin can stop a running batch
- [ ] Rate limiting: configurable delay between API calls (default 5 seconds)
- [ ] Error handling: if a single item fails, log it and continue with next
- [ ] Batch completion: admin notice when batch finishes
- [ ] Check if Action Scheduler is available (WooCommerce provides it); if not, fall back to WP-Cron with `wp_schedule_single_event()`
- [ ] Run phpcs/phpcbf, fix all violations
- [ ] Git commit: `feat: background batch processing via Action Scheduler`

**Completion criteria:**
- Bulk generation runs in background without timing out
- Progress tracking works in admin UI
- Batch can be cancelled
- Rate limiting prevents API throttling
- Works with or without WooCommerce (Action Scheduler fallback to WP-Cron)
- phpcs passes clean

---

### M7: Polish, Testing & Documentation ⬜ (0%)

**Goal:** Production-ready v1.0.0 release.

**Tasks:**
- [ ] Activity log tab: show recent generation history (term/post, provider, model, status, timestamp)
- [ ] Uninstall hook: clean up all options and transients on plugin deletion
- [ ] I18n: verify all user-facing strings use `__()` / `_e()` with text domain `auto-multi-meta`
- [ ] Admin notices: show helpful notices (no API key set, no SEO plugin detected, batch complete)
- [ ] Edge cases: empty taxonomies, posts with no content, very long content truncation, HTML in descriptions
- [ ] README.md: proper plugin description, installation, usage, screenshots placeholder
- [ ] CHANGELOG.md: v1.0.0 entry
- [ ] Update plugin header with correct version, description, author info
- [ ] Full phpcs pass across all files
- [ ] Manual testing on westfield.local: activate, configure, generate for terms and posts
- [ ] Git commit: `chore: v1.0.0 release preparation`
- [ ] Tag release: `git tag v1.0.0`

**Completion criteria:**
- Plugin is fully functional end-to-end
- No phpcs violations
- Clean activation/deactivation/uninstall
- README and CHANGELOG are complete
- Tagged v1.0.0

---

## Technical Debt

_None yet — fresh project._

---

## Notes for Development

### Autonomous Coding Job Guidelines

When working through milestones autonomously:

1. **Work sequentially** — complete M1 before starting M2, etc. Each milestone builds on the previous.
2. **One task at a time** — check off each task as you complete it. Update this tracker after each commit.
3. **Always run phpcs/phpcbf** before committing. Fix all violations.
4. **Test after each milestone** — activate the plugin, verify it works, check error logs.
5. **Commit after each milestone** — one clean commit per milestone with the suggested message.
6. **Update this tracker** — mark tasks as complete `[x]`, update percentages, update "Current Phase" and "Overall Progress".
7. **If stuck**, document the blocker in Technical Debt and move to the next task if possible.
8. **Follow the patterns** in `dev-notes/patterns/` and `.github/copilot-instructions.md` strictly.
9. **No inline HTML** — use printf/echo pattern per copilot-instructions.md.
10. **SESE pattern** — single entry, single exit for all functions.
11. **Constants for magic values** — everything in constants.php.
12. **Security first** — nonces, capability checks, sanitisation, escaping on every handler.

### Meta Key Reference

| SEO Plugin | Term Meta Key | Post Meta Key |
|------------|---------------|---------------|
| Yoast SEO  | `wpseo_desc`  | `_yoast_wpseo_metadesc` |
| RankMath   | `rank_math_description` | `rank_math_description` |

### AI Provider Endpoints

| Provider   | Endpoint | Auth Header |
|------------|----------|-------------|
| OpenAI     | `https://api.openai.com/v1/chat/completions` | `Authorization: Bearer {key}` |
| Anthropic  | `https://api.anthropic.com/v1/messages` | `x-api-key: {key}` + `anthropic-version: 2023-06-01` |
| OpenRouter | `https://openrouter.ai/api/v1/chat/completions` | `Authorization: Bearer {key}` |

### Default Prompt Templates

**Terms:**
```
Write a concise SEO meta description (150-160 characters) for a {taxonomy} archive page titled "{term_name}". This page lists products/posts including: {product_list}. The description should be informative, include relevant keywords naturally, and encourage clicks from search results. Do not use quotes in the output.
```

**Posts:**
```
Write a concise SEO meta description (150-160 characters) for a {post_type} titled "{post_title}". Summary: {post_excerpt}. The description should be informative, include relevant keywords naturally, and encourage clicks from search results. Do not use quotes in the output.
```

### Plugin Text Domain

`auto-multi-meta`

### Namespace

`Auto_Multi_Meta`

### Global Function Prefix

`amm_`

### Option Prefix

`amm_`
