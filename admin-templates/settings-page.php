<?php
/**
 * Admin settings page template.
 *
 * Variables provided by Admin_Hooks::render_settings_page():
 *
 * @var string          $auto_multi_meta_seo_plugin     Detected SEO plugin ('yoast', 'rankmath', 'none').
 * @var \WP_Taxonomy[]  $auto_multi_meta_all_taxonomies Registered public taxonomies (objects).
 * @var \WP_Post_Type[] $auto_multi_meta_all_post_types Registered public post types (objects).
 *
 * @package Auto_Multi_Meta
 */

defined( 'ABSPATH' ) || die();

$auto_multi_meta_enabled_taxonomies = (array) get_option( AMM_OPT_ENABLED_TAXONOMIES, [] );
$auto_multi_meta_enabled_post_types = (array) get_option( AMM_OPT_ENABLED_POST_TYPES, [] );
$auto_multi_meta_api_provider       = (string) get_option( AMM_OPT_API_PROVIDER, AMM_DEFAULT_API_PROVIDER );
$auto_multi_meta_api_key            = (string) get_option( AMM_OPT_API_KEY, '' );
$auto_multi_meta_model              = (string) get_option( AMM_OPT_MODEL, AMM_DEFAULT_MODEL );
$auto_multi_meta_max_tokens         = (int) get_option( AMM_OPT_MAX_TOKENS, AMM_DEFAULT_MAX_TOKENS );
$auto_multi_meta_prompt_terms       = (string) get_option( AMM_OPT_PROMPT_TEMPLATE_TERMS, AMM_DEFAULT_PROMPT_TEMPLATE_TERMS );
$auto_multi_meta_prompt_posts       = (string) get_option( AMM_OPT_PROMPT_TEMPLATE_POSTS, AMM_DEFAULT_PROMPT_TEMPLATE_POSTS );
$auto_multi_meta_overwrite          = (bool) get_option( AMM_OPT_OVERWRITE_EXISTING, AMM_DEFAULT_OVERWRITE_EXISTING );
$auto_multi_meta_batch_delay        = (int) get_option( AMM_OPT_BATCH_DELAY, AMM_DEFAULT_BATCH_DELAY );
$auto_multi_meta_generation_log     = (array) get_option( AMM_OPT_GENERATION_LOG, [] );

$auto_multi_meta_seo_labels = [
	'yoast'    => __( 'Yoast SEO', 'auto-multi-meta' ),
	'rankmath' => __( 'RankMath', 'auto-multi-meta' ),
	'none'     => __( 'None detected', 'auto-multi-meta' ),
];

$auto_multi_meta_provider_labels = [
	'openai'     => __( 'OpenAI', 'auto-multi-meta' ),
	'anthropic'  => __( 'Anthropic', 'auto-multi-meta' ),
	'openrouter' => __( 'OpenRouter', 'auto-multi-meta' ),
];

// Exclude internal / attachment post types.
$auto_multi_meta_excluded_post_types = [ 'attachment', 'revision', 'nav_menu_item', 'custom_css', 'customize_changeset', 'oembed_cache', 'user_request', 'wp_block', 'wp_template', 'wp_template_part', 'wp_global_styles', 'wp_navigation' ];
$auto_multi_meta_display_post_types  = array_filter(
	$auto_multi_meta_all_post_types,
	function ( $auto_multi_meta_pt ) use ( $auto_multi_meta_excluded_post_types ) {
		return ! in_array( $auto_multi_meta_pt->name, $auto_multi_meta_excluded_post_types, true );
	}
);

// Exclude internal taxonomies.
$auto_multi_meta_excluded_taxonomies = [ 'nav_menu', 'link_category', 'post_format' ];
$auto_multi_meta_display_taxonomies  = array_filter(
	$auto_multi_meta_all_taxonomies,
	function ( $auto_multi_meta_tax ) use ( $auto_multi_meta_excluded_taxonomies ) {
		return ! in_array( $auto_multi_meta_tax->name, $auto_multi_meta_excluded_taxonomies, true );
	}
);

// Page wrapper and heading.
printf( '<div class="wrap amm-wrap">' );
printf( '<h1>%s</h1>', esc_html( get_admin_page_title() ) );

