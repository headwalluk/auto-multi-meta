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

// SEO plugin meta keys — terms.
define( 'AMM_YOAST_TERM_META_KEY', 'wpseo_desc' );
define( 'AMM_RANKMATH_TERM_META_KEY', 'rank_math_description' );

// SEO plugin meta keys — posts.
define( 'AMM_YOAST_POST_META_KEY', '_yoast_wpseo_metadesc' );
define( 'AMM_RANKMATH_POST_META_KEY', 'rank_math_description' );
// phpcs:enable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound
