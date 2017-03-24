<?php
/**
 * User Feed Manager
 *
 * @package  User_Feed_Importer
 */

namespace Lift\Plugins\User_Feed_Importer;

/**
 * Class: User_Feed_Manager
 */
class User_Feed_Manager {

	/**
	 * User
	 *
	 * @var \WP_User The user we are managing the feed of.
	 */
	public $user;

	/**
	 * Authorized Roles
	 *
	 * @var array An array of roles authorized to import a feed.
	 */
	public $authorized_roles;

	/**
	 * Is Authorized
	 *
	 * @var boolean True if the user is authorized to import a feed, false otherwise.
	 */
	public $is_authorized = false;

	public $default_roles = array(
		'administrator',
		'editor',
		'author',
		'ogn_contributor',
	);

	/**
	 * Constructor
	 *
	 * @param \WP_User $user The user we are managing the feed of.
	 */
	public function __construct( \WP_User $user ) {
		$this->user = $user;

		/**
		 * Filter: {plugin_hook_slug}authorized_roles
		 *
		 * @param $default_roles Array of default roles.  Administrator, Editor, Author.
		 */
		$this->authorized_roles = apply_filters( hook_slug( 'authorized_roles' ), $this->default_roles );

		if ( ! empty( array_intersect( $this->user->roles, $this->authorized_roles ) ) ) {
			$this->is_authorized = true;
		}
		return $this;
	}

	/**
	 * Factory
	 *
	 * @param  \WP_User $user The user we are managing the feed of.
	 * @return User_Feed_Manager New instance of self.
	 */
	public static function factory( \WP_User $user ) {
		return new self( $user );
	}

	/**
	 * Setup
	 *
	 * Declares all hooks.
	 *
	 * @return User_Feed_Manager Self instance.
	 */
	public function setup() {
		if ( ! $this->is_authorized ) {
			return $this;
		}

		// Add RSS Field to Standard WordPress.
		add_filter( 'user_contactmethods', array( $this, 'add_rss_profile_field' ) );

		// Also support Buddypress.
		add_action( 'bp_after_profile_field_content', array( $this, 'add_bp_rss_profile_field' ), 2 );
		add_action( 'edit_user_profile_update', array( $this, 'save_rss_profile_field' ) );
		add_action( 'personal_options_update', array( $this, 'save_rss_profile_field' ) );
		add_action( 'xprofile_updated_profile', array( $this, 'save_rss_profile_field' ) );

		// When a user adds or updates a feed, schedule imports for the feed.
		add_action( 'update_user_meta', array( $this, 'handle_feed_meta_update' ), 10, 4 );
		return $this;
	}

	/**
	 * Add RSS Profile Fields
	 *
	 * Adds an RSS Feed URL Field to the standard WordPress profile edit screen.
	 *
	 * @param  array $fields An array of contact methods.
	 * @return array         An array of contact methods.
	 */
	public function add_rss_profile_field( $fields ) {
		$fields['user_rss_feed'] = __( 'RSS Feed URL', 'user-feed-importer' );
		return $fields;
	}

	/**
	 * Add BuddyPress Profile Fields
	 *
	 * Adds an RSS Feed URL Field to the public xprofile edit page of BuddyPress.
	 *
	 * @return void
	 */
	public function add_bp_rss_profile_field() {
		if ( ( 1 !== bp_get_the_profile_group_id()  && ! is_admin() ) ) {
			return;
		}
		$url = get_user_meta( bp_displayed_user_id(), 'user_rss_feed', true );
		?>
		<div class="bp-profile-field editfield field_type_textbox field_user_feed_importer" >
			<label for="user_rss_feed" class="label">
				<?php _e( 'RSS Feed URL', 'user-feed-importer' ) ?>
			</label>
			<input
			id="user_rss_feed"
			name="user_rss_feed"
			type="text"
			value="<?php echo esc_attr( $url ) ?>"
			aria-rqqequired="false">
		</div>
		<?php
	}

	/**
	 * Save RSS Profile Field
	 *
	 * @param  int $user_id The User ID of the user whose profile was just saved.
	 * @return void
	 */
	public function save_rss_profile_field( $user_id ) {
		if ( current_user_can( 'edit_user', $user_id ) ) {
			update_user_meta( $user_id, 'user_rss_feed', $_POST['user_rss_feed'] );
		}
	}

	/**
	 * Handle Feed Meta Update
	 *
	 * @param  int    $meta_id    ID of the metadata entry.
	 * @param  int    $object_id  ID of the object the metadata is attached to.
	 * @param  string $meta_key   The key of the metadata.
	 * @param  mixed  $meta_value The value of the metadata.
	 * @return User_Feed_Manager  Self instance.
	 */
	public function handle_feed_meta_update( $meta_id, $object_id, $meta_key, $meta_value ) {
		if ( 'user_rss_feed' !== $meta_key ) {
			return;
		}

		$scheduler = new User_Feed_Import_Scheduler( $object_id, $meta_value );
		$scheduler->clear()->schedule_next();
	}
}
