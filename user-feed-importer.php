<?php
/**
 * Plugin Name:     User RSS Feed Importer
 * Plugin URI:      https://liftux.com
 * Description:     Imports Articles as WordPress Posts from User defined RSS feed.
 * Author:          Christian Chung <christian@liftux.com>
 * Author URI:      https://liftux.com
 * Text Domain:     user-feed-importer
 * Domain Path:     /languages
 * Version:         0.1.0
 *
 * @package         User_Feed_Importer
 */

namespace Lift\Plugins\User_Feed_Importer;

// Require the user feed manager.
require_once( plugin_dir_path( __FILE__ ) . 'src/class-user-feed-manager.php' );

// Require the importer.
require_once( plugin_dir_path( __FILE__ ) . 'src/class-user-feed-importer.php' );
require_once( plugin_dir_path( __FILE__ ) . 'src/class-user-feed-item-importer.php' );

// Require the scheduler.
require_once( plugin_dir_path( __FILE__ ) . 'src/class-user-feed-import-scheduler.php' );

// Require misc functions.
require_once( plugin_dir_path( __FILE__ ) . 'src/user-feed-functions.php' );

// Require the options page if we're in the admin.
require_once( plugin_dir_path( __FILE__ ) . 'src/class-user-feed-options.php' );

/**
 * User Manager
 *
 * @return void
 */
function user_feed_manager() {
	if ( ! is_user_logged_in() ) {
		return;
	}
	$user_feed_manager = User_Feed_Manager::factory( wp_get_current_user() )->setup();
}
add_action( 'init', __NAMESPACE__ . '\\user_feed_manager' );

/**
 * Run
 *
 * Kicks of the parts of the plugin that run always. All this does is add the actions
 * and filters for cron, and options.
 *
 * @return void
 */
function run() {
	User_Feed_Import_Scheduler::factory()->setup();
}
add_action( 'plugins_loaded', __NAMESPACE__ . '\\run' );

/**
 * Admin
 *
 * Sets up the Options page.
 *
 * @return void
 */
function admin() {
	if ( is_admin() ) {
		User_Feed_Options::factory()->setup();
	}
}
add_action( 'init', __NAMESPACE__ . '\\admin' );
