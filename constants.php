<?php
/**
 * Plugin constants.
 *
 * @package Auto_Multi_Meta
 */

namespace Auto_Multi_Meta;

defined( 'ABSPATH' ) || die();

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound
// Reason: All constants use the AMM_ prefix. WPCS flags them as unprefixed due to
// short-prefix detection behaviour (AMM = 3 chars), but the prefix is correctly applied.

// Plugin metadata.
define( 'AMM_VERSION', '1.0.0-dev' );
define( 'AMM_DIR', plugin_dir_path( __FILE__ ) );
define( 'AMM_URL', plugin_dir_url( __FILE__ ) );

// Option keys.
define( 'AMM_OPT_API_PROVIDER', 'amm_api_provider' );
define( 'AMM_OPT_API_KEY', 'amm_api_key' );
define( 'AMM_OPT_MODEL', 'amm_model' );
define( 'AMM_OPT_ENABLED_TAXONOMIES', 'amm_enabled_taxonomies' );
define( 'AMM_OPT_ENABLED_POST_TYPES', 'amm_enabled_post_types' );
define( 'AMM_OPT_PROMPT_TEMPLATE_TERMS', 'amm_prompt_template_terms' );
define( 'AMM_OPT_PROMPT_TEMPLATE_POSTS', 'amm_prompt_template_posts' );
define( 'AMM_OPT_MAX_TOKENS', 'amm_max_tokens' );
define( 'AMM_OPT_OVERWRITE_EXISTING', 'amm_overwrite_existing' );

// Default values.
define( 'AMM_DEFAULT_API_PROVIDER', 'openai' );
define( 'AMM_DEFAULT_MODEL', 'gpt-4o-mini' );
define( 'AMM_DEFAULT_MAX_TOKENS', 300 );
define( 'AMM_DEFAULT_OVERWRITE_EXISTING', false );
define(
	'AMM_DEFAULT_PROMPT_TEMPLATE_TERMS',
	'Write a concise SEO meta description (150-160 characters) for a {taxonomy} archive page titled "{term_name}". This page lists products/posts including: {product_list}. The description should be informative, include relevant keywords naturally, and encourage clicks from search results. Do not use quotes in the output.'
);
define(
	'AMM_DEFAULT_PROMPT_TEMPLATE_POSTS',
	'Write a concise SEO meta description (150-160 characters) for a {post_type} titled "{post_title}". Summary: {post_excerpt}. The description should be informative, include relevant keywords naturally, and encourage clicks from search results. Do not use quotes in the output.'
);

// HTTP timeout (seconds) for outbound AI API requests.
define( 'AMM_HTTP_TIMEOUT', 30 );

// Context builder — maximum chars of post content to include in context.
define( 'AMM_CONTEXT_MAX_CONTENT_CHARS', 500 );

// Context builder — maximum number of sample post/product titles to include for a term.
define( 'AMM_CONTEXT_MAX_SAMPLE_TITLES', 10 );

// Context builder — HTTP timeout (seconds) for loopback frontend fetch.
define( 'AMM_CONTEXT_LOOPBACK_TIMEOUT', 5 );

// Generation log — option key and max entries to retain.
define( 'AMM_OPT_GENERATION_LOG', 'amm_generation_log' );
define( 'AMM_GENERATION_LOG_MAX_ENTRIES', 100 );

// Meta description length limits.
// Target: 120–160 characters (used in prompt templates).
// Hard minimum: 20 characters (catches empty / broken responses).
// Hard maximum: 320 characters (rejects runaway responses).
define( 'AMM_META_DESC_MIN_LENGTH', 20 );
define( 'AMM_META_DESC_TARGET_MIN', 120 );
define( 'AMM_META_DESC_TARGET_MAX', 160 );
define( 'AMM_META_DESC_ABSOLUTE_MAX', 320 );

// SEO plugin meta keys — terms.
define( 'AMM_YOAST_TERM_META_KEY', 'wpseo_desc' );
define( 'AMM_RANKMATH_TERM_META_KEY', 'rank_math_description' );

// SEO plugin meta keys — posts.
define( 'AMM_YOAST_POST_META_KEY', '_yoast_wpseo_metadesc' );
define( 'AMM_RANKMATH_POST_META_KEY', 'rank_math_description' );

// Batch processing — option key for configurable inter-item delay (seconds).
define( 'AMM_OPT_BATCH_DELAY', 'amm_batch_delay' );
define( 'AMM_DEFAULT_BATCH_DELAY', 5 );

// Batch processing — transient key and TTL (24 hours).
define( 'AMM_BATCH_TRANSIENT_KEY', 'amm_active_batch' );
define( 'AMM_BATCH_TRANSIENT_TTL', 86400 );

// Batch processing — Action Scheduler / WP-Cron hook names.
define( 'AMM_BATCH_ACTION_TERM', 'amm_process_batch_term' );
define( 'AMM_BATCH_ACTION_POST', 'amm_process_batch_post' );

// Batch processing — Action Scheduler group name.
define( 'AMM_BATCH_AS_GROUP', 'auto-multi-meta' );

// Batch processing — option key for the completion admin notice.
define( 'AMM_OPT_BATCH_NOTICE', 'amm_batch_notice' );
// phpcs:enable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound
