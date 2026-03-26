<?php
/**
 * Admin settings page template.
 *
 * Variables provided by Admin_Hooks::render_settings_page():
 *
 * @var string       $seo_plugin     Detected SEO plugin ('yoast', 'rankmath', 'none').
 * @var \WP_Taxonomy[] $all_taxonomies  Registered public taxonomies (objects).
 * @var \WP_Post_Type[] $all_post_types  Registered public post types (objects).
 *
 * @package Auto_Multi_Meta
 */

defined( 'ABSPATH' ) || die();

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
// Reason: These variables are template-scoped locals, not true PHP globals. PHPCS flags
// them because this file is parsed in isolation (outside the including method's scope).

$enabled_taxonomies = (array) get_option( AMM_OPT_ENABLED_TAXONOMIES, [] );
$enabled_post_types = (array) get_option( AMM_OPT_ENABLED_POST_TYPES, [] );
$api_provider       = (string) get_option( AMM_OPT_API_PROVIDER, AMM_DEFAULT_API_PROVIDER );
$api_key            = (string) get_option( AMM_OPT_API_KEY, '' );
$model              = (string) get_option( AMM_OPT_MODEL, AMM_DEFAULT_MODEL );
$max_tokens         = (int) get_option( AMM_OPT_MAX_TOKENS, AMM_DEFAULT_MAX_TOKENS );
$prompt_terms       = (string) get_option( AMM_OPT_PROMPT_TEMPLATE_TERMS, AMM_DEFAULT_PROMPT_TEMPLATE_TERMS );
$prompt_posts       = (string) get_option( AMM_OPT_PROMPT_TEMPLATE_POSTS, AMM_DEFAULT_PROMPT_TEMPLATE_POSTS );
$overwrite          = (bool) get_option( AMM_OPT_OVERWRITE_EXISTING, AMM_DEFAULT_OVERWRITE_EXISTING );
$batch_delay        = (int) get_option( AMM_OPT_BATCH_DELAY, AMM_DEFAULT_BATCH_DELAY );

$seo_labels = [
	'yoast'    => __( 'Yoast SEO', 'auto-multi-meta' ),
	'rankmath' => __( 'RankMath', 'auto-multi-meta' ),
	'none'     => __( 'None detected', 'auto-multi-meta' ),
];

$provider_labels = [
	'openai'     => __( 'OpenAI', 'auto-multi-meta' ),
	'anthropic'  => __( 'Anthropic', 'auto-multi-meta' ),
	'openrouter' => __( 'OpenRouter', 'auto-multi-meta' ),
];

// Exclude internal / attachment post types.
$excluded_post_types = [ 'attachment', 'revision', 'nav_menu_item', 'custom_css', 'customize_changeset', 'oembed_cache', 'user_request', 'wp_block', 'wp_template', 'wp_template_part', 'wp_global_styles', 'wp_navigation' ];
$display_post_types  = array_filter(
	$all_post_types,
	function ( $pt ) use ( $excluded_post_types ) {
		return ! in_array( $pt->name, $excluded_post_types, true );
	}
);

// Exclude internal taxonomies.
$excluded_taxonomies = [ 'nav_menu', 'link_category', 'post_format' ];
$display_taxonomies  = array_filter(
	$all_taxonomies,
	function ( $tax ) use ( $excluded_taxonomies ) {
		return ! in_array( $tax->name, $excluded_taxonomies, true );
	}
);
?>

