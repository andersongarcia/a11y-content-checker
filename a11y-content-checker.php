<?php
/**
 * Plugin Name:       A11y Content Checker
 * Plugin URI:        https://github.com/andersongarcia/a11y-content-checker
 * Description:       Checks the accessibility of content while it is written and shows authors what to fix, with rules mapped to WCAG 2.2 and ABNT NBR 17225.
 * Version:           0.1.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Anderson Canale Garcia
 * Author URI:        https://github.com/andersongarcia
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       a11y-content-checker
 * Domain Path:       /languages
 */

defined( 'ABSPATH' ) || exit;

define( 'A11YCC_VERSION', '0.1.0' );
define( 'A11YCC_AXE_VERSION', '4.10.2' );
define( 'A11YCC_DIR', plugin_dir_path( __FILE__ ) );
define( 'A11YCC_URL', plugin_dir_url( __FILE__ ) );
define( 'A11YCC_META', '_a11ycc_result' );

require_once A11YCC_DIR . 'includes/class-checker.php';
require_once A11YCC_DIR . 'includes/class-storage.php';
require_once A11YCC_DIR . 'includes/class-admin.php';
require_once A11YCC_DIR . 'includes/class-rest.php';
require_once A11YCC_DIR . 'includes/class-review.php';

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	require_once A11YCC_DIR . 'includes/class-cli.php';
	WP_CLI::add_command( 'a11ycc', 'A11yCC_CLI' );
}

add_action( 'init', function () {
	load_plugin_textdomain( 'a11y-content-checker', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
} );

add_action( 'plugins_loaded', function () {
	A11yCC_Storage::register();
	A11yCC_Admin::register();
	A11yCC_Rest::register();
	A11yCC_Review::register();
} );
