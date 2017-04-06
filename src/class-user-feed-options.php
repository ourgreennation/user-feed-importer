<?php
/**
 * User Feed Options
 *
 * Creates an options page where users can set the interval of imports and the authors to
 * import the posts as.
 *
 * @package  User_Feed_Importer
 */

namespace Lift\Plugins\User_Feed_Importer;

/**
 * Class: User_Feed_Options
 *
 * Sets up an options page in the WordPress backend, and handles the settings, the fields, and can
 * statically get the options set.
 *
 * @since  v0.1.0
 */
class User_Feed_Options {

	/**
	 * Option Name
	 *
	 * @var string The name of the option.
	 */
	public static $option_name = 'user_feed_settings';

	/**
	 * No Op Constructor
	 *
	 * @since  v0.1.0
	 * @return  void
	 */
	public function __construct() {}

	/**
	 * Factory
	 *
	 * @return Options New self instance.
	 */
	public static function factory() {
		return new self;
	}

	/**
	 * Get Option
	 *
	 * @param  string $option The specific setting to get.
	 * @return mixed          The specific setting, an array of all settings, or null.
	 */
	public static function get_option( $option = null ) {
		$opts = get_option( self::$option_name );
		if ( false === $opts ) {
			return null;
		}

		if ( $option ) {
			return ( isset( $opts[ $option ] ) ) ? $opts[ $option ] : null;
		}

		return $options;

	}

	/**
	 * Setup
	 *
	 * @return Options Instance of self.
	 */
	public function setup() {
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		add_action( 'admin_init', array( $this, 'process_admin_actions' ) );
		add_action( 'admin_init', array( $this, 'settings' ) );
		return $this;
	}

	/**
	 * Add Admin Menu Page
	 *
	 * @since  v0.1.0
	 * @return void
	 */
	public function admin_menu() {
		$return = \add_options_page(
			'User Feed Settings',
			'User Feed Settings',
			'manage_options',
			self::$option_name,
			array( $this, 'settings_page' )
		);
	}

	/**
	 * Settings
	 *
	 * Adds settings and settings fields
	 *
	 * @return void
	 */
	public function settings() {
		\add_settings_section(
			'user_feed_settings_section',
			__( 'Settings', 'wpls' ),
			array( $this, 'user_feed_settings_section_callback' ),
			self::$option_name
		);

		\add_settings_field(
			'interval',
			__( 'Sync Interval', 'user-feed-importer' ),
			array( $this, 'interval' ),
			self::$option_name,
			'user_feed_settings_section'
		);

		\add_settings_field(
			'post_status',
			__( 'Default Post Status', 'user-feed-importer' ),
			array( $this, 'post_status' ),
			self::$option_name,
			'user_feed_settings_section'
		);

		\add_settings_field(
			'intro_text',
			__( 'Introduction Text', 'user-feed-importer' ),
			array( $this, 'intro_text' ),
			self::$option_name,
			'user_feed_settings_section'
		);

		register_setting( self::$option_name, self::$option_name );
	}

	/**
	 * Settings section description
	 *
	 * @since  v0.1.0
	 * @return void
	 */
	public function user_feed_settings_section_callback() {
		echo __( 'Configure your User Feed Settings below.' );
	}

	/**
	 * Post Status
	 *
	 * Renders the post status field.
	 *
	 * @since  v0.1.0
	 * @return void
	 */
	public function post_status() {
		$settings = get_option( self::$option_name );
		$post_status = isset( $settings['post_status'] ) ? $settings['post_status'] : 'draft';
		$post_stati = array(
			'publish' => 'Published',
			'draft' => 'Draft',
			'pending' => 'Pending Approval',
		);
		$description = __( 'Set the default post status of imported posts.', 'user-feed-importer' );
		?>
		<select name="user_feed_settings['post_status']">
			<?php foreach ( $post_stati as $status => $label ) : ?>
				<option value="<?php echo esc_attr( $status ); ?>" <?php selected( $post_status, $status ); ?>>
					<?php echo esc_html( $label ); ?>
				</option>
			<?php endforeach; ?>
		</select>
		<p class="description" id="post_status-description">
			<?php echo esc_html( $description ); ?>
		</p>
		<?php
	}

	/**
	 * Intro Text
	 *
	 * Renders the intro text textarea.
	 *
	 * @since  v0.1.0
	 * @return void
	 */
	public function intro_text() {
		$settings = get_option( self::$option_name );
		$intro_text = isset( $settings['intro_text'] ) ? $settings['intro_text'] : 'Enter your RSS feed.';
		$description = __( 'Provide instructions and disclaimers for users entering their RSS feed.', 'user-feed-importer' );
		?>
		<textarea
			type="textarea"
			id="intro_text"
			name="user_feed_settings[intro_text]"
			rows="7"
			cols="50"
			/><?php echo esc_attr( $intro_text ); ?></textarea>
		<p class="description" id="intro_text-description">
			<?php echo esc_html( $description ); ?>
		</p>
		<?php
	}

