<?php
/**
 * User Feed User Options
 *
 * Creates an options page where users setup their individual RSS feeds.
 *
 * @package  User_Feed_Importer
 */

namespace Lift\Plugins\User_Feed_Importer;

/**
 * Class: User Feed User Options
 *
 * Sets up an options page in the WordPress backend under the Posts tab where users can set their RSS Feed URL
 * and read instructions on how it all works.
 *
 * @since  v0.1.0
 */
class User_Feed_User_Options {

	/**
	 * Option Name
	 *
	 * @var string The name of the option.
	 */
	public static $option_name = 'user_feed_user_settings';

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
		$return = \add_submenu_page(
			'edit.php',
			'RSS Feed',
			'RSS Feed',
			'edit_posts',
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
			'user_feed_user_settings_section',
			__( 'Settings', 'wpls' ),
			array( $this, 'user_feed_user_settings_section_callback' ),
			self::$option_name
		);

		register_setting( self::$option_name, self::$option_name );
	}

	/**
	 * Settings section description
	 *
	 * @since  v0.1.0
	 * @return void
	 */
	public function user_feed_user_settings_section_callback() {
		echo __( 'Configure your User RSS Feed Settings below.' );
	}

	/**
	 * Live Page Field
	 *
	 * Renders the live page field
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
		<select name="user_feed_user_settings['post_status']">
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
	 * Render RSS Input Field
	 *
	 * Renders the input so users can add an RSS feed.
	 *
	 * @since  v2.0.0
	 * @return void
	 */
	public function render_rss_input_field() {
		$user_rss_feed = get_user_meta( get_current_user_id(), 'user_rss_feed', true );
		$user_rss_feed = $user_rss_feed ? $user_rss_feed : '';
		$description = __( 'Publicly accessible url to your feed.', 'user-feed-importer' );
		?>
		<input
			type="text"
			id="user_rss_feed"
			name="user_rss_feed"
			value="<?php echo esc_attr( $user_rss_feed ); ?>"
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
		// Handle our own error.
		if ( isset( $_GET['user_feed_status'] ) && 'error' === $_GET['user_feed_status'] ) {
			$this->handle_user_feed_error();
		}

		// Ensure it's our action.
		if ( ! isset( $_POST['action'] ) || 'update' !== $_POST['action'] ) {
			return;
		}

		if ( ! isset( $_POST['option_page'] ) || 'user_feed_user_settings' !== $_POST['option_page'] ) {
			return;
		}

		// Cap Check.
		if ( ! \current_user_can( 'edit_user', get_current_user_id() ) ) {
			wp_die( 'You do not have permissions to add an RSS Feed.' );
			return;
		}

		// Nonce check.
		if ( ! \wp_verify_nonce( $_POST['user_feed_nonce'], 'user_feed_user_options' ) ) {
			wp_nonce_ays( 'user_feed_user_options' );
			return;
		}

		// User Id and Feed check.
		if ( ! isset( $_POST['user_rss_feed'] ) ) {
			return;
		}

		// Validate the field.
		$feed = $this->validate_feed( $_POST['user_rss_feed'] );

		// Feed is not valid, inform the user.
		if ( false === $feed ) {
			$redirect = add_query_arg( array(
				'user_feed_status' => 'error',
			), $_POST['_wp_http_referer'] );
			wp_safe_redirect( $redirect );
			exit;
		}

		// Okay, the url is valid, let's update the user and schdule an import.
		$feed = sanitize_text_field( $feed );
		update_user_meta( get_current_user_id(), 'user_rss_feed', $feed );
		$scheduler = new User_Feed_Import_Scheduler( get_current_user_id(), $feed );
		$scheduler->clear()->schedule_next( time() );
	}

	/**
	 * Validate Feed
	 *
	 * @param string $feed The URL to the feed.
	 * @return bool|string Return the feed if valid, false otherwise.
	 */
	public function validate_feed( $feed ) {
		$feed = trim( strtolower( $feed ) );

		// If nothing has changed, we don't need any validation.
		if ( get_user_meta( get_current_user_id(), 'user_rss_feed', true ) === $feed || '' === $feed ) {
			return $feed;
		}

		$response = wp_remote_get( $feed );
		if ( 200 !== wp_remote_retrieve_response_code( $response ) || is_wp_error( $response ) ) {
			return false;
		}
		if ( strpos( wp_remote_retrieve_header( $response, 'content-type' ), 'application/rss+xml' ) < 0 ) {
			return false;
		}
		return $feed;
	}

	/**
	 * Handle User Feed Error
	 *
	 * @return void
	 */
	public function handle_user_feed_error() {
		add_action( 'admin_notices', function() {
			?>
			<div class="notice notice-error is-dismissible">
				<p>
					<?php echo esc_html_e( 'Sorry, we could not locate an RSS feed at that location. Please try again.' ); ?>
				</p>
			</div>
			<?php
		} );
	}

	/**
	 * Render Intro
	 *
	 * @return void
	 */
	public function render_intro() {
		$settings = get_option( 'user_feed_settings' );
		$intro_text = isset( $settings['intro_text'] ) ? $settings['intro_text'] : 'Enter your RSS feed.';
		echo wp_kses_post( wpautop( $intro_text ) );
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
		?>
		<div class="wrap">
			<form action="options.php" method="post">
				<?php settings_fields( self::$option_name ); ?>
				<?php wp_nonce_field( 'user_feed_user_options', 'user_feed_nonce' ); ?>
				<h2><?php esc_html_e( 'RSS Feed Settings', 'user-feed-importer' ); ?></h2>
				<?php $this->render_intro(); ?>
				<table class="form-table">
					<tbody>
						<tr>
							<th scope="row">URL to Your RSS Feed</th>
							<td>
								<?php $this->render_rss_input_field(); ?>
							</td>
						</tr>
					</tbody>
				</table>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}
}
