<?php
/**
 * Post Manager list table.
 *
 * @package Auto_Multi_Meta
 */

namespace Auto_Multi_Meta;

defined( 'ABSPATH' ) || die();

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Class Post_Manager
 *
 * WP_List_Table implementation for browsing posts/pages/custom post types
 * and their meta description status. Provides per-row generate/preview
 * buttons and a bulk "Generate Missing" action, both handled via AJAX.
 */
class Post_Manager extends \WP_List_Table {

	/**
	 * Meta handler instance.
	 *
	 * @var Meta_Handler
	 */
	private Meta_Handler $meta_handler;

	/**
	 * Excluded post types that should never appear in the manager.
	 *
	 * @var array<int, string>
	 */
	private array $excluded_post_types = [
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

	/**
	 * Constructor.
	 *
	 * @param Meta_Handler $meta_handler Meta handler dependency.
	 */
	public function __construct( Meta_Handler $meta_handler ) {
		parent::__construct(
			[
				'singular' => 'post',
				'plural'   => 'posts',
				'ajax'     => false,
			]
		);

		$this->meta_handler = $meta_handler;
	}

	/**
	 * Defines the columns for the list table.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, string>
	 */
	public function get_columns(): array {
		return [
			'cb'          => '<input type="checkbox" />',
			'post_title'  => __( 'Title', 'auto-multi-meta' ),
			'post_type'   => __( 'Post Type', 'auto-multi-meta' ),
			'description' => __( 'Meta Description', 'auto-multi-meta' ),
			'status'      => __( 'Status', 'auto-multi-meta' ),
			'actions'     => __( 'Actions', 'auto-multi-meta' ),
		];
	}

	/**
	 * Returns sortable columns.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, array<int, mixed>>
	 */
	protected function get_sortable_columns(): array {
		return [
			'post_title' => [ 'title', true ],
		];
	}

	/**
	 * Returns available bulk actions.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, string>
	 */
	protected function get_bulk_actions(): array {
		return [
			'generate_missing' => __( 'Generate Missing Descriptions', 'auto-multi-meta' ),
		];
	}

	/**
	 * Returns the primary column name.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	protected function get_primary_column_name(): string {
		return 'post_title';
	}

	/**
	 * Renders the checkbox column.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $item Row item data.
	 * @return string
	 */
	protected function column_cb( $item ): string {
		return sprintf(
			'<input type="checkbox" name="item_ids[]" value="%d" />',
			esc_attr( (string) $item['post_id'] )
		);
	}

	/**
	 * Renders the Post Title column with an edit link.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $item Row item data.
	 * @return string
	 */
	protected function column_post_title( $item ): string {
		$edit_link = get_edit_post_link( (int) $item['post_id'] );
		$output    = '';

		if ( $edit_link ) {
			$output = sprintf(
				'<a href="%s"><strong>%s</strong></a>',
				esc_url( $edit_link ),
				esc_html( (string) $item['post_title'] )
			);
		} else {
			$output = sprintf( '<strong>%s</strong>', esc_html( (string) $item['post_title'] ) );
		}

		return $output;
	}

	/**
	 * Renders the Post Type column.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $item Row item data.
	 * @return string
	 */
	protected function column_post_type( $item ): string {
		return esc_html( (string) $item['post_type_label'] );
	}

	/**
	 * Renders the Meta Description column showing a truncated preview.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $item Row item data.
	 * @return string
	 */
	protected function column_description( $item ): string {
		$desc   = (string) $item['current_description'];
		$output = '';

		if ( '' === $desc ) {
			$output = sprintf(
				'<em class="amm-no-desc">%s</em>',
				esc_html__( '(none)', 'auto-multi-meta' )
			);
		} else {
			$preview = mb_strlen( $desc ) > 80
				? mb_substr( $desc, 0, 80 ) . '…'
				: $desc;

			$output = sprintf(
				'<span class="amm-desc-text" title="%s">%s</span> <span class="amm-desc-chars">(%d chars)</span>',
				esc_attr( $desc ),
				esc_html( $preview ),
				mb_strlen( $desc )
			);
		}

		return $output;
	}

	/**
	 * Renders the Status column with a traffic-light indicator.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $item Row item data.
	 * @return string
	 */
	protected function column_status( $item ): string {
		$desc   = (string) $item['current_description'];
		$output = '';

		if ( '' === $desc ) {
			$output = sprintf(
				'<span class="amm-status amm-status-missing" title="%s">&#10060;</span>',
				esc_attr__( 'No meta description set', 'auto-multi-meta' )
			);
		} else {
			$len = mb_strlen( $desc );

			if ( $len >= AMM_META_DESC_TARGET_MIN && $len <= AMM_META_DESC_TARGET_MAX ) {
				$output = sprintf(
					'<span class="amm-status amm-status-good" title="%s">&#9989;</span>',
					esc_attr__( 'Good length (120–160 chars)', 'auto-multi-meta' )
				);
			} else {
				$output = sprintf(
					'<span class="amm-status amm-status-warn" title="%s">&#9888;&#65039;</span>',
					esc_attr__( 'Description exists but outside optimal length (120–160 chars)', 'auto-multi-meta' )
				);
			}
		}

		return $output;
	}

	/**
	 * Renders the Actions column with Generate and Preview buttons.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $item Row item data.
	 * @return string
	 */
	protected function column_actions( $item ): string {
		$has_desc = '' !== (string) $item['current_description'];

		$gen_label = $has_desc
			? __( 'Regenerate', 'auto-multi-meta' )
			: __( 'Generate', 'auto-multi-meta' );

		$generate = sprintf(
			'<button type="button" class="button button-small amm-generate-btn" data-type="post" data-id="%d">%s</button>',
			esc_attr( (string) $item['post_id'] ),
			esc_html( $gen_label )
		);

		$preview = sprintf(
			'<button type="button" class="button button-small amm-preview-btn" data-type="post" data-id="%d">%s</button>',
			esc_attr( (string) $item['post_id'] ),
			esc_html__( 'Preview', 'auto-multi-meta' )
		);

		$view_url = get_permalink( (int) $item['post_id'] );
		$view     = '';

		if ( $view_url ) {
			$view = sprintf(
				' <a href="%s" class="button button-small" target="_blank" rel="noopener noreferrer">%s</a>',
				esc_url( $view_url ),
				esc_html__( 'View', 'auto-multi-meta' )
			);
		}

		return $generate . ' ' . $preview . $view;
	}

	/**
	 * Default column renderer for any column without a dedicated method.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $item        Row item data.
	 * @param string               $column_name Column name.
	 * @return string
	 */
	protected function column_default( $item, $column_name ): string {
		return isset( $item[ $column_name ] ) ? esc_html( (string) $item[ $column_name ] ) : '';
	}

	/**
	 * Renders each table row, adding data attributes for JS row updates.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $item Row item data.
	 * @return void
	 */
	public function single_row( $item ): void {
		printf(
			'<tr data-type="post" data-id="%d">',
			esc_attr( (string) $item['post_id'] )
		);
		$this->single_row_columns( $item );
		echo '</tr>';
	}

	/**
	 * Outputs the "no items" message.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function no_items(): void {
		$enabled = (array) get_option( AMM_OPT_ENABLED_POST_TYPES, [] );

		if ( empty( $enabled ) ) {
			esc_html_e( 'No post types are enabled. Enable post types on the Settings page.', 'auto-multi-meta' );
		} else {
			esc_html_e( 'No posts found in the enabled post types.', 'auto-multi-meta' );
		}
	}

	/**
	 * Populates $this->items with post rows, applying pagination.
	 *
	 * Reads enabled post types from plugin settings. Supports an optional
	 * ?amm_post_type= GET filter to show a single post type.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function prepare_items(): void {
		$enabled_post_types = array_diff(
			(array) get_option( AMM_OPT_ENABLED_POST_TYPES, [] ),
			$this->excluded_post_types
		);

		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- read-only filter, no state change.
		$filter_post_type = isset( $_GET['amm_post_type'] )
			? sanitize_key( wp_unslash( $_GET['amm_post_type'] ) )
			: '';
		// phpcs:enable

		if ( '' !== $filter_post_type && in_array( $filter_post_type, $enabled_post_types, true ) ) {
			$post_types_to_query = [ $filter_post_type ];
		} else {
			$post_types_to_query = array_values( $enabled_post_types );
		}

		$all_items = [];

		foreach ( $post_types_to_query as $post_type ) {
			$pt_object = get_post_type_object( $post_type );

			if ( null === $pt_object ) {
				continue;
			}

			$posts = get_posts(
				[
					'post_type'      => $post_type,
					'post_status'    => 'publish',
					// phpcs:ignore WordPress.WP.PostsPerPage.posts_per_page_posts_per_page -- Intentional: manager page needs full list for bulk actions.
					'posts_per_page' => 200,
					'orderby'        => 'title',
					'order'          => 'ASC',
				]
			);

			foreach ( $posts as $post ) {
				$current_desc = $this->meta_handler->get_post_meta_description( $post->ID );
				$all_items[]  = [
					'post_id'             => $post->ID,
					'post_title'          => '' !== $post->post_title ? $post->post_title : __( '(no title)', 'auto-multi-meta' ),
					'post_type'           => $post_type,
					'post_type_label'     => $pt_object->label,
					'current_description' => $current_desc,
				];
			}
		}

		$this->_column_headers = [
			$this->get_columns(),
			[],
			$this->get_sortable_columns(),
			$this->get_primary_column_name(),
		];

		$per_page     = 25;
		$current_page = $this->get_pagenum();
		$total_items  = count( $all_items );

		$this->set_pagination_args(
			[
				'total_items' => $total_items,
				'per_page'    => $per_page,
			]
		);

		$offset      = ( $current_page - 1 ) * $per_page;
		$this->items = array_slice( $all_items, $offset, $per_page );
	}
}
