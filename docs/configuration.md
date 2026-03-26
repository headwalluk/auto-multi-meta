# Configuration

## Settings Tab

| Setting | Description |
|---------|-------------|
| API Provider | OpenAI, Anthropic, or OpenRouter |
| API Key | Your provider API key |
| Model | Model name (e.g. `gpt-4o-mini`, `claude-3-haiku-20240307`) |
| Max Tokens | Maximum tokens for the AI response (default: 300) |
| Overwrite Existing | Whether to replace descriptions that already exist |
| Batch Delay | Seconds between API calls during bulk generation (default: 5) |

## AI Providers

| Provider | Endpoint | Auth Header |
|----------|----------|-------------|
| OpenAI | `https://api.openai.com/v1/chat/completions` | `Authorization: Bearer {key}` |
| Anthropic | `https://api.anthropic.com/v1/messages` | `x-api-key: {key}` |
| OpenRouter | `https://openrouter.ai/api/v1/chat/completions` | `Authorization: Bearer {key}` |

OpenRouter is the cheapest option for testing — it provides access to many models at low cost.

## SEO Plugin Integration

The plugin auto-detects Yoast SEO or RankMath and writes to the correct meta keys:

| SEO Plugin | Term Meta Key | Post Meta Key |
|------------|---------------|---------------|
| Yoast SEO | `wpseo_desc` | `_yoast_wpseo_metadesc` |
| RankMath | `rank_math_description` | `rank_math_description` |

If no supported SEO plugin is active, the plugin will display a warning and meta descriptions cannot be stored.

## Prompt Templates

Separate templates for taxonomy terms and posts. Use tokens as placeholders that are replaced with real data at generation time.

### Term template tokens

| Token | Description |
|-------|-------------|
| `{term_name}` | Term display name |
| `{term_slug}` | Term slug |
| `{taxonomy}` | Taxonomy slug |
| `{taxonomy_label}` | Taxonomy human-readable label |
| `{description}` | Term description |
| `{product_list}` | Comma-separated sample of items in the term |
| `{page_title}` | Page `<title>` tag (from loopback fetch, if available) |
| `{existing_meta}` | Existing meta description from page HTML |
| `{headings}` | H1/H2 headings from the archive page |

### Post template tokens

| Token | Description |
|-------|-------------|
| `{post_title}` | Post title |
| `{post_type}` | Post type slug |
| `{post_type_label}` | Post type human-readable label |
| `{post_excerpt}` | Post excerpt |
| `{post_content}` | First 500 characters of post content (HTML stripped) |
| `{categories}` | Comma-separated category names |
| `{tags}` | Comma-separated tag names |
| `{page_title}` | Page `<title>` tag (from loopback fetch, if available) |
| `{existing_meta}` | Existing meta description from page HTML |
| `{headings}` | H1/H2 headings from the post |

### Loopback fetch

The plugin attempts a `wp_remote_get()` to the frontend URL of each term or post to extract the page title, existing meta description, and headings. This has a 5-second timeout and is skipped gracefully if the site is not publicly accessible (e.g. local development behind a firewall).