// Tab navigation.
printf(
	'<nav class="nav-tab-wrapper wp-clearfix" aria-label="%s">',
	esc_attr__( 'Settings tabs', 'auto-multi-meta' )
);
printf( '<a href="#settings" class="nav-tab" data-tab="settings">%s</a>', esc_html__( 'Settings', 'auto-multi-meta' ) );
printf( '<a href="#taxonomies" class="nav-tab" data-tab="taxonomies">%s</a>', esc_html__( 'Taxonomies', 'auto-multi-meta' ) );
printf( '<a href="#post-types" class="nav-tab" data-tab="post-types">%s</a>', esc_html__( 'Post Types', 'auto-multi-meta' ) );
printf( '<a href="#log" class="nav-tab" data-tab="log">%s</a>', esc_html__( 'Log', 'auto-multi-meta' ) );
printf( '<a href="#batch" class="nav-tab" data-tab="batch">%s</a>', esc_html__( 'Batch', 'auto-multi-meta' ) );
printf( '</nav>' );

// Settings form.
printf( '<form method="post" action="options.php" id="amm-settings-form">' );
settings_fields( \Auto_Multi_Meta\Settings::OPTION_GROUP );
printf( '<div class="amm-tab-content">' );

// ===== Settings Tab =====
printf( '<div id="settings-panel" class="amm-tab-panel">' );

// SEO plugin detection notice.
if ( 'none' !== $auto_multi_meta_seo_plugin ) {
	printf(
		'<div class="notice notice-success inline"><p>%s</p></div>',
		sprintf(
			/* translators: %s: SEO plugin name. */
			esc_html__( 'SEO plugin detected: %s. Meta descriptions will be written to the correct meta keys automatically.', 'auto-multi-meta' ),
			'<strong>' . esc_html( $auto_multi_meta_seo_labels[ $auto_multi_meta_seo_plugin ] ) . '</strong>'
		)
	);
} else {
	printf(
		'<div class="notice notice-warning inline"><p>%s</p></div>',
		esc_html__( 'No supported SEO plugin detected (Yoast SEO or RankMath). Meta descriptions cannot be stored without an active SEO plugin.', 'auto-multi-meta' )
	);
}

// AI Provider section.
printf( '<h2>%s</h2>', esc_html__( 'AI Provider', 'auto-multi-meta' ) );
printf( '<table class="form-table" role="presentation"><tbody>' );

// Provider dropdown.
printf( '<tr><th scope="row">' );
printf( '<label for="amm-api-provider">%s</label>', esc_html__( 'Provider', 'auto-multi-meta' ) );
printf( '</th><td>' );
printf( '<select name="%s" id="amm-api-provider">', esc_attr( AMM_OPT_API_PROVIDER ) );
foreach ( $auto_multi_meta_provider_labels as $auto_multi_meta_provider_value => $auto_multi_meta_provider_label ) {
	printf(
		'<option value="%s"%s>%s</option>',
		esc_attr( $auto_multi_meta_provider_value ),
		selected( $auto_multi_meta_api_provider, $auto_multi_meta_provider_value, false ),
		esc_html( $auto_multi_meta_provider_label )
	);
}
printf( '</select>' );
printf( '<p class="description">%s</p>', esc_html__( 'Select which AI service to use for generating meta descriptions.', 'auto-multi-meta' ) );
printf( '</td></tr>' );

// API Key.
printf( '<tr><th scope="row">' );
printf( '<label for="amm-api-key">%s</label>', esc_html__( 'API Key', 'auto-multi-meta' ) );
printf( '</th><td>' );
printf(
	'<input type="password" name="%s" id="amm-api-key" value="%s" class="regular-text" autocomplete="new-password" />',
	esc_attr( AMM_OPT_API_KEY ),
	esc_attr( $auto_multi_meta_api_key )
);
printf( '<p class="description">%s</p>', esc_html__( 'Your API key. Leave blank to keep the existing saved key.', 'auto-multi-meta' ) );
printf( '</td></tr>' );

