<?php
/**
 * Checks a post when it is saved and stores the result in post meta.
 */

defined( 'ABSPATH' ) || exit;

class A11yCC_Storage {

	public static function register() {
		add_action( 'save_post', array( __CLASS__, 'on_save' ), 20, 2 );
		// Elementor writes its data after save_post; check again when it is done.
		add_action( 'elementor/document/after_save', function ( $document ) {
			self::check_post( $document->get_main_id() );
		} );
	}

	public static function post_types() {
		return apply_filters( 'a11ycc_post_types', array( 'post', 'page' ) );
	}

	public static function on_save( $post_id, $post ) {
		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return;
		}
		if ( ! in_array( $post->post_type, self::post_types(), true ) ) {
			return;
		}
		self::check_post( $post_id );
	}

	/**
	 * @return array|null Stored result, or null if the post type is not checked.
	 */
	public static function check_post( $post_id ) {
		$post = get_post( $post_id );
		if ( ! $post || ! in_array( $post->post_type, self::post_types(), true ) ) {
			return null;
		}

		$result = array(
			'version' => A11YCC_VERSION,
			'time'    => time(),
			'issues'  => A11yCC_Checker::check( self::html( $post ) ),
		);
		update_post_meta( $post_id, A11YCC_META, $result );

		return $result;
	}

	public static function result( $post_id ) {
		$result = get_post_meta( $post_id, A11YCC_META, true );
		return is_array( $result ) ? $result : null;
	}

	public static function count( array $result ) {
		$count = array( 'error' => 0, 'warning' => 0 );
		foreach ( $result['issues'] as $issue ) {
			$count[ $issue['level'] ]++;
		}
		return $count;
	}

	/**
	 * Content HTML as visitors see it, without the theme header and footer.
	 */
	private static function html( WP_Post $post ) {
		if ( 'builder' === get_post_meta( $post->ID, '_elementor_edit_mode', true )
			&& class_exists( '\Elementor\Plugin' ) ) {
			try {
				return \Elementor\Plugin::$instance->frontend->get_builder_content_for_display( $post->ID );
			} catch ( \Throwable $e ) {
				// Fall back to regular content if Elementor cannot render outside the front end.
			}
		}

		$previous        = $GLOBALS['post'] ?? null;
		$GLOBALS['post'] = $post;
		setup_postdata( $post );
		$html = apply_filters( 'the_content', $post->post_content );
		$GLOBALS['post'] = $previous;
		wp_reset_postdata();

		return $html;
	}
}
