<?php
/**
 * Admin term manager template.
 *
 * Variables provided by Admin_Hooks::render_term_manager():
 *
 * @var \Auto_Multi_Meta\Term_Manager $term_manager       Term manager list table instance.
 * @var array<int, string>            $enabled_taxonomies Enabled taxonomy slugs.
 *
 * @package Auto_Multi_Meta
 */

defined( 'ABSPATH' ) || die();

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
// Reason: Template-scoped locals passed from the including method; not true PHP globals.

$term_manager->prepare_items();

// phpcs:disable WordPress.Security.NonceVerification.Recommended -- read-only filter.
$current_filter = isset( $_GET['amm_taxonomy'] )
	? sanitize_key( wp_unslash( $_GET['amm_taxonomy'] ) )
	: '';
// phpcs:enable WordPress.Security.NonceVerification.Recommended

// Build list of taxonomy objects for the filter dropdown.
$filter_options = [];
foreach ( $enabled_taxonomies as $amm_tax_slug ) {
	$amm_tax_obj = get_taxonomy( $amm_tax_slug );
	if ( $amm_tax_obj ) {
		$filter_options[ $amm_tax_slug ] = $amm_tax_obj->label;
	}
}

$settings_url     = admin_url( 'tools.php?page=auto-multi-meta#taxonomies' );
$post_manager_url = admin_url( 'tools.php?page=amm-post-manager' );
?>

<div class="wrap amm-wrap">

	<h1 class="wp-heading-inline"><?php esc_html_e( 'Term Manager', 'auto-multi-meta' ); ?></h1>

	<div class="amm-manager-nav">
		<?php
		printf(
			'<a href="%s" class="button">%s</a>',
			esc_url( $post_manager_url ),
			esc_html__( 'Switch to Post Manager', 'auto-multi-meta' )
		);
		printf(
			'<a href="%s" class="button">%s</a>',
			esc_url( $settings_url ),
			esc_html__( '← Settings', 'auto-multi-meta' )
		);
		?>
	</div>

	<p class="description">
		<?php esc_html_e( 'Browse taxonomy terms, check their meta description status, and generate or preview descriptions.', 'auto-multi-meta' ); ?>
	</p>

	<?php if ( empty( $enabled_taxonomies ) ) : ?>

	<div class="notice notice-warning">
		<p>
			<?php
			printf(
				wp_kses(
					/* translators: %s: URL to settings page. */
					__( 'No taxonomies are enabled. <a href="%s">Enable taxonomies on the Settings page</a> first.', 'auto-multi-meta' ),
					[ 'a' => [ 'href' => [] ] ]
				),
				esc_url( $settings_url )
			);
			?>
		</p>
	</div>

	<?php else : ?>

	<!-- Taxonomy filter -->
	<div class="amm-manager-filters">
		<?php
		$filter_url = admin_url( 'tools.php?page=amm-term-manager' );

		printf(
			'<form method="get" action="%s" class="amm-filter-form">',
			esc_url( $filter_url )
		);
		printf( '<input type="hidden" name="page" value="amm-term-manager" />' );
		printf(
			'<label for="amm-taxonomy-filter">%s</label>',
			esc_html__( 'Filter by taxonomy:', 'auto-multi-meta' )
		);
		printf( '<select name="amm_taxonomy" id="amm-taxonomy-filter">' );
		printf(
			'<option value="">%s</option>',
			esc_html__( '— All Enabled Taxonomies —', 'auto-multi-meta' )
		);

		foreach ( $filter_options as $amm_tax_slug => $amm_tax_label ) {
			printf(
				'<option value="%s"%s>%s</option>',
				esc_attr( $amm_tax_slug ),
				selected( $current_filter, $amm_tax_slug, false ),
				esc_html( $amm_tax_label )
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

	<!-- List table form -->
	<form id="amm-term-manager-form" method="post">
		<?php
		wp_nonce_field( 'amm_admin', 'amm_nonce' );
		printf( '<input type="hidden" name="amm_type" value="term" />' );
		$term_manager->display();
		?>
	</form>

	<?php endif; ?>

</div><!-- .amm-wrap -->
<?php
// phpcs:enable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