// Model.
printf( '<tr><th scope="row">' );
printf( '<label for="amm-model">%s</label>', esc_html__( 'Model', 'auto-multi-meta' ) );
printf( '</th><td>' );
printf(
	'<input type="text" name="%s" id="amm-model" value="%s" class="regular-text" placeholder="gpt-4o-mini" />',
	esc_attr( AMM_OPT_MODEL ),
	esc_attr( $auto_multi_meta_model )
);
printf( '<p class="description">%s</p>', esc_html__( 'Model name to use. Examples: gpt-4o-mini, claude-3-haiku-20240307, openai/gpt-4o-mini.', 'auto-multi-meta' ) );
printf( '</td></tr>' );

// Max Tokens.
printf( '<tr><th scope="row">' );
printf( '<label for="amm-max-tokens">%s</label>', esc_html__( 'Max Tokens', 'auto-multi-meta' ) );
printf( '</th><td>' );
printf(
	'<input type="number" name="%s" id="amm-max-tokens" value="%s" class="small-text" min="50" max="4096" step="10" />',
	esc_attr( AMM_OPT_MAX_TOKENS ),
	esc_attr( (string) $auto_multi_meta_max_tokens )
);
printf( '<p class="description">%s</p>', esc_html__( 'Maximum tokens for the AI response (50–4096). Default: 300.', 'auto-multi-meta' ) );
printf( '</td></tr>' );

printf( '</tbody></table>' );

// Test Connection button.
printf( '<div class="amm-test-connection">' );
printf(
	'<button type="button" id="amm-test-connection" class="button button-secondary">%s</button>',
	esc_html__( 'Test Connection', 'auto-multi-meta' )
);
printf( '<span id="amm-test-result" class="amm-test-result" style="display:none;" aria-live="polite"></span>' );
printf( '</div>' );

// Prompt Templates section.
printf( '<h2>%s</h2>', esc_html__( 'Prompt Templates', 'auto-multi-meta' ) );
printf(
	'<p class="description">%s</p>',
	sprintf(
		/* translators: Placeholder token list. */
		esc_html__( 'Use tokens in your prompts: %s', 'auto-multi-meta' ),
		'<code>{term_name}</code>, <code>{term_slug}</code>, <code>{taxonomy}</code>, <code>{product_list}</code>, <code>{post_title}</code>, <code>{post_excerpt}</code>, <code>{post_content}</code>, <code>{categories}</code>, <code>{tags}</code>, <code>{post_type}</code>'
	)
);

printf( '<table class="form-table" role="presentation"><tbody>' );

// Terms prompt textarea.
printf( '<tr><th scope="row">' );
printf( '<label for="amm-prompt-terms">%s</label>', esc_html__( 'Taxonomy Terms Prompt', 'auto-multi-meta' ) );
printf( '</th><td>' );
printf(
	'<textarea name="%s" id="amm-prompt-terms" rows="5" class="large-text">%s</textarea>',
	esc_attr( AMM_OPT_PROMPT_TEMPLATE_TERMS ),
	esc_textarea( $auto_multi_meta_prompt_terms )
);
printf( '</td></tr>' );

// Posts prompt textarea.
printf( '<tr><th scope="row">' );
printf( '<label for="amm-prompt-posts">%s</label>', esc_html__( 'Posts / Pages Prompt', 'auto-multi-meta' ) );
printf( '</th><td>' );
printf(
	'<textarea name="%s" id="amm-prompt-posts" rows="5" class="large-text">%s</textarea>',
	esc_attr( AMM_OPT_PROMPT_TEMPLATE_POSTS ),
	esc_textarea( $auto_multi_meta_prompt_posts )
);
printf( '</td></tr>' );

// Overwrite existing checkbox.
printf( '<tr><th scope="row">%s</th><td>', esc_html__( 'Overwrite Existing', 'auto-multi-meta' ) );
printf(
	'<label for="amm-overwrite"><input type="checkbox" name="%s" id="amm-overwrite" value="1"%s /> %s</label>',
	esc_attr( AMM_OPT_OVERWRITE_EXISTING ),
	checked( $auto_multi_meta_overwrite, true, false ),
	esc_html__( 'Regenerate descriptions for items that already have one.', 'auto-multi-meta' )
);
printf( '</td></tr>' );

printf( '</tbody></table>' );
printf( '</div>' );