	/**
	 * Interval
	 *
	 * Renders the interval field
	 *
	 * @since  v1.0.0
	 * @return void
	 */
	public function interval() {
		$settings = get_option( self::$option_name );
		$interval = isset( $settings['interval'] ) ? $settings['interval'] : 4;
		$description = __( 'Represents the interval (hrs) betweeen importing feeds.', 'user-feed-importer' );
		?>
		<input
			type="number"
			id="interval"
			name="user_feed_settings[interval]"
			value="<?php echo esc_attr( $interval ); ?>"
			/>
		<p class="description" id="interval-description">
			<?php echo esc_html( $description ); ?>
		</p>
		<?php
	}

	/**
	 * Process Admin Actions
	 *
	 * Process actions when you click on the UI buttons
	 *
	 * @return void
	 */
	public function process_admin_actions() {
		// Ensure it's our action.
		if ( ! isset( $_POST['action'] ) || 'user_feed_import' !== $_POST['action'] ) {
			return;
		}

		// Cap Check.
		if ( ! \current_user_can( 'manage_options' ) ) {
			wp_die( 'Please ask a site administrator to run this action.' );
			return;
		}

		// Nonce check.
		if ( ! \wp_verify_nonce( $_POST['_wpnonce'], 'user_feed_import' ) ) {
			wp_die( 'nonce' );
			return;
		}

		// User Id and Feed check.
		if ( ! isset( $_POST['user_feed_id'] ) || ! isset( $_POST['user_feed_url'] ) ) {
			return;
		}

		$scheduler = new User_Feed_Import_Scheduler( $_POST['user_feed_id'], $_POST['user_feed_url'] );
		$scheduler->clear()->schedule_next( time() );
	}

	/**
	 * Settings Page
	 *
	 * Renders all the markup for the User Feed Settings Page
	 *
	 * @since  v0.1.0
	 * @return void
	 */
	public function settings_page() {
		$users = get_users( array(
			'role_in' => User_Feed_Manager::get_roles(),
		) );
		?>
		<div class="wrap">

			<h2><?php esc_html_e( 'User Feed Importer Settings', 'user-feed-importer' ); ?></h2>

			<table class="wp-list-table widefat posts">
				<thead>
					<tr>
						<th><?php esc_html_e( 'User Name', 'user-feed-importer' ); ?></th>
						<th><?php esc_html_e( 'User ID', 'user-feed-importer' ); ?></th>
						<th><?php esc_html_e( 'User RSS Feed', 'user-feed-importer' ); ?></th>
						<th><?php esc_html_e( 'Manual Actions', 'user-feed-importer' ); ?></th>
						<th><?php esc_html_e( 'Next Automatic Sync', 'user-feed-importer' ); ?></th>
					</tr>
				</thead>

				<tbody>
				<?php foreach ( $users as $user ) : ?>

				<?php
				$feed_url = get_user_meta( $user->ID, 'user_rss_feed', true );
				if ( ! $feed_url ) {
					continue;
				}
				?>
					<tr>
						<td><?php echo esc_html( $user->data->display_name ); ?></td>
						<td><?php echo esc_html( $user->ID ); ?></td>
						<td><?php echo get_user_meta( $user->ID, 'user_rss_feed', true ); ?></td>
						<td>
							<form method="post" class="alignleft" style="margin-left:1em;">
								<input type="hidden" name="action" value="user_feed_import" />
								<input type="hidden" name="user_feed_id"
									value="<?php echo esc_attr( $user->ID ); ?>" />
								<input type="hidden" name="user_feed_url"
									value="<?php echo esc_attr( $feed_url ); ?>" />
								<?php wp_nonce_field( 'user_feed_import' ); ?>
								<input type="submit" class="button secondary"
									value="<?php esc_attr_e( 'Run Import', 'user-feed-options' ); ?>" />
							</form>
						</td>
						<td>
							<?php
								$next_schedule = wp_next_scheduled( 'user_feed_import', [ $user->ID ] );
							if ( false !== $next_schedule ) {
								$next_schedule = absint( $next_schedule );
								try {
									$next_schedule_time = new \DateTime( date( 'r', $next_schedule ) );
								} catch ( \Exception $e ) {
									esc_html_e( 'Error', 'user-feed-importer' );
								}

								$next_schedule_time->setTimezone( new \DateTimeZone( get_option( 'timezone_string' ) ) );

								echo esc_html( $next_schedule_time->format( 'm-d-Y g:ia T' ) );
							} else {
								esc_html_e( 'Nothing Scheduled', 'user-feed-importer' );
							}
							?>
						</td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>

			<form action="options.php" method="post">
				<?php settings_fields( self::$option_name ); ?>
				<?php do_settings_sections( self::$option_name ); ?>
				<?php submit_button(); ?>
			</form>

		</div>
		<?php
	}
}
