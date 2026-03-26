# Usage

## Installation

1. Upload the `auto-multi-meta` folder to `/wp-content/plugins/`
2. Activate the plugin in **Plugins > Installed Plugins**
3. Go to **Tools > Auto Multi-Meta**
4. On the **Settings** tab, choose your AI provider and enter your API key
5. Click **Test Connection** to verify
6. Enable taxonomies and post types on their respective tabs
7. Click **Save Settings**

## Generating Descriptions

### Individual items

1. Go to **Tools > Auto Multi-Meta > Taxonomies** (or **Post Types**) tab
2. Click **Open Term Manager** (or **Open Post Manager**)
3. Filter by taxonomy or post type as needed
4. Click **Generate** on any row to create a description via AJAX
5. Click **Preview** to see a dry-run result without saving
6. Click **Force Regenerate** to replace an existing description

### Bulk generation (foreground)

1. In the Term Manager or Post Manager, select multiple items via checkboxes
2. Use the bulk action to generate descriptions for all selected items
3. Items are processed sequentially with progress shown in the UI

### Background batch processing

1. Go to the **Batch** tab on the settings page
2. Choose the type: Terms & Posts, Terms only, or Posts only
3. Click **Generate All Missing** — jobs are queued in the background
4. Processing uses Action Scheduler (if available via WooCommerce) or WP-Cron as fallback
5. A configurable delay between API calls prevents rate limiting
6. Progress is shown live; you can close the page and return later
7. A completion notice appears on the next admin page load

## Activity Log

The **Log** tab shows the most recent generation attempts (up to 100, newest first) including:

- Timestamp
- Item type (term or post) and ID
- AI provider and model used
- Status (generated, skipped, or error)
- Result message
