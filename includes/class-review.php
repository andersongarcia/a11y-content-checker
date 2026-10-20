<?php
/**
 * Review mode on the site itself: for users who can edit the page, a toolbar
 * item runs axe-core and shows the issues stored on save.
 *
 * Works with any editor (blocks, classic, Elementor) because it checks the
 * rendered page, including previews.
 */

defined( 'ABSPATH' ) || exit;

class A11yCC_Review {

	public static function register() {
		add_action( 'admin_bar_menu', array( __CLASS__, 'toolbar' ), 90 );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'scripts' ) );
	}

	private static function can_review() {
		return is_singular() && current_user_can( 'edit_post', get_queried_object_id() );
	}

	public static function toolbar( WP_Admin_Bar $bar ) {
		if ( ! self::can_review() ) {
			return;
		}
		$bar->add_node( array(
			'id'    => 'a11ycc',
			'title' => esc_html__( 'Check accessibility', 'a11y-content-checker' ),
			'href'  => '#a11ycc',
		) );
	}

	public static function scripts() {
		if ( ! self::can_review() ) {
			return;
		}
		// axe-core is bundled (MPL-2.0): nothing is loaded from third-party servers.
		wp_enqueue_script( 'axe-core', A11YCC_URL . 'vendor/axe.min.js', array(), A11YCC_AXE_VERSION, true );
		$axe_locale = self::axe_locale();
		if ( $axe_locale ) {
			wp_add_inline_script( 'axe-core', 'axe.configure({locale:' . $axe_locale . '});' );
		}

		wp_enqueue_script( 'a11ycc-review', A11YCC_URL . 'assets/review.js', array( 'axe-core', 'wp-i18n' ), A11YCC_VERSION, true );
		wp_set_script_translations( 'a11ycc-review', 'a11y-content-checker', A11YCC_DIR . 'languages' );
		wp_enqueue_style( 'a11ycc-review', A11YCC_URL . 'assets/review.css', array(), A11YCC_VERSION );

		$result = A11yCC_Storage::result( get_queried_object_id() );
		wp_localize_script( 'a11ycc-review', 'a11ycc', array(
			'issues'    => $result ? $result['issues'] : array(),
			'checklist' => esc_url( apply_filters( 'a11ycc_checklist_url', '' ) ),
			'scope'     => apply_filters( 'a11ycc_axe_scope', 'main' ),
		) );
	}

	/**
	 * axe-core translation for the current locale (pt_BR, then pt), if bundled.
	 */
	private static function axe_locale() {
		$locale = determine_locale();
		foreach ( array( $locale, substr( $locale, 0, 2 ) ) as $name ) {
			$file = A11YCC_DIR . 'vendor/axe-locales/' . $name . '.json';
			if ( is_readable( $file ) ) {
				return file_get_contents( $file );
			}
		}
		return null;
	}
}
