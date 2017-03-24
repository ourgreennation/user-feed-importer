<?php
/**
 * User Feed Import Scheduler
 *
 * Manages the cron events used to schedule imports.
 *
 * @package  User_Feed_Importer
 */

namespace Lift\Plugins\User_Feed_Importer;

/**
 * Class: User_Feed_Import_Scheduler
 *
 * Class containing the methods to spawn, despawn, and reschedule feed imports.
 *
 * @since  v0.1.0
 */
class User_Feed_Import_Scheduler {

	/**
	 * Cron Hook
	 *
	 * @var string  The cron hook to attach the import event to.
	 */
	protected $hook = 'user_feed_import';

	/**
	 * Recurrence
	 *
	 * @var string  Name to reference the interval.
	 */
	protected $recurrence = 'user_feed_import_recurrence';

	/**
	 * Interval
	 *
	 * @var integer  Cron interval to run the importer.
	 */
	public $interval = 14400;

	/**
	 * User ID
	 *
	 * @var int The Id of the User we are scheduling an import for.
	 */
	public $user_id;

	/**
	 * RSS Feed URL
	 *
	 * @var string The URL to the feed we are scheduling an import for.
	 */
	public $rss_feed_url;

	/**
	 * Constructor
	 *
	 * Sets up the Scheduler with the options from the settings page
	 *
	 * @param int    $user_id      The User ID we are scheduling rss imports for.
	 * @param string $rss_feed_url The URL to the RSS Feed.
	 * @return User_Feed_Import_Scheduler Instance of self.
	 */
	public function __construct( $user_id = null, $rss_feed_url = null ) {
		$this->user_id = absint( $user_id );
		$this->rss_feed_url = $rss_feed_url;

		// Extract interval from options.
		$opts = get_option( 'user_feed_import_options' );

		if ( is_array( $opts ) && isset( $opts['interval'] ) ) {
			$this->interval = absint( $opts['interval'] ) * HOUR_IN_SECONDS;
		}
		return $this;
	}

	/**
	 * Get Hook Arguments
	 *
	 * @return array An array of arguments to pass the cron hook.
	 */
	public function get_hook_args() {
		return array( $this->user_id );
	}

	/**
	 * Setup
	 *
	 * Attaches the necessary options to run the cron events and includes our interval.
	 * in the list of recurrence schedules. On admin, checks to see if a cron task is scheduled,
	 * and if it doesn't find one, schedules it.
	 *
	 * @return User_Feed_Import_Scheduler Instance of self.
	 */
	public function setup() {
		add_action( $this->hook, array( $this, 'import' ) );
		add_filter( 'cron_schedules', array( $this, 'add_recurrence' ), 10, 1 );
		add_action( 'update_option', array( $this, 'reset_schedule' ), 10, 3 );
		return $this;
	}

	/**
	 * Schedule Next
	 *
	 * Searches the cron schedule for the next appearance of the import, and adds one
	 * if it doesn't find it.
	 *
	 * @param  int $time Timestamp of when the initial event should run.
	 * @return bool|void       False if nothing was added, void if an event was scheduled.
	 */
	public function schedule_next( $time = null ) {
		$scheduled = false;
		if ( ! wp_next_scheduled( $this->hook, $this->get_hook_args() ) ) {
			if ( ! $time ) {
				$time = time() + MINUTE_IN_SECONDS;
			}
			$scheduled = wp_schedule_event( $time, $this->recurrence, $this->hook, $this->get_hook_args() );
		}
		return $scheduled;
	}

	/**
	 * Add Recurrence Schedules
	 *
	 * Adds our recurrence schedule to WordPress' list.
	 *
	 * @param   array $schedules A list of recurrence schedules.
	 * @return  array            A filtered list of recurrence schedules.
	 */
	public function add_recurrence( array $schedules ) {
		$schedules[ $this->recurrence ] = array(
			'interval' => $this->interval,
			'display' => 'Every ' . strval( ($this->interval / HOUR_IN_SECONDS ) ) . ' hours.',
			);

		return $schedules;
	}

	/**
	 * Import
	 *
	 * Imports items from User RSS Feed
	 *
	 * @param int $user_id The ID of the user.
	 * @return User_Feed_Importer An instance of User_Feed_Importer.
	 */
	public function import( $user_id ) {
		$user_id = absint( $user_id );
		$rss_feed_url = get_user_meta( $user_id, 'rss_feed_url', true );
		$importer = new User_Feed_Importer( $user_id, $rss_feed_url );
		$importer->import();

		return $importer;
	}

	/**
	 * Reset Schedule
	 *
	 * Resets the sync schedule, usually attached to the `update_option` hook.
	 *
	 * @param  string $option Option name.
	 * @param  mixed  $old    Old value.
	 * @param  mixed  $new    New Value.
	 * @return void
	 */
	public function reset_schedule( $option, $old, $new ) {
		if ( 'user_feed_import_options' !== $option ) {
			return;
		}

		if ( $old['interval'] !== $new['interval'] ) {
			$this->clear();
			$this->interval = absint( $new['interval'] ) * DAY_IN_SECONDS;
			$this->schedule_next();
		}
	}

	/**
	 * Clear
	 *
	 * Clears all the cron hooks
	 *
	 * @return User_Feed_Import_Scheduler Instance of self.
	 */
	public function clear() {
		wp_clear_scheduled_hook( $this->hook, $this->get_hook_args() );
		return $this;
	}
}
