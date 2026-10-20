<?php
/**
 * "Accessibility" column in post lists, block editor panel and
 * an issues report under Tools.
 */

defined( 'ABSPATH' ) || exit;

class A11yCC_Admin {

	public static function register() {
		foreach ( A11yCC_Storage::post_types() as $type ) {
			add_filter( "manage_{$type}_posts_columns", array( __CLASS__, 'columns' ) );
			add_action( "manage_{$type}_posts_custom_column", array( __CLASS__, 'column' ), 10, 2 );
		}
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'enqueue_block_editor_assets', array( __CLASS__, 'editor' ) );
	}

	public static function columns( $columns ) {
		$columns['a11ycc'] = __( 'Accessibility', 'a11y-content-checker' );
		return $columns;
	}

	public static function column( $column, $post_id ) {
		if ( 'a11ycc' === $column ) {
			echo esc_html( self::summary( $post_id ) );
		}
	}

	public static function summary( $post_id ) {
		$result = A11yCC_Storage::result( $post_id );
		if ( ! $result ) {
			return __( 'Not checked', 'a11y-content-checker' );
		}
		$c = A11yCC_Storage::count( $result );
		if ( 0 === $c['error'] + $c['warning'] ) {
			return __( 'No automatic issues', 'a11y-content-checker' );
		}
		return sprintf(
			/* translators: 1: number of errors, 2: number of warnings */
			__( '%1$d error(s), %2$d warning(s)', 'a11y-content-checker' ),
			$c['error'],
			$c['warning']
		);
	}

	public static function menu() {
		add_management_page(
			__( 'Content accessibility', 'a11y-content-checker' ),
			__( 'Accessibility', 'a11y-content-checker' ),
			'edit_others_posts',
			'a11y-content-checker',
			array( __CLASS__, 'page' )
		);
	}

	/**
	 * Lists published posts with issues, most recently modified first.
	 */
	public static function page() {
		$query = new WP_Query( array(
			'post_type'      => A11yCC_Storage::post_types(),
			'post_status'    => 'publish',
			'posts_per_page' => 200,
			'meta_key'       => A11YCC_META,
			'orderby'        => 'modified',
			'fields'         => 'ids',
		) );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Content accessibility', 'a11y-content-checker' ); ?></h1>
			<p><?php esc_html_e( 'Posts with issues found by the automatic check. It does not replace a human review.', 'a11y-content-checker' ); ?></p>
			<table class="widefat striped">
				<caption class="screen-reader-text"><?php esc_html_e( 'Posts with issues', 'a11y-content-checker' ); ?></caption>
				<thead><tr>
					<th scope="col"><?php esc_html_e( 'Post', 'a11y-content-checker' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Issues', 'a11y-content-checker' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Rules', 'a11y-content-checker' ); ?></th>
				</tr></thead>
				<tbody>
				<?php
				foreach ( $query->posts as $id ) :
					$result = A11yCC_Storage::result( $id );
					if ( ! $result || ! $result['issues'] ) {
						continue;
					}
					$rules = array_unique( wp_list_pluck( $result['issues'], 'rule' ) );
					?>
					<tr>
						<td><a href="<?php echo esc_url( get_edit_post_link( $id ) ); ?>"><?php echo esc_html( get_the_title( $id ) ); ?></a></td>
						<td><?php echo esc_html( self::summary( $id ) ); ?></td>
						<td><code><?php echo esc_html( implode( ', ', $rules ) ); ?></code></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php
	}

	public static function editor() {
		wp_enqueue_script(
			'a11ycc-editor',
			A11YCC_URL . 'assets/editor.js',
			array( 'wp-plugins', 'wp-edit-post', 'wp-element', 'wp-data', 'wp-api-fetch', 'wp-i18n' ),
			A11YCC_VERSION,
			true
		);
		wp_set_script_translations( 'a11ycc-editor', 'a11y-content-checker', A11YCC_DIR . 'languages' );
		wp_localize_script( 'a11ycc-editor', 'a11ycc', array(
			'checklist' => esc_url( apply_filters( 'a11ycc_checklist_url', '' ) ),
		) );
	}
}