// ===== Taxonomies Tab =====
printf( '<div id="taxonomies-panel" class="amm-tab-panel" style="display:none;">' );
printf( '<h2>%s</h2>', esc_html__( 'Enabled Taxonomies', 'auto-multi-meta' ) );
printf(
	'<p class="description">%s</p>',
	esc_html__( 'Select the taxonomies for which Auto Multi-Meta will generate meta descriptions on archive pages.', 'auto-multi-meta' )
);
printf(
	'<p><a href="%s" class="button button-secondary">%s</a></p>',
	esc_url( admin_url( 'tools.php?page=amm-term-manager' ) ),
	esc_html__( 'Open Term Manager →', 'auto-multi-meta' )
);

// Hidden flag: lets the sanitise callback detect when this tab was submitted.
printf( '<input type="hidden" name="amm_taxonomies_submitted" value="1" />' );

if ( empty( $auto_multi_meta_display_taxonomies ) ) {
	printf( '<p>%s</p>', esc_html__( 'No public taxonomies found.', 'auto-multi-meta' ) );
} else {
	printf( '<div class="amm-checklist-actions">' );
	printf(
		'<button type="button" class="button amm-check-all" data-group="taxonomies">%s</button>',
		esc_html__( 'Check All', 'auto-multi-meta' )
	);
	printf(
		'<button type="button" class="button amm-uncheck-all" data-group="taxonomies">%s</button>',
		esc_html__( 'Uncheck All', 'auto-multi-meta' )
	);
	printf( '</div>' );

	printf( '<ul class="amm-checklist" id="amm-taxonomy-list">' );
	foreach ( $auto_multi_meta_display_taxonomies as $auto_multi_meta_taxonomy ) {
		printf(
			'<li><label><input type="checkbox" name="%s[]" value="%s"%s /> <strong>%s</strong> <code>%s</code></label></li>',
			esc_attr( AMM_OPT_ENABLED_TAXONOMIES ),
			esc_attr( $auto_multi_meta_taxonomy->name ),
			checked( in_array( $auto_multi_meta_taxonomy->name, $auto_multi_meta_enabled_taxonomies, true ), true, false ),
			esc_html( $auto_multi_meta_taxonomy->label ),
			esc_html( $auto_multi_meta_taxonomy->name )
		);
	}
	printf( '</ul>' );
}

printf( '</div>' );

// ===== Post Types Tab =====
printf( '<div id="post-types-panel" class="amm-tab-panel" style="display:none;">' );
printf( '<h2>%s</h2>', esc_html__( 'Enabled Post Types', 'auto-multi-meta' ) );
printf(
	'<p class="description">%s</p>',
	esc_html__( 'Select the post types for which Auto Multi-Meta will generate meta descriptions.', 'auto-multi-meta' )
);
printf(
	'<p><a href="%s" class="button button-secondary">%s</a></p>',
	esc_url( admin_url( 'tools.php?page=amm-post-manager' ) ),
	esc_html__( 'Open Post Manager →', 'auto-multi-meta' )
);

// Hidden flag: lets the sanitise callback detect when this tab was submitted.
printf( '<input type="hidden" name="amm_post_types_submitted" value="1" />' );

if ( empty( $auto_multi_meta_display_post_types ) ) {
	printf( '<p>%s</p>', esc_html__( 'No public post types found.', 'auto-multi-meta' ) );
} else {
	printf( '<div class="amm-checklist-actions">' );
	printf(
		'<button type="button" class="button amm-check-all" data-group="post-types">%s</button>',
		esc_html__( 'Check All', 'auto-multi-meta' )
	);
	printf(
		'<button type="button" class="button amm-uncheck-all" data-group="post-types">%s</button>',
		esc_html__( 'Uncheck All', 'auto-multi-meta' )
	);
	printf( '</div>' );

	printf( '<ul class="amm-checklist" id="amm-post-type-list">' );
	foreach ( $auto_multi_meta_display_post_types as $auto_multi_meta_post_type ) {
		printf(
			'<li><label><input type="checkbox" name="%s[]" value="%s"%s /> <strong>%s</strong> <code>%s</code></label></li>',
			esc_attr( AMM_OPT_ENABLED_POST_TYPES ),
			esc_attr( $auto_multi_meta_post_type->name ),
			checked( in_array( $auto_multi_meta_post_type->name, $auto_multi_meta_enabled_post_types, true ), true, false ),
			esc_html( $auto_multi_meta_post_type->label ),
			esc_html( $auto_multi_meta_post_type->name )
		);
	}
	printf( '</ul>' );
}

