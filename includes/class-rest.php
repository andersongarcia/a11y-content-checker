<?php
/**
 * POST /a11y-content-checker/v1/check: checks unsaved content (block editor panel).
 */

defined( 'ABSPATH' ) || exit;

class A11yCC_Rest {

	public static function register() {
		add_action( 'rest_api_init', function () {
			register_rest_route( 'a11y-content-checker/v1', '/check', array(
				'methods'             => 'POST',
				'permission_callback' => function () {
					return current_user_can( 'edit_posts' );
				},
				'args'                => array(
					'content' => array( 'type' => 'string', 'required' => true ),
				),
				'callback'            => function ( WP_REST_Request $req ) {
					$html = do_blocks( $req->get_param( 'content' ) );
					return array( 'issues' => A11yCC_Checker::check( $html ) );
				},
			) );
		} );
	}
}
