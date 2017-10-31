<?php

namespace War_Api\Security;

use War_Api\Security\War_JWT as War_JWT;

class User_Auth {

	private $user_id;
	private $auth_type;
	private $authed = null;

	public function __construct(){
		$this->get_user_id_by_jwt();
		if( empty( $this->user_id ) ) $this->get_user_id_by_nonce(); //Try the nonce then
		$this->auth_used( $this->authed );
	}


	public function get_user_id(){
		return ( property_exists( $this, 'user_id' ) && $this->user_id !== 0 ) ? $this->user_id : false;
	}

	public function get_auth_type(){
		return ( property_exists( $this, 'auth_type' ) ) ? $this->auth_type : NULL;
	}

	private function get_user_id_by_jwt(){
		if( isset( $this->user_id ) ) return;
		if( isset( $_SERVER[ 'HTTP_AUTHORIZATION' ] ) )
			$this->user_id = $this->key_check( $_SERVER[ 'HTTP_AUTHORIZATION' ] );
	}

	private function get_user_id_by_nonce(){
		if( isset( $this->user_id ) ) return;
		if( isset( $_SERVER[ 'HTTP_X_WP_NONCE' ] ) )
			$this->user_id = $this->nonce_check( $_SERVER[ 'HTTP_X_WP_NONCE' ] );
	}

	private function key_check( $key ){
		if(empty($key)) return false;
		$jwt = new War_JWT;
		$res = $jwt->jwt_key_decode( $key );
		if( is_wp_error( $res ) ){
			$this->authed = $res;
			return;
		}
		$this->auth_type = 'JWT';
		$this->authed = true;
		return $res;
	}

	private function nonce_check( $nonce ){
		if( empty( $nonce ) ) return false;

		$verify = wp_verify_nonce( $nonce, 'wp_rest' );

		if( ! $verify ){
			$this->authed = false;
			return;
		}

		$this->auth_type = 'COOKIE';
		$this->authed = true;
		return get_current_user_id();
	}

	private function auth_used(){
		add_filter( 'rest_authentication_errors', function( $authed ){
			return $this->authed;
		});
	}


}
