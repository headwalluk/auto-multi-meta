# WP-CLI Commands

Auto Multi-Meta provides a full set of WP-CLI commands under the `wp amm` namespace.

## Status

Show the current plugin configuration at a glance.

```bash
wp amm status
```

Displays: provider, model, API key status, detected SEO plugin, enabled taxonomies, enabled post types, site language, batch delay.

## Listing Items

List terms or posts with their meta description status.

```bash
# List all terms across enabled taxonomies.
wp amm list terms

# List terms missing meta descriptions in a specific taxonomy.
wp amm list terms --taxonomy=product_cat --status=missing

# List all posts with descriptions.
wp amm list posts --status=has

# List posts in a specific post type, output as JSON.
wp amm list posts --post-type=page --format=json
```

### Options

| Flag | Description |
|------|-------------|
| `--taxonomy=<slug>` | Filter terms to a single taxonomy |
| `--post-type=<slug>` | Filter posts to a single post type |
| `--status=<status>` | Filter by `all`, `missing`, or `has` (default: `all`) |
| `--format=<format>` | Output as `table`, `csv`, or `json` (default: `table`) |

### Output columns

**Terms:** ID, Name, Taxonomy, Status, Chars, Description (truncated to 80 chars)

**Posts:** ID, Title, Type, Status, Chars, Description (truncated to 80 chars)

## Generating Descriptions

### Single item

Generate a meta description for one term or post.

```bash
# Generate for a term (taxonomy auto-detected if possible).
wp amm generate term 15

# Generate for a term with explicit taxonomy.
wp amm generate term 15 --taxonomy=category

# Generate for a post.
wp amm generate post 42

# Preview without saving.
wp amm generate post 42 --dry-run

# Overwrite an existing description.
wp amm generate post 42 --force
```

### Bulk generation

Generate meta descriptions for all items missing them.

```bash
# Generate for all terms in all enabled taxonomies.
wp amm generate terms

# Generate for terms in a specific taxonomy only.
wp amm generate terms --taxonomy=product_cat

# Generate for all posts in all enabled post types.
wp amm generate posts

# Generate for pages only.
wp amm generate posts --post-type=page

# Force regenerate everything (including items that already have descriptions).
wp amm generate terms --force

# Preview all without saving.
wp amm generate posts --dry-run

# Custom delay between API calls (seconds).
wp amm generate terms --delay=10
```

Bulk commands show a progress bar and print a summary on completion.

### Options

| Flag | Description |
|------|-------------|
| `--taxonomy=<slug>` | Limit to a single taxonomy (terms only) |
| `--post-type=<slug>` | Limit to a single post type (posts only) |
| `--force` | Overwrite existing descriptions |
| `--dry-run` | Preview without saving |
| `--delay=<seconds>` | Seconds between API calls (default: saved batch delay setting) |

## Examples

```bash
# Check what's configured.
wp amm status

# See how many product categories are missing descriptions.
wp amm list terms --taxonomy=product_cat --status=missing

# Generate descriptions for all of them.
wp amm generate terms --taxonomy=product_cat

# Preview what a single page description would look like.
wp amm generate post 123 --dry-run

# Export all posts with descriptions to CSV.
wp amm list posts --status=has --format=csv > posts-with-meta.csv
```
