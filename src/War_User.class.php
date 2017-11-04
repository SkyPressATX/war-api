<?php

namespace War_Api\Security;

use War_Api\Security\User_Auth as User_Auth;

class War_User {

	private $current_user;
	private $war_user;
	private $auth_by;
	private $user_id;

	public function get_war_user(){
		if( ! is_user_logged_in() ) return [];
		$this->current_user = wp_get_current_user();
		$this->set_war_user();
		return $this->war_user;
	}

	public function get_user(){
		$this->get_authed_user(); //Get an authorized user either through JWT or Cookie
		$this->set_authed_user(); //Set current user for the rest of WordPress
		$this->set_war_user(); //Build a WAR User object (strip out private data)
		return (object)$this->war_user;
	}

	private function get_authed_user(){
		$user_auth = new User_Auth;
		$this->user_id = $user_auth->get_user_id();
		$this->auth_by = $user_auth->get_auth_type();
	}


	private function set_authed_user(){
		if( ! isset( $this->user_id ) ) return;

		wp_set_current_user( $this->user_id );
		$this->current_user = wp_get_current_user();
	}

	/**
	 * Set War User Obj
	 *
	 * This is needed instead of just using a standard WP_User Object.
	 * We Don't need everyting, just specific items from the WP_User Object.
	 **/
	private function set_war_user(){
		$this->war_user = [
			'auth'	=> $this->auth_by
		];

		if( empty( $this->current_user ) || ! is_user_logged_in() ) return $this->war_user;

		$this->war_user = array_merge( $this->war_user, [
			'id' => $this->current_user->get( 'ID' ),
			'email' => $this->current_user->get( 'user_email' ),
			'roles' => $this->current_user->roles
		]);
		$this->war_user['caps'] = array_keys( array_filter( $this->current_user->allcaps ) );
	}
}