printf( '</div>' );

// ===== Log Tab =====
printf( '<div id="log-panel" class="amm-tab-panel" style="display:none;">' );
printf( '<h2>%s</h2>', esc_html__( 'Activity Log', 'auto-multi-meta' ) );

if ( empty( $auto_multi_meta_generation_log ) ) {
	printf(
		'<p class="description">%s</p>',
		esc_html__( 'No generation activity yet. Activity will appear here once you start generating meta descriptions.', 'auto-multi-meta' )
	);
} else {
	printf(
		'<p class="description">%s</p>',
		sprintf(
			/* translators: %d: number of log entries shown. */
			esc_html__( 'Showing %d most recent generation attempts (newest first).', 'auto-multi-meta' ),
			count( $auto_multi_meta_generation_log )
		)
	);

	printf( '<table class="wp-list-table widefat fixed striped amm-log-table">' );
	printf( '<thead><tr>' );
	printf( '<th class="column-timestamp">%s</th>', esc_html__( 'Date / Time', 'auto-multi-meta' ) );
	printf( '<th class="column-type">%s</th>', esc_html__( 'Type', 'auto-multi-meta' ) );
	printf( '<th class="column-id">%s</th>', esc_html__( 'ID', 'auto-multi-meta' ) );
	printf( '<th class="column-provider">%s</th>', esc_html__( 'Provider', 'auto-multi-meta' ) );
	printf( '<th class="column-model">%s</th>', esc_html__( 'Model', 'auto-multi-meta' ) );
	printf( '<th class="column-status">%s</th>', esc_html__( 'Status', 'auto-multi-meta' ) );
	printf( '<th class="column-message">%s</th>', esc_html__( 'Message', 'auto-multi-meta' ) );
	printf( '</tr></thead><tbody>' );

	foreach ( $auto_multi_meta_generation_log as $auto_multi_meta_entry ) {
		$auto_multi_meta_e_type     = isset( $auto_multi_meta_entry['type'] ) ? sanitize_key( $auto_multi_meta_entry['type'] ) : '';
		$auto_multi_meta_e_id       = isset( $auto_multi_meta_entry['id'] ) ? (int) $auto_multi_meta_entry['id'] : 0;
		$auto_multi_meta_e_taxonomy = isset( $auto_multi_meta_entry['taxonomy'] ) ? sanitize_key( $auto_multi_meta_entry['taxonomy'] ) : '';
		$auto_multi_meta_e_provider = isset( $auto_multi_meta_entry['provider'] ) ? sanitize_key( $auto_multi_meta_entry['provider'] ) : '';
		$auto_multi_meta_e_model    = isset( $auto_multi_meta_entry['model'] ) ? sanitize_text_field( $auto_multi_meta_entry['model'] ) : '';
		$auto_multi_meta_e_status   = isset( $auto_multi_meta_entry['status'] ) ? sanitize_key( $auto_multi_meta_entry['status'] ) : '';
		$auto_multi_meta_e_message  = isset( $auto_multi_meta_entry['message'] ) ? sanitize_text_field( $auto_multi_meta_entry['message'] ) : '';
		$auto_multi_meta_e_ts       = isset( $auto_multi_meta_entry['timestamp'] ) ? sanitize_text_field( $auto_multi_meta_entry['timestamp'] ) : '';

		$auto_multi_meta_status_class = 'generated' === $auto_multi_meta_e_status ? 'amm-status-ok' : ( 'error' === $auto_multi_meta_e_status ? 'amm-status-error' : 'amm-status-skip' );
		$auto_multi_meta_type_label   = 'term' === $auto_multi_meta_e_type && '' !== $auto_multi_meta_e_taxonomy
			? 'term (' . $auto_multi_meta_e_taxonomy . ')'
			: $auto_multi_meta_e_type;

		printf( '<tr>' );
		printf( '<td>%s</td>', esc_html( $auto_multi_meta_e_ts ) );
		printf( '<td>%s</td>', esc_html( $auto_multi_meta_type_label ) );
		printf( '<td>%s</td>', esc_html( (string) $auto_multi_meta_e_id ) );
		printf( '<td>%s</td>', esc_html( $auto_multi_meta_e_provider ) );
		printf( '<td><code>%s</code></td>', esc_html( $auto_multi_meta_e_model ) );
		printf(
			'<td><span class="amm-status-badge %s">%s</span></td>',
			esc_attr( $auto_multi_meta_status_class ),
			esc_html( $auto_multi_meta_e_status )
		);
		printf( '<td class="column-message">%s</td>', esc_html( $auto_multi_meta_e_message ) );
		printf( '</tr>' );
	}

	printf( '</tbody></table>' );
}

