<?php
/**
 * Background batch processor.
 *
 * @package Auto_Multi_Meta
 */

namespace Auto_Multi_Meta;

defined( 'ABSPATH' ) || die();

/**
 * Class Batch_Processor
 *
 * Handles background bulk generation of meta descriptions using Action Scheduler
 * (provided by WooCommerce) with a WP-Cron fallback for sites without WooCommerce.
 *
 * Batch state is persisted in a transient (AMM_BATCH_TRANSIENT_KEY) with a 24-hour
 * TTL. Only one batch can run at a time; starting a new batch cancels any running one.
 *
 * Public API:
 *   start_batch( string $type, bool $force ) — queue a new batch
 *   get_progress()                           — return current batch summary
 *   cancel_batch()                           — stop a running batch
 */
class Batch_Processor {

	/**
	 * Starts a new batch generation job.
	 *
	 * Collects all taxonomy terms or post-type posts that are missing meta
	 * descriptions (or all items when $force is true), saves them to a transient,
	 * and schedules the first processing action via Action Scheduler or WP-Cron.
	 *
	 * Any already-running batch is cancelled before the new one begins.
	 *
	 * @since 1.0.0
	 *
	 * @param string $type  Item type: 'term', 'post', or 'all'.
	 * @param bool   $force When true, include items that already have a description.
	 *
	 * @return true|\WP_Error True on success, WP_Error when no eligible items are found.
	 */
	public function start_batch( string $type, bool $force = false ): true|\WP_Error {
		$items  = $this->collect_items( $type, $force );
		$result = null;

		if ( empty( $items ) ) {
			$result = new \WP_Error(
				'amm_batch_empty',
				__( 'No items found that need meta descriptions.', 'auto-multi-meta' )
			);
		}

		if ( is_null( $result ) ) {
			$this->cancel_batch();

			$now   = new \DateTime( 'now', wp_timezone() );
			$batch = [
				'status'       => 'running',
				'type'         => $type,
				'total'        => count( $items ),
				'completed'    => 0,
				'failed'       => 0,
				'items'        => $items,
				'started_at'   => $now->format( 'Y-m-d H:i:s T' ),
				'completed_at' => null,
			];

			set_transient( AMM_BATCH_TRANSIENT_KEY, $batch, AMM_BATCH_TRANSIENT_TTL );

			$this->schedule_next( $batch, 0 );

			$result = true;
		}

		return $result;
	}

	/**
	 * Returns the current batch progress summary.
	 *
	 * Returns an array with 'status' => 'idle' when no active batch transient exists.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, mixed> Progress summary with keys: status, type, total,
	 *                              completed, failed, started_at, completed_at.
	 */
	public function get_progress(): array {
		$batch    = get_transient( AMM_BATCH_TRANSIENT_KEY );
		$progress = [ 'status' => 'idle' ];

		if ( is_array( $batch ) ) {
			$progress = [
				'status'       => $batch['status'],
				'type'         => $batch['type'],
				'total'        => $batch['total'],
				'completed'    => $batch['completed'],
				'failed'       => $batch['failed'],
				'started_at'   => $batch['started_at'],
				'completed_at' => $batch['completed_at'],
			];
		}

		return $progress;
	}

	/**
	 * Cancels a running batch job.
	 *
	 * Sets the transient status to 'cancelled' and unschedules any pending
	 * Action Scheduler actions for this plugin group. No-op when no batch is running.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function cancel_batch(): void {
		$batch = get_transient( AMM_BATCH_TRANSIENT_KEY );

		if ( is_array( $batch ) && 'running' === $batch['status'] ) {
			$batch['status'] = 'cancelled';
			set_transient( AMM_BATCH_TRANSIENT_KEY, $batch, AMM_BATCH_TRANSIENT_TTL );

			if ( function_exists( 'as_unschedule_all_actions' ) ) {
				as_unschedule_all_actions( AMM_BATCH_ACTION_TERM, [], AMM_BATCH_AS_GROUP );
				as_unschedule_all_actions( AMM_BATCH_ACTION_POST, [], AMM_BATCH_AS_GROUP );
			}
		}
	}

	/**
	 * Action Scheduler / WP-Cron callback: processes a single taxonomy term.
	 *
	 * Reads the batch transient, generates a meta description for the given term,
	 * updates the transient with the result, and schedules the next item.
	 * Exits silently if the batch has been cancelled or the transient is missing.
	 *
	 * @since 1.0.0
	 *
	 * @param int    $term_id  Term ID.
	 * @param string $taxonomy Taxonomy slug.
	 * @param int    $index    Item index within the batch items array.
	 *
	 * @return void
	 */
	public function process_term_item( int $term_id, string $taxonomy, int $index ): void {
		$batch = get_transient( AMM_BATCH_TRANSIENT_KEY );

		if ( ! is_array( $batch ) || 'running' !== $batch['status'] ) {
			return;
		}

		$result = auto_multi_meta_get_plugin()->get_generator()->generate_for_term( $term_id, $taxonomy );
		$status = ( 'error' === $result['status'] ) ? 'error' : $result['status'];

		$this->finish_item( $batch, $index, $status );
	}

	/**
	 * Action Scheduler / WP-Cron callback: processes a single post.
	 *
	 * Reads the batch transient, generates a meta description for the given post,
	 * updates the transient with the result, and schedules the next item.
	 * Exits silently if the batch has been cancelled or the transient is missing.
	 *
	 * @since 1.0.0
	 *
	 * @param int $post_id Post ID.
	 * @param int $index   Item index within the batch items array.
	 *
	 * @return void
	 */
	public function process_post_item( int $post_id, int $index ): void {
		$batch = get_transient( AMM_BATCH_TRANSIENT_KEY );

		if ( ! is_array( $batch ) || 'running' !== $batch['status'] ) {
			return;
		}

		$result = auto_multi_meta_get_plugin()->get_generator()->generate_for_post( $post_id );
		$status = ( 'error' === $result['status'] ) ? 'error' : $result['status'];

		$this->finish_item( $batch, $index, $status );
	}