<div class="wrap amm-wrap">
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

	<nav class="nav-tab-wrapper wp-clearfix" aria-label="<?php esc_attr_e( 'Settings tabs', 'auto-multi-meta' ); ?>">
		<a href="#settings" class="nav-tab" data-tab="settings">
			<?php esc_html_e( 'Settings', 'auto-multi-meta' ); ?>
		</a>
		<a href="#taxonomies" class="nav-tab" data-tab="taxonomies">
			<?php esc_html_e( 'Taxonomies', 'auto-multi-meta' ); ?>
		</a>
		<a href="#post-types" class="nav-tab" data-tab="post-types">
			<?php esc_html_e( 'Post Types', 'auto-multi-meta' ); ?>
		</a>
		<a href="#log" class="nav-tab" data-tab="log">
			<?php esc_html_e( 'Log', 'auto-multi-meta' ); ?>
		</a>
		<a href="#batch" class="nav-tab" data-tab="batch">
			<?php esc_html_e( 'Batch', 'auto-multi-meta' ); ?>
		</a>
	</nav>

	<form method="post" action="options.php" id="amm-settings-form">
		<?php settings_fields( \Auto_Multi_Meta\Settings::OPTION_GROUP ); ?>

		<div class="amm-tab-content">

			<!-- ===== Settings Tab ===== -->
			<div id="settings-panel" class="amm-tab-panel">

				<?php if ( 'none' !== $seo_plugin ) : ?>
				<div class="notice notice-success inline">
					<p>
						<?php
						printf(
							/* translators: %s: SEO plugin name. */
							esc_html__( 'SEO plugin detected: %s. Meta descriptions will be written to the correct meta keys automatically.', 'auto-multi-meta' ),
							'<strong>' . esc_html( $seo_labels[ $seo_plugin ] ) . '</strong>'
						);
						?>
					</p>
				</div>
				<?php else : ?>
				<div class="notice notice-warning inline">
					<p>
						<?php esc_html_e( 'No supported SEO plugin detected (Yoast SEO or RankMath). Meta descriptions cannot be stored without an active SEO plugin.', 'auto-multi-meta' ); ?>
					</p>
				</div>
				<?php endif; ?>

				<h2><?php esc_html_e( 'AI Provider', 'auto-multi-meta' ); ?></h2>

				<table class="form-table" role="presentation">
					<tbody>
						<tr>
							<th scope="row">
								<label for="amm-api-provider"><?php esc_html_e( 'Provider', 'auto-multi-meta' ); ?></label>
							</th>
							<td>
								<select name="<?php echo esc_attr( AMM_OPT_API_PROVIDER ); ?>" id="amm-api-provider">
									<?php foreach ( $provider_labels as $amm_provider_value => $amm_provider_label ) : ?>
									<option value="<?php echo esc_attr( $amm_provider_value ); ?>" <?php selected( $api_provider, $amm_provider_value ); ?>>
										<?php echo esc_html( $amm_provider_label ); ?>
									</option>
									<?php endforeach; ?>
								</select>
								<p class="description">
									<?php esc_html_e( 'Select which AI service to use for generating meta descriptions.', 'auto-multi-meta' ); ?>
								</p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="amm-api-key"><?php esc_html_e( 'API Key', 'auto-multi-meta' ); ?></label>
							</th>
							<td>
								<input
									type="password"
									name="<?php echo esc_attr( AMM_OPT_API_KEY ); ?>"
									id="amm-api-key"
									value="<?php echo esc_attr( $api_key ); ?>"
									class="regular-text"
									autocomplete="new-password"
								/>
								<p class="description">
									<?php esc_html_e( 'Your API key. Leave blank to keep the existing saved key.', 'auto-multi-meta' ); ?>
								</p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="amm-model"><?php esc_html_e( 'Model', 'auto-multi-meta' ); ?></label>
							</th>
							<td>
								<input
									type="text"
									name="<?php echo esc_attr( AMM_OPT_MODEL ); ?>"
									id="amm-model"
									value="<?php echo esc_attr( $model ); ?>"
									class="regular-text"
									placeholder="gpt-4o-mini"
								/>
								<p class="description">
									<?php esc_html_e( 'Model name to use. Examples: gpt-4o-mini, claude-3-haiku-20240307, openai/gpt-4o-mini.', 'auto-multi-meta' ); ?>
								</p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="amm-max-tokens"><?php esc_html_e( 'Max Tokens', 'auto-multi-meta' ); ?></label>
							</th>
							<td>
								<input
									type="number"
									name="<?php echo esc_attr( AMM_OPT_MAX_TOKENS ); ?>"
									id="amm-max-tokens"
									value="<?php echo esc_attr( (string) $max_tokens ); ?>"
									class="small-text"
									min="50"
									max="4096"
									step="10"
								/>
								<p class="description">
									<?php esc_html_e( 'Maximum tokens for the AI response (50–4096). Default: 300.', 'auto-multi-meta' ); ?>
								</p>
							</td>
						</tr>
					</tbody>
				</table>

				<div class="amm-test-connection">
					<?php
					printf(
						'<button type="button" id="amm-test-connection" class="button button-secondary">%s</button>',
						esc_html__( 'Test Connection', 'auto-multi-meta' )
					);
					printf(
						'<span id="amm-test-result" class="amm-test-result" style="display:none;" aria-live="polite"></span>'
					);
					?>
				</div>

				<h2><?php esc_html_e( 'Prompt Templates', 'auto-multi-meta' ); ?></h2>

				<p class="description">
					<?php
					printf(
						/* translators: Placeholder token list. */
						esc_html__( 'Use tokens in your prompts: %s', 'auto-multi-meta' ),
						'<code>{term_name}</code>, <code>{term_slug}</code>, <code>{taxonomy}</code>, <code>{product_list}</code>, <code>{post_title}</code>, <code>{post_excerpt}</code>, <code>{post_content}</code>, <code>{categories}</code>, <code>{tags}</code>, <code>{post_type}</code>'
					);
					?>
				</p>

				<table class="form-table" role="presentation">
					<tbody>
						<tr>
							<th scope="row">
								<label for="amm-prompt-terms"><?php esc_html_e( 'Taxonomy Terms Prompt', 'auto-multi-meta' ); ?></label>
							</th>
							<td>
								<textarea
									name="<?php echo esc_attr( AMM_OPT_PROMPT_TEMPLATE_TERMS ); ?>"
									id="amm-prompt-terms"
									rows="5"
									class="large-text"
								><?php echo esc_textarea( $prompt_terms ); ?></textarea>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="amm-prompt-posts"><?php esc_html_e( 'Posts / Pages Prompt', 'auto-multi-meta' ); ?></label>
							</th>
							<td>
								<textarea
									name="<?php echo esc_attr( AMM_OPT_PROMPT_TEMPLATE_POSTS ); ?>"
									id="amm-prompt-posts"
									rows="5"
									class="large-text"
								><?php echo esc_textarea( $prompt_posts ); ?></textarea>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Overwrite Existing', 'auto-multi-meta' ); ?></th>
							<td>
								<label for="amm-overwrite">
									<input
										type="checkbox"
										name="<?php echo esc_attr( AMM_OPT_OVERWRITE_EXISTING ); ?>"
										id="amm-overwrite"
										value="1"
										<?php checked( $overwrite ); ?>
									/>
									<?php esc_html_e( 'Regenerate descriptions for items that already have one.', 'auto-multi-meta' ); ?>
								</label>
							</td>
						</tr>
					</tbody>
				</table>

			</div><!-- #settings-panel -->

			<!-- ===== Taxonomies Tab ===== -->
			<div id="taxonomies-panel" class="amm-tab-panel" style="display:none;">
				<h2><?php esc_html_e( 'Enabled Taxonomies', 'auto-multi-meta' ); ?></h2>
				<p class="description">
					<?php esc_html_e( 'Select the taxonomies for which Auto Multi-Meta will generate meta descriptions on archive pages.', 'auto-multi-meta' ); ?>
				</p>
				<p>
					<?php
					printf(
						'<a href="%s" class="button button-secondary">%s</a>',
						esc_url( admin_url( 'tools.php?page=amm-term-manager' ) ),
						esc_html__( 'Open Term Manager →', 'auto-multi-meta' )
					);
					?>
				</p>

				<!-- Hidden flag: lets the sanitise callback detect when this tab was submitted. -->
				<input type="hidden" name="amm_taxonomies_submitted" value="1" />

				<?php if ( empty( $display_taxonomies ) ) : ?>
				<p><?php esc_html_e( 'No public taxonomies found.', 'auto-multi-meta' ); ?></p>
				<?php else : ?>
				<div class="amm-checklist-actions">
					<button type="button" class="button amm-check-all" data-group="taxonomies">
						<?php esc_html_e( 'Check All', 'auto-multi-meta' ); ?>
					</button>
					<button type="button" class="button amm-uncheck-all" data-group="taxonomies">
						<?php esc_html_e( 'Uncheck All', 'auto-multi-meta' ); ?>
					</button>
				</div>
				<ul class="amm-checklist" id="amm-taxonomy-list">
					<?php foreach ( $display_taxonomies as $amm_taxonomy ) : ?>
					<li>
						<label>
							<input
								type="checkbox"
								name="<?php echo esc_attr( AMM_OPT_ENABLED_TAXONOMIES ); ?>[]"
								value="<?php echo esc_attr( $amm_taxonomy->name ); ?>"
								<?php checked( in_array( $amm_taxonomy->name, $enabled_taxonomies, true ) ); ?>
							/>
							<strong><?php echo esc_html( $amm_taxonomy->label ); ?></strong>
							<code><?php echo esc_html( $amm_taxonomy->name ); ?></code>
						</label>
					</li>
					<?php endforeach; ?>
				</ul>
				<?php endif; ?>
			</div><!-- #taxonomies-panel -->

			<!-- ===== Post Types Tab ===== -->
			<div id="post-types-panel" class="amm-tab-panel" style="display:none;">
				<h2><?php esc_html_e( 'Enabled Post Types', 'auto-multi-meta' ); ?></h2>
				<p class="description">
					<?php esc_html_e( 'Select the post types for which Auto Multi-Meta will generate meta descriptions.', 'auto-multi-meta' ); ?>
				</p>
				<p>
					<?php
					printf(
						'<a href="%s" class="button button-secondary">%s</a>',
						esc_url( admin_url( 'tools.php?page=amm-post-manager' ) ),
						esc_html__( 'Open Post Manager →', 'auto-multi-meta' )
					);
					?>
				</p>

				<!-- Hidden flag: lets the sanitise callback detect when this tab was submitted. -->
				<input type="hidden" name="amm_post_types_submitted" value="1" />

				<?php if ( empty( $display_post_types ) ) : ?>
				<p><?php esc_html_e( 'No public post types found.', 'auto-multi-meta' ); ?></p>
				<?php else : ?>
				<div class="amm-checklist-actions">
					<button type="button" class="button amm-check-all" data-group="post-types">
						<?php esc_html_e( 'Check All', 'auto-multi-meta' ); ?>
					</button>
					<button type="button" class="button amm-uncheck-all" data-group="post-types">
						<?php esc_html_e( 'Uncheck All', 'auto-multi-meta' ); ?>
					</button>
				</div>
				<ul class="amm-checklist" id="amm-post-type-list">
					<?php foreach ( $display_post_types as $amm_post_type ) : ?>
					<li>
						<label>
							<input
								type="checkbox"
								name="<?php echo esc_attr( AMM_OPT_ENABLED_POST_TYPES ); ?>[]"
								value="<?php echo esc_attr( $amm_post_type->name ); ?>"
								<?php checked( in_array( $amm_post_type->name, $enabled_post_types, true ) ); ?>
							/>
							<strong><?php echo esc_html( $amm_post_type->label ); ?></strong>
							<code><?php echo esc_html( $amm_post_type->name ); ?></code>
						</label>
					</li>
					<?php endforeach; ?>
				</ul>
				<?php endif; ?>
			</div><!-- #post-types-panel -->

			<!-- ===== Log Tab ===== -->
			<div id="log-panel" class="amm-tab-panel" style="display:none;">
				<h2><?php esc_html_e( 'Activity Log', 'auto-multi-meta' ); ?></h2>
				<p class="description">
					<?php esc_html_e( 'Generation history and activity log will appear here in a future release.', 'auto-multi-meta' ); ?>
				</p>
			</div><!-- #log-panel -->

			<!-- ===== Batch Tab ===== -->
			<div id="batch-panel" class="amm-tab-panel" style="display:none;">
				<h2><?php esc_html_e( 'Background Batch Processing', 'auto-multi-meta' ); ?></h2>
				<p class="description">
					<?php esc_html_e( 'Run a background job to generate meta descriptions for all items that are missing one. The job runs in the background via Action Scheduler (or WP-Cron as a fallback) — you can close this page and return later to check progress.', 'auto-multi-meta' ); ?>
				</p>

				<h3><?php esc_html_e( 'Rate Limiting', 'auto-multi-meta' ); ?></h3>
				<table class="form-table" role="presentation">
					<tbody>
						<tr>
							<th scope="row">
								<label for="amm-batch-delay-input"><?php esc_html_e( 'Delay Between Requests', 'auto-multi-meta' ); ?></label>
							</th>
							<td>
								<input
									type="number"
									name="<?php echo esc_attr( AMM_OPT_BATCH_DELAY ); ?>"
									id="amm-batch-delay-input"
									value="<?php echo esc_attr( (string) $batch_delay ); ?>"
									class="small-text"
									min="0"
									max="60"
									step="1"
								/>
								<?php esc_html_e( 'seconds', 'auto-multi-meta' ); ?>
								<p class="description">
									<?php esc_html_e( 'Pause between each AI API call to avoid rate limiting (0–60 seconds). Default: 5 seconds.', 'auto-multi-meta' ); ?>
								</p>
							</td>
						</tr>
					</tbody>
				</table>

				<h3><?php esc_html_e( 'Generate All Missing', 'auto-multi-meta' ); ?></h3>
				<p class="description">
					<?php esc_html_e( 'Queues all enabled taxonomies and post types that are missing meta descriptions. Save your settings first if you have made changes above.', 'auto-multi-meta' ); ?>
				</p>

				<div class="amm-batch-controls">
					<?php
					printf(
						'<select id="amm-batch-type-select" class="amm-batch-type-select"><option value="all">%s</option><option value="term">%s</option><option value="post">%s</option></select>',
						esc_html__( 'Terms &amp; Posts', 'auto-multi-meta' ),
						esc_html__( 'Terms only', 'auto-multi-meta' ),
						esc_html__( 'Posts only', 'auto-multi-meta' )
					);
					?>
					<input type="hidden" id="amm-batch-type" value="all" />
					<?php
					printf(
						'<button type="button" id="amm-batch-start" class="button button-primary">%s</button>',
						esc_html__( 'Generate All Missing', 'auto-multi-meta' )
					);
					printf(
						'<button type="button" id="amm-batch-cancel" class="button button-secondary" style="display:none;">%s</button>',
						esc_html__( 'Cancel Batch', 'auto-multi-meta' )
					);
					?>
				</div>

				<div id="amm-batch-progress-wrap" class="amm-batch-progress-wrap" style="display:none;">
					<div class="amm-batch-bar-track">
						<div id="amm-batch-bar" class="amm-batch-bar" style="width:0%;"></div>
					</div>
					<p id="amm-batch-status" class="amm-batch-status" aria-live="polite"></p>
				</div>

			</div><!-- #batch-panel -->

		</div><!-- .amm-tab-content -->

		<?php submit_button( __( 'Save Settings', 'auto-multi-meta' ) ); ?>

	</form><!-- #amm-settings-form -->
</div><!-- .amm-wrap -->
<?php
// phpcs:enable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
