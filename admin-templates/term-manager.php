<?php
/**
 * Admin term manager template.
 *
 * Variables provided by Admin_Hooks::render_term_manager():
 *
 * @var \Auto_Multi_Meta\Term_Manager $auto_multi_meta_term_manager       Term manager list table instance.
 * @var array<int, string>            $auto_multi_meta_enabled_taxonomies Enabled taxonomy slugs.
 *
 * @package Auto_Multi_Meta
 */

defined( 'ABSPATH' ) || die();

$auto_multi_meta_term_manager->prepare_items();

// phpcs:disable WordPress.Security.NonceVerification.Recommended -- read-only filter.
$auto_multi_meta_current_filter = isset( $_GET['amm_taxonomy'] )
	? sanitize_key( wp_unslash( $_GET['amm_taxonomy'] ) )
	: '';
// phpcs:enable WordPress.Security.NonceVerification.Recommended

// Build list of taxonomy objects for the filter dropdown.
$auto_multi_meta_filter_options = [];
foreach ( $auto_multi_meta_enabled_taxonomies as $auto_multi_meta_tax_slug ) {
	$auto_multi_meta_tax_obj = get_taxonomy( $auto_multi_meta_tax_slug );
	if ( $auto_multi_meta_tax_obj ) {
		$auto_multi_meta_filter_options[ $auto_multi_meta_tax_slug ] = $auto_multi_meta_tax_obj->label;
	}
}

$auto_multi_meta_settings_url     = admin_url( 'tools.php?page=auto-multi-meta#taxonomies' );
$auto_multi_meta_post_manager_url = admin_url( 'tools.php?page=amm-post-manager' );

// Page heading.
printf( '<div class="wrap amm-wrap">' );
printf( '<h1 class="wp-heading-inline">%s</h1>', esc_html__( 'Term Manager', 'auto-multi-meta' ) );

// Navigation buttons.
printf( '<div class="amm-manager-nav">' );
printf(
	'<a href="%s" class="button">%s</a>',
	esc_url( $auto_multi_meta_post_manager_url ),
	esc_html__( 'Switch to Post Manager', 'auto-multi-meta' )
);
printf(
	'<a href="%s" class="button">%s</a>',
	esc_url( $auto_multi_meta_settings_url ),
	esc_html__( '← Settings', 'auto-multi-meta' )
);
printf( '</div>' );

// Description.
printf(
	'<p class="description">%s</p>',
	esc_html__( 'Browse taxonomy terms, check their meta description status, and generate or preview descriptions.', 'auto-multi-meta' )
);

if ( empty( $auto_multi_meta_enabled_taxonomies ) ) {

	// No taxonomies enabled warning.
	printf(
		'<div class="notice notice-warning"><p>%s</p></div>',
		wp_kses(
			sprintf(
				/* translators: %s: URL to settings page. */
				__( 'No taxonomies are enabled. <a href="%s">Enable taxonomies on the Settings page</a> first.', 'auto-multi-meta' ),
				esc_url( $auto_multi_meta_settings_url )
			),
			[ 'a' => [ 'href' => [] ] ]
		)
	);

} else {

	// Taxonomy filter form.
	$auto_multi_meta_filter_url = admin_url( 'tools.php?page=amm-term-manager' );

	printf( '<div class="amm-manager-filters">' );
	printf(
		'<form method="get" action="%s" class="amm-filter-form">',
		esc_url( $auto_multi_meta_filter_url )
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

	foreach ( $auto_multi_meta_filter_options as $auto_multi_meta_tax_slug => $auto_multi_meta_tax_label ) {
		printf(
			'<option value="%s"%s>%s</option>',
			esc_attr( $auto_multi_meta_tax_slug ),
			selected( $auto_multi_meta_current_filter, $auto_multi_meta_tax_slug, false ),
			esc_html( $auto_multi_meta_tax_label )
		);
	}

	printf( '</select>' );
	printf(
		'<button type="submit" class="button">%s</button>',
		esc_html__( 'Filter', 'auto-multi-meta' )
	);
	printf( '</form>' );
	printf( '</div>' );

	// Notification area for AJAX feedback.
	printf( '<div id="amm-manager-notice" class="amm-manager-notice" style="display:none;" aria-live="polite"></div>' );

	// Preview area.
	printf( '<div id="amm-preview-area" class="amm-preview-area" style="display:none;">' );
	printf( '<h3>%s</h3>', esc_html__( 'Preview', 'auto-multi-meta' ) );
	printf( '<div id="amm-preview-content" class="amm-preview-content"></div>' );
	printf( '<div class="amm-preview-actions">' );
	printf(
		'<button type="button" id="amm-preview-save" class="button button-primary">%s</button>',
		esc_html__( 'Save This Description', 'auto-multi-meta' )
	);
	printf(
		'<button type="button" id="amm-preview-dismiss" class="button">%s</button>',
		esc_html__( 'Dismiss', 'auto-multi-meta' )
	);
	printf( '</div>' );
	printf( '</div>' );

	// Background batch generation panel.
	printf( '<div id="amm-batch-panel" class="amm-batch-panel">' );
	printf( '<input type="hidden" id="amm-batch-type" value="term" />' );
	printf( '<div class="amm-batch-actions">' );
	printf(
		'<button type="button" id="amm-batch-start" class="button button-primary">%s</button>',
		esc_html__( 'Generate All Missing', 'auto-multi-meta' )
	);
	printf(
		'<button type="button" id="amm-batch-cancel" class="button" style="display:none;">%s</button>',
		esc_html__( 'Cancel Batch', 'auto-multi-meta' )
	);
	printf( '</div>' );
	printf( '<div id="amm-batch-progress-wrap" class="amm-batch-progress-wrap" style="display:none;" aria-live="polite">' );
	printf( '<div class="amm-batch-progress">' );
	printf( '<div id="amm-batch-bar" class="amm-batch-bar" style="width:0%%"></div>' );
	printf( '</div>' );
	printf( '<p id="amm-batch-status" class="amm-batch-status"></p>' );
	printf( '</div>' );
	printf( '</div>' );

	// List table form.
	printf( '<form id="amm-term-manager-form" method="post">' );
	wp_nonce_field( 'amm_admin', 'amm_nonce' );
	printf( '<input type="hidden" name="amm_type" value="term" />' );
	$auto_multi_meta_term_manager->display();
	printf( '</form>' );

}

printf( '</div>' );
