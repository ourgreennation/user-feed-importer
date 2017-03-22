<?php
/**
 * User Feed Manager
 *
 * @package  User_Feed_Importer
 */

namespace Lift\Plugins\User_Feed_Importer;

class User_Feed_Manager {

	public $user;

	public function __construct( \WP_User $user ) {
		$this->user = $user;
	}
}
