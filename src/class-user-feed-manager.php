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
	 * Constructor
	 *
	 * @param \WP_User $user The user we are managing the feed of.
	 */
	public function __construct( \WP_User $user ) {
		$this->user = $user;
	}
}