	/**
	 * Collects items that require meta description generation.
	 *
	 * For 'term' type: returns all terms in enabled taxonomies that lack a description.
	 * For 'post' type: returns all published posts in enabled post types that lack a description.
	 * For 'all': combines both term and post lists.
	 * When $force is true, all items are included regardless of existing descriptions.
	 *
	 * @since 1.0.0
	 *
	 * @param string $type  'term', 'post', or 'all'.
	 * @param bool   $force When true, include items that already have a description.
	 *
	 * @return array<int, array<string, mixed>> List of items with keys: type, id, taxonomy, status.
	 */
	private function collect_items( string $type, bool $force ): array {
		$items        = [];
		$meta_handler = auto_multi_meta_get_plugin()->get_meta_handler();

		if ( 'term' === $type || 'all' === $type ) {
			$enabled_taxonomies = (array) get_option( AMM_OPT_ENABLED_TAXONOMIES, [] );

			foreach ( $enabled_taxonomies as $taxonomy ) {
				$terms = get_terms(
					[
						'taxonomy'   => (string) $taxonomy,
						'hide_empty' => false,
						'fields'     => 'ids',
					]
				);

				if ( is_wp_error( $terms ) ) {
					continue;
				}

				foreach ( (array) $terms as $term_id ) {
					$tid = (int) $term_id;

					if ( $force || '' === $meta_handler->get_term_meta( $tid, (string) $taxonomy ) ) {
						$items[] = [
							'type'     => 'term',
							'id'       => $tid,
							'taxonomy' => (string) $taxonomy,
							'status'   => 'pending',
						];
					}
				}
			}
		}

		if ( 'post' === $type || 'all' === $type ) {
			$enabled_post_types = (array) get_option( AMM_OPT_ENABLED_POST_TYPES, [] );

			foreach ( $enabled_post_types as $post_type ) {
				$query = new \WP_Query(
					[
						'post_type'           => (string) $post_type,
						'posts_per_page'      => -1,
						'post_status'         => 'publish',
						'fields'              => 'ids',
						'no_found_rows'       => true,
						'ignore_sticky_posts' => true,
					]
				);

				foreach ( (array) $query->posts as $post_id ) {
					$pid = (int) $post_id;

					if ( $force || '' === $meta_handler->get_post_meta_description( $pid ) ) {
						$items[] = [
							'type'     => 'post',
							'id'       => $pid,
							'taxonomy' => '',
							'status'   => 'pending',
						];
					}
				}
			}
		}

		return $items;
	}

	/**
	 * Schedules the next batch item via Action Scheduler or WP-Cron.
	 *
	 * Action Scheduler is used when available (WooCommerce is active). WP-Cron is
	 * the fallback for sites without WooCommerce. Both honour the AMM_OPT_BATCH_DELAY
	 * setting as the inter-item delay in seconds.
	 *
	 * No-op when $index is out of bounds.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $batch Current batch state array.
	 * @param int                  $index Index of the item to schedule next.
	 *
	 * @return void
	 */
	private function schedule_next( array $batch, int $index ): void {
		if ( $index >= $batch['total'] ) {
			return;
		}

		$item      = $batch['items'][ $index ];
		$delay     = (int) get_option( AMM_OPT_BATCH_DELAY, AMM_DEFAULT_BATCH_DELAY );
		$timestamp = time() + max( 0, $delay );

		if ( 'term' === $item['type'] ) {
			$hook = AMM_BATCH_ACTION_TERM;
			$args = [ $item['id'], $item['taxonomy'], $index ];
		} else {
			$hook = AMM_BATCH_ACTION_POST;
			$args = [ $item['id'], $index ];
		}

		if ( function_exists( 'as_schedule_single_action' ) ) {
			as_schedule_single_action( $timestamp, $hook, $args, AMM_BATCH_AS_GROUP );
		} else {
			wp_schedule_single_event( $timestamp, $hook, $args );
		}
	}

	/**
	 * Updates batch state after an item is processed, then schedules the next one.
	 *
	 * When the last item is processed the batch is marked 'completed' and a notice
	 * option (AMM_OPT_BATCH_NOTICE) is written for display on the next admin page load.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $batch  Current batch state (read from transient).
	 * @param int                  $index  Index of the just-processed item.
	 * @param string               $status Item result: 'generated', 'skipped', or 'error'.
	 *
	 * @return void
	 */
	private function finish_item( array $batch, int $index, string $status ): void {
		if ( isset( $batch['items'][ $index ] ) ) {
			$batch['items'][ $index ]['status'] = $status;
		}

		++$batch['completed'];

		if ( 'error' === $status ) {
			++$batch['failed'];
		}

		$next = $index + 1;

		if ( $next >= $batch['total'] ) {
			$now                   = new \DateTime( 'now', wp_timezone() );
			$batch['status']       = 'completed';
			$batch['completed_at'] = $now->format( 'Y-m-d H:i:s T' );

			update_option(
				AMM_OPT_BATCH_NOTICE,
				[
					'generated' => $batch['completed'] - $batch['failed'],
					'failed'    => $batch['failed'],
					'total'     => $batch['total'],
					'type'      => $batch['type'],
					'time'      => $batch['completed_at'],
				],
				false
			);
		}

		set_transient( AMM_BATCH_TRANSIENT_KEY, $batch, AMM_BATCH_TRANSIENT_TTL );

		if ( 'running' === $batch['status'] ) {
			$this->schedule_next( $batch, $next );
		}
	}
}
