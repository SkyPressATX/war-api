<?php

namespace War_Api\Security;

use War_Api\Security\User_Auth as User_Auth;

class War_User {

	private $current_user;
	private $war_user;
	private $auth_by;

	public function get_user(){
		$this->get_authed_user();
		$this->set_war_user();
		return $this->war_user;
	}

	public function get_wp_user(){
		$this->get_authed_user(); //First try to auth the Rest API way

		// If no Rest API auth returns a user, try the cookie
		if( ! method_exists( $this->current_user, 'get') || $this->current_user->get( 'ID' ) == 0 ) $this->current_user = wp_get_current_user();
		// If we had to use the cookie, set the proper auth_by value
		if( ! isset( $this->auth_by ) && $this->current_user->get( 'ID' ) != 0 ) $this->auth_by = 'COOKIE';

		$this->set_war_user();
		return $this->war_user;
	}

	private function get_authed_user(){
		$user_auth = new User_Auth;
		$id = $user_auth->get_user_id();
		$this->set_wp_user( $id );
		$this->auth_by = $user_auth->get_auth_type();
	}

	private function set_wp_user( $user_id = 0 ){
		if( $user_id == 0 ){
			$this->current_user = (object)[];
			return;
		}
		wp_set_current_user( $user_id );
		$this->current_user = wp_get_current_user();
	}

	/**
	 * Set War User Obj
	 *
	 * This is needed instead of just using a standard WP_User Object.
	 * We Don't need everyting, just specific items from the WP_User Object.
	 **/
	private function set_war_user(){
		$this->war_user = (object)[
			'id' => $this->current_user->get( 'ID' ),
			'email' => $this->current_user->get( 'user_email' ),
			'roles' => $this->current_user->roles,
			'auth' => $this->auth_by
		];
		$this->war_user->caps = array_keys( array_filter( $this->current_user->allcaps ) );
	}
}