printf( '</div>' );

// ===== Batch Tab =====
printf( '<div id="batch-panel" class="amm-tab-panel" style="display:none;">' );
printf( '<h2>%s</h2>', esc_html__( 'Background Batch Processing', 'auto-multi-meta' ) );
printf(
	'<p class="description">%s</p>',
	esc_html__( 'Run a background job to generate meta descriptions for all items that are missing one. The job runs in the background via Action Scheduler (or WP-Cron as a fallback) — you can close this page and return later to check progress.', 'auto-multi-meta' )
);

// Rate Limiting.
printf( '<h3>%s</h3>', esc_html__( 'Rate Limiting', 'auto-multi-meta' ) );
printf( '<table class="form-table" role="presentation"><tbody>' );
printf( '<tr><th scope="row">' );
printf( '<label for="amm-batch-delay-input">%s</label>', esc_html__( 'Delay Between Requests', 'auto-multi-meta' ) );
printf( '</th><td>' );
printf(
	'<input type="number" name="%s" id="amm-batch-delay-input" value="%s" class="small-text" min="0" max="60" step="1" />',
	esc_attr( AMM_OPT_BATCH_DELAY ),
	esc_attr( (string) $auto_multi_meta_batch_delay )
);
printf( ' %s', esc_html__( 'seconds', 'auto-multi-meta' ) );
printf( '<p class="description">%s</p>', esc_html__( 'Pause between each AI API call to avoid rate limiting (0–60 seconds). Default: 5 seconds.', 'auto-multi-meta' ) );
printf( '</td></tr>' );
printf( '</tbody></table>' );

// Generate All Missing.
printf( '<h3>%s</h3>', esc_html__( 'Generate All Missing', 'auto-multi-meta' ) );
printf(
	'<p class="description">%s</p>',
	esc_html__( 'Queues all enabled taxonomies and post types that are missing meta descriptions. Save your settings first if you have made changes above.', 'auto-multi-meta' )
);

printf( '<div class="amm-batch-controls">' );
printf(
	'<select id="amm-batch-type-select" class="amm-batch-type-select"><option value="all">%s</option><option value="term">%s</option><option value="post">%s</option></select>',
	esc_html__( 'Terms &amp; Posts', 'auto-multi-meta' ),
	esc_html__( 'Terms only', 'auto-multi-meta' ),
	esc_html__( 'Posts only', 'auto-multi-meta' )
);
printf( '<input type="hidden" id="amm-batch-type" value="all" />' );
printf(
	'<button type="button" id="amm-batch-start" class="button button-primary">%s</button>',
	esc_html__( 'Generate All Missing', 'auto-multi-meta' )
);
printf(
	'<button type="button" id="amm-batch-cancel" class="button button-secondary" style="display:none;">%s</button>',
	esc_html__( 'Cancel Batch', 'auto-multi-meta' )
);
printf( '</div>' );

printf( '<div id="amm-batch-progress-wrap" class="amm-batch-progress-wrap" style="display:none;">' );
printf( '<div class="amm-batch-bar-track">' );
printf( '<div id="amm-batch-bar" class="amm-batch-bar" style="width:0%%;"></div>' );
printf( '</div>' );
printf( '<p id="amm-batch-status" class="amm-batch-status" aria-live="polite"></p>' );
printf( '</div>' );

printf( '</div>' );

printf( '</div>' );

submit_button( __( 'Save Settings', 'auto-multi-meta' ) );

printf( '</form>' );
printf( '</div>' );
