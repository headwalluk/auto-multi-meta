# Auto Multi-Meta

Automatically generates SEO meta descriptions for taxonomy term archives and post types using AI (OpenAI, Anthropic, OpenRouter). Integrates with Yoast SEO and RankMath to write to the correct meta fields.

---

## Features

- AI-generated meta descriptions for **taxonomy terms** (categories, tags, product categories, custom taxonomies)
- AI-generated meta descriptions for **posts, pages, and custom post types**
- Support for **OpenAI**, **Anthropic**, and **OpenRouter** providers
- Auto-detects **Yoast SEO** or **RankMath** and writes to the correct meta keys
- Admin UI with traffic-light status indicators (has description / missing)
- **Dry-run preview** — see the generated description before saving
- **Bulk generation** with background processing via Action Scheduler (or WP-Cron fallback)
- Overwrite protection — skip items that already have descriptions unless forced
- Activity log showing recent generation history

---

## Requirements

- WordPress 6.4+
- PHP 8.0+
- An active SEO plugin: **Yoast SEO** or **RankMath**
- An API key for at least one supported AI provider

---

## Installation

1. Upload the `auto-multi-meta` folder to `/wp-content/plugins/`
2. Activate the plugin in **Plugins → Installed Plugins**
3. Go to **Tools → Auto Multi-Meta**
4. On the **Settings** tab, choose your AI provider and enter your API key
5. Select which taxonomies and post types to enable
6. Use the **Taxonomies** or **Post Types** tabs to browse and generate descriptions

---

## Configuration

### Settings Tab

| Setting | Description |
|---------|-------------|
| API Provider | OpenAI, Anthropic, or OpenRouter |
| API Key | Your provider API key |
| Model | Model name (e.g. `gpt-4o-mini`, `claude-3-haiku-20240307`) |
| Max Tokens | Maximum tokens for the AI response (default: 300) |
| Overwrite Existing | Whether to replace descriptions that already exist |
| Batch Delay | Seconds between API calls during bulk generation (default: 5) |

### Prompt Templates

Separate templates for taxonomy terms and posts. Supported tokens:

**Term template tokens:**
- `{term_name}` — Term display name
- `{term_slug}` — Term slug
- `{taxonomy}` — Taxonomy slug
- `{taxonomy_label}` — Taxonomy human-readable label
- `{description}` — Term description
- `{product_list}` — Comma-separated sample of items in the term
- `{page_title}` — Page `<title>` tag (from loopback fetch, if available)
- `{existing_meta}` — Existing meta description from page HTML
- `{headings}` — H1/H2 headings from the archive page

**Post template tokens:**
- `{post_title}` — Post title
- `{post_type}` — Post type slug
- `{post_type_label}` — Post type human-readable label
- `{post_excerpt}` — Post excerpt
- `{post_content}` — First 500 characters of post content (HTML stripped)
- `{categories}` — Comma-separated category names
- `{tags}` — Comma-separated tag names
- `{page_title}` — Page `<title>` tag (from loopback fetch, if available)
- `{existing_meta}` — Existing meta description from page HTML
- `{headings}` — H1/H2 headings from the post

---

## Usage

### Generate for Individual Items

1. Go to **Tools → Auto Multi-Meta → Taxonomies** (or Post Types)
2. Filter by taxonomy or post type as needed
3. Click **Generate** on any row to create a description via AJAX
4. Click **Preview** to see a dry-run result without saving
5. Click **Force Regenerate** to replace an existing description

### Bulk Generation (Background)

1. Go to the **Batch** tab on any manager page
2. Select whether to process terms or posts
3. Click **Start Batch** — jobs are queued with configurable delays between API calls
4. Progress is shown live in the admin UI
5. A completion notice appears when all items are processed

---

## Meta Key Reference

| SEO Plugin | Term Meta Key | Post Meta Key |
|------------|---------------|---------------|
| Yoast SEO | `wpseo_desc` | `_yoast_wpseo_metadesc` |
| RankMath | `rank_math_description` | `rank_math_description` |

---

## Screenshots

*(Screenshots placeholder — add images to `assets/` for WordPress.org listing)*

1. Settings page with API configuration
2. Term manager with status indicators
3. Post manager with bulk generation
4. Activity log tab

---

## Changelog

See [CHANGELOG.md](CHANGELOG.md).

---

## License

GPL-2.0-or-later — see [LICENSE](https://www.gnu.org/licenses/gpl-2.0.html).
