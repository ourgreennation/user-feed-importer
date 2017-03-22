<?php
/**
 * Plugin Name:     User RSS Feed Importer
 * Plugin URI:      https://liftux.com
 * Description:     Imports Articles as WordPress Posts from User defined RSS feed.
 * Author:          Christian Chung <christian@liftux.com>
 * Author URI:      https://liftux.com
 * Text Domain:     contributor-feed-importer
 * Domain Path:     /languages
 * Version:         0.1.0
 *
 * @package         User_Feed_Importer
 */

namespace Lift\Plugins\User_Feed_Importer;

// Require the importer.
require_once( plugin_dir_path( __FILE__ ) . 'src/class-user-feed-importer.php' );
require_once( plugin_dir_path( __FILE__ ) . 'src/class-user-feed-item-importer.php' );

// Require the scheduler.
require_once( plugin_dir_path( __FILE__ ) . 'src/class-user-feed-import-scheduler.php' );

// Require misc functions.
require_once( plugin_dir_path( __FILE__ ) . 'src/user-feed-functions.php' );

// Require the options page if we're in the admin.
if ( is_admin() ) {
	require_once( plugin_dir_path( __FILE__ ) . 'src/user-feed-options.php' );
}

/**
 * Activate
 *
 * Schedules the importer cron job on plugin activation
 *
 * @return void
 */
function activate() {
	$scheduler = new User_Feed_Import_Scheduler;
	// The first run will start run in 20 minutes.
	$first_run = time() + ( 20 * MINUTE_IN_SECONDS );

	// Clear Scheduler of any previous cron events to be sure.
	$scheduler->clear();

	// Schedule the first run.
	$scheduler->setup()->schedule_next( $first_run );
}
register_activation_hook( __FILE__, __NAMESPACE__ . '\\activate' );

/**
 * Deactivate
 *
 * Removes the cron jobs spawned by this plugin on deactivation
 *
 * @return void
 */
function deactivate() {
	$scheduler = new CSL_Feed_Import_Scheduler;
	$scheduler->clear();
}
register_deactivation_hook( __FILE__, __NAMESPACE__ . '\\deactivate' );

/**
 * Run
 *
 * Kicks of the parts of the plugin that run always. All this does is add the actions
 * and filters for cron, and options.
 *
 * @return void
 */
function run() {
	$scheduler = new User_Feed_Import_Scheduler;
	$scheduler->setup();
}
add_action( 'plugins_loaded', __NAMESPACE__ . '\\run' );
