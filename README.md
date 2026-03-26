# Auto Multi-Meta

![WordPress](https://img.shields.io/badge/WordPress-6.4%2B-blue)
![PHP](https://img.shields.io/badge/PHP-8.0%2B-purple)
![License](https://img.shields.io/badge/License-GPL--2.0--or--later-green)
![Version](https://img.shields.io/badge/Version-0.2.0-orange)

AI-generated SEO meta descriptions for taxonomy terms and posts. Supports OpenAI, Anthropic, and OpenRouter. Integrates with Yoast SEO and RankMath.

## Features

- Meta descriptions for **taxonomy terms** and **posts/pages/custom post types**
- **OpenAI**, **Anthropic**, and **OpenRouter** providers
- Auto-detects **Yoast SEO** or **RankMath** for correct meta key storage
- Dry-run **preview** before saving
- **Background batch processing** via Action Scheduler or WP-Cron
- Configurable **prompt templates** with token replacement
- Overwrite protection and activity logging

## Quick Start

1. Install and activate the plugin
2. Go to **Tools > Auto Multi-Meta**
3. Enter your AI provider API key and click **Test Connection**
4. Enable your taxonomies and post types
5. Open the Term or Post Manager and start generating

## Documentation

- [Configuration](docs/configuration.md) — providers, prompt templates, SEO plugin integration
- [Usage](docs/usage.md) — generating descriptions, bulk processing, activity log
- [Changelog](CHANGELOG.md)

## Requirements

- WordPress 6.4+
- PHP 8.0+
- Yoast SEO or RankMath
- API key for OpenAI, Anthropic, or OpenRouter

## License

GPL-2.0-or-later
