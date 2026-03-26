<?php
/**
 * Admin post manager template.
 *
 * Variables provided by Admin_Hooks::render_post_manager():
 *
 * @var \Auto_Multi_Meta\Post_Manager $post_manager        Post manager list table instance.
 * @var array<int, string>            $enabled_post_types  Enabled post type slugs.
 *
 * @package Auto_Multi_Meta
 */

defined( 'ABSPATH' ) || die();

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
// Reason: Template-scoped locals passed from the including method; not true PHP globals.

$post_manager->prepare_items();

// phpcs:disable WordPress.Security.NonceVerification.Recommended -- read-only filter.
$current_filter = isset( $_GET['amm_post_type'] )
	? sanitize_key( wp_unslash( $_GET['amm_post_type'] ) )
	: '';
// phpcs:enable WordPress.Security.NonceVerification.Recommended

// Exclude internal post types from the filter.
$amm_excluded_types = [
	'attachment',
	'revision',
	'nav_menu_item',
	'custom_css',
	'customize_changeset',
	'oembed_cache',
	'user_request',
	'wp_block',
	'wp_template',
	'wp_template_part',
	'wp_global_styles',
	'wp_navigation',
];

// Build list of post type objects for the filter dropdown.
$filter_options = [];
foreach ( $enabled_post_types as $amm_pt_slug ) {
	if ( in_array( $amm_pt_slug, $amm_excluded_types, true ) ) {
		continue;
	}
	$amm_pt_obj = get_post_type_object( $amm_pt_slug );
	if ( $amm_pt_obj ) {
		$filter_options[ $amm_pt_slug ] = $amm_pt_obj->label;
	}
}

$settings_url     = admin_url( 'tools.php?page=auto-multi-meta#post-types' );
$term_manager_url = admin_url( 'tools.php?page=amm-term-manager' );
?>

<div class="wrap amm-wrap">

	<h1 class="wp-heading-inline"><?php esc_html_e( 'Post Manager', 'auto-multi-meta' ); ?></h1>

	<div class="amm-manager-nav">
		<?php
		printf(
			'<a href="%s" class="button">%s</a>',
			esc_url( $term_manager_url ),
			esc_html__( 'Switch to Term Manager', 'auto-multi-meta' )
		);
		printf(
			'<a href="%s" class="button">%s</a>',
			esc_url( $settings_url ),
			esc_html__( '← Settings', 'auto-multi-meta' )
		);
		?>
	</div>

	<p class="description">
		<?php esc_html_e( 'Browse posts, pages, and custom post types — check meta description status, generate, or preview descriptions.', 'auto-multi-meta' ); ?>
	</p>

	<?php if ( empty( $filter_options ) ) : ?>

	<div class="notice notice-warning">
		<p>
			<?php
			printf(
				wp_kses(
					/* translators: %s: URL to settings page. */
					__( 'No post types are enabled. <a href="%s">Enable post types on the Settings page</a> first.', 'auto-multi-meta' ),
					[ 'a' => [ 'href' => [] ] ]
				),
				esc_url( $settings_url )
			);
			?>
		</p>
	</div>

	<?php else : ?>

	<!-- Post type filter -->
	<div class="amm-manager-filters">
		<?php
		$filter_url = admin_url( 'tools.php?page=amm-post-manager' );

		printf(
			'<form method="get" action="%s" class="amm-filter-form">',
			esc_url( $filter_url )
		);
		printf( '<input type="hidden" name="page" value="amm-post-manager" />' );
		printf(
			'<label for="amm-post-type-filter">%s</label>',
			esc_html__( 'Filter by post type:', 'auto-multi-meta' )
		);
		printf( '<select name="amm_post_type" id="amm-post-type-filter">' );
		printf(
			'<option value="">%s</option>',
			esc_html__( '— All Enabled Post Types —', 'auto-multi-meta' )
		);

		foreach ( $filter_options as $amm_pt_slug => $amm_pt_label ) {
			printf(
				'<option value="%s"%s>%s</option>',
				esc_attr( $amm_pt_slug ),
				selected( $current_filter, $amm_pt_slug, false ),
				esc_html( $amm_pt_label )
			);
		}

		printf( '</select>' );
		printf(
			'<button type="submit" class="button">%s</button>',
			esc_html__( 'Filter', 'auto-multi-meta' )
		);
		printf( '</form>' );
		?>
	</div>

	<!-- Notification area for AJAX feedback -->
	<div id="amm-manager-notice" class="amm-manager-notice" style="display:none;" aria-live="polite"></div>

	<!-- Preview area shown below table -->
	<div id="amm-preview-area" class="amm-preview-area" style="display:none;">
		<h3><?php esc_html_e( 'Preview', 'auto-multi-meta' ); ?></h3>
		<div id="amm-preview-content" class="amm-preview-content"></div>
		<div class="amm-preview-actions">
			<?php
			printf(
				'<button type="button" id="amm-preview-save" class="button button-primary">%s</button>',
				esc_html__( 'Save This Description', 'auto-multi-meta' )
			);
			printf(
				'<button type="button" id="amm-preview-dismiss" class="button">%s</button>',
				esc_html__( 'Dismiss', 'auto-multi-meta' )
			);
			?>
		</div>
	</div>

	<!-- Background batch generation panel -->
	<div id="amm-batch-panel" class="amm-batch-panel">
		<?php printf( '<input type="hidden" id="amm-batch-type" value="post" />' ); ?>
		<div class="amm-batch-actions">
			<?php
			printf(
				'<button type="button" id="amm-batch-start" class="button button-primary">%s</button>',
				esc_html__( 'Generate All Missing', 'auto-multi-meta' )
			);
			printf(
				'<button type="button" id="amm-batch-cancel" class="button" style="display:none;">%s</button>',
				esc_html__( 'Cancel Batch', 'auto-multi-meta' )
			);
			?>
		</div>
		<div id="amm-batch-progress-wrap" class="amm-batch-progress-wrap" style="display:none;" aria-live="polite">
			<div class="amm-batch-progress">
				<?php printf( '<div id="amm-batch-bar" class="amm-batch-bar" style="width:0%%"></div>' ); ?>
			</div>
			<?php printf( '<p id="amm-batch-status" class="amm-batch-status"></p>' ); ?>
		</div>
	</div>

	<!-- List table form -->
	<form id="amm-post-manager-form" method="post">
		<?php
		wp_nonce_field( 'amm_admin', 'amm_nonce' );
		printf( '<input type="hidden" name="amm_type" value="post" />' );
		$post_manager->display();
		?>
	</form>

	<?php endif; ?>

</div><!-- .amm-wrap -->
<?php
// phpcs:enable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
