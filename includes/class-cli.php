<?php
/**
 * Batch checks from the command line.
 *
 *     wp a11ycc check
 *     wp a11ycc check --post_type=page --format=csv > pages.csv
 *
 * Across a whole multisite network:
 *
 *     wp site list --field=url | xargs -I{} wp --url={} a11ycc check --format=csv
 */

defined( 'ABSPATH' ) || exit;

class A11yCC_CLI {

	/**
	 * Checks all published posts of the site and stores the results.
	 *
	 * ## OPTIONS
	 *
	 * [--post_type=<post_type>]
	 * : Post type. Default: every checked post type.
	 *
	 * [--format=<format>]
	 * : table, csv or json.
	 * ---
	 * default: table
	 * ---
	 */
	public function check( $args, $assoc ) {
		$types = isset( $assoc['post_type'] ) ? array( $assoc['post_type'] ) : A11yCC_Storage::post_types();
		$ids   = get_posts( array(
			'post_type'      => $types,
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
		) );

		$rows = array();
		// A progress bar would be mixed into CSV or JSON output.
		$bar = 'table' === $assoc['format']
			? \WP_CLI\Utils\make_progress_bar( 'Checking', count( $ids ) )
			: null;
		foreach ( $ids as $id ) {
			$result = A11yCC_Storage::check_post( $id );
			if ( $bar ) {
				$bar->tick();
			}
			if ( ! $result ) {
				continue;
			}
			foreach ( $result['issues'] as $issue ) {
				$rows[] = array(
					'site'  => home_url(),
					'id'    => $id,
					'title' => get_the_title( $id ),
					'url'   => get_permalink( $id ),
					'level' => $issue['level'],
					'rule'  => $issue['rule'],
					'wcag'  => $issue['wcag'],
				);
			}
		}
		if ( $bar ) {
			$bar->finish();
			WP_CLI::success( sprintf( '%d posts checked, %d issues.', count( $ids ), count( $rows ) ) );
		}

		if ( $rows ) {
			\WP_CLI\Utils\format_items( $assoc['format'], $rows, array_keys( $rows[0] ) );
		}
	}
}
