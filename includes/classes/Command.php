<?php
/**
 * CLI command of the plugin
 *
 * @package PagePreview
 */

namespace PagePreview;

use \WP_CLI_Command as WP_CLI_Command;
use \WP_CLI as WP_CLI;
use const PagePreview\Constants\PREVIEW_URL_META_KEY;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * CLI Commands for PagePreview
 */
class Command extends WP_CLI_Command {

	/**
	 * Bulk create page previews.
	 *
	 * ## OPTIONS
	 *
	 * [--post_type=<post_type>]
	 *    Comma-separated list of post types to generate previews for.
	 *
	 * [--per-page=<per_page_number>]
	 *    Number of posts to process per batch. Default is 100.
	 *
	 * [--only-missing]
	 *    Generate previews only for posts that do not already have one.
	 *
	 * [--rate-limit=<rate_limit>]
	 *    Specify the number of requests allowed per 10 minute window. Default is 100.
	 *
	 * [--network-wide=<number_of_sites>]
	 *    Process specified number of sites on a multisite network. 0 for all sites.
	 *
	 * ## EXAMPLES
	 *    wp page-preview create --post_type=post,page --only-missing --network-wide=5
	 *
	 * @param array $args       Positional CLI args.
	 * @param array $assoc_args Associative CLI args.
	 */
	public function create( $args, $assoc_args ) {
		$start_time = microtime( true ); // Start timer

		if ( isset( $assoc_args['network-wide'] ) && is_multisite() ) {
			if ( ! is_numeric( $assoc_args['network-wide'] ) ) {
				$assoc_args['network-wide'] = 0;
			}

			$num_sites = (int) $assoc_args['network-wide'];
			$sites     = get_sites( [ 'number' => $num_sites > 0 ? $num_sites : 0 ] ); // Get specified number of sites, 0 for all sites
			foreach ( $sites as $site ) {
				switch_to_blog( $site->blog_id ); // Switch to each site
				WP_CLI::log( "Processing site: {$site->blog_id}" );
				$this->process_site( $args, $assoc_args ); // Process each site
				WP_CLI::log( "Finished processing site: {$site->blog_id}" );
				restore_current_blog(); // Restore to the original site
			}
		} else {
			$this->process_site( $args, $assoc_args ); // Process single site
		}

		$end_time      = microtime( true ); // Stop timer
		$time_taken    = $end_time - $start_time;
		$time_readable = gmdate( 'H:i:s', $time_taken );
		WP_CLI::success( "All posts have been processed. Time taken: {$time_readable}." );
	}

	/**
	 * Process site
	 *
	 * @param array $args       Positional CLI args.
	 * @param array $assoc_args Associative CLI args.
	 */
	private function process_site( $args, $assoc_args ) {
		$paged      = 1;
		$rate_limit = isset( $assoc_args['rate-limit'] ) ? (int) $assoc_args['rate-limit'] : 100; // Default rate limit
		$sleep_time = ( 10 * 60 ) / $rate_limit; // Calculate sleep time based on rate limit
		$batch_size = isset( $assoc_args['per-page'] ) ? (int) $assoc_args['per-page'] : 100;
		$settings   = \PagePreview\Utils\get_settings();
		$post_types = $settings['post_types'];

		if ( isset( $assoc_args['post_type'] ) ) {
			$post_types = explode( ',', $assoc_args['post_type'] );
		}

		while ( true ) {
			$query_args = [
				'post_type'      => $post_types,
				'posts_per_page' => $batch_size,
				'paged'          => $paged,
				'no_found_rows'  => true,
			];

			if ( isset( $assoc_args['only-missing'] ) ) {
				$query_args['meta_query'] = [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					[
						'key'     => PREVIEW_URL_META_KEY,
						'compare' => 'NOT EXISTS',
					],
				];
			}

			$query = new \WP_Query( $query_args );
			if ( ! $query->have_posts() ) {
				break;
			}

			foreach ( $query->posts as $post ) {
				WP_CLI::log( "Create preview for post: {$post->ID}" );
				$result = PagePreviewer::generate_preview_image( $post->ID );
				if ( is_wp_error( $result ) ) {
					WP_CLI::error( $result->get_error_message(), false );
				} else {
					WP_CLI::success( "Preview created for post: {$post->ID}" );
					foreach ( $result as $preview_url ) {
						WP_CLI::log( sprintf( 'Preview URL: %s', $preview_url ) );
					}
				}
			}

			$paged ++; // Increment the page number for the next batch.
			sleep( $sleep_time ); // Adjusted sleep time based on rate limit.
		}
	}

}
