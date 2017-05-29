<?php

namespace War_Api\Security;

use War_Api\Security\War_JWT as War_JWT;

class User_Auth {

	private $user_id;
	private $auth_type;

	public function __construct(){
		$this->get_user_id_by_jwt();
		$this->get_user_id_by_nonce();
	}


	public function get_user_id(){
		return ( isset( $this->user_id ) && $this->user_id !== 0 ) ? $this->user_id : false;
	}

	public function get_auth_type(){
		return ( isset( $this->auth_type ) ) ? $this->auth_type : NULL;
	}

	private function get_user_id_by_jwt(){
		if( ! isset( $this->user_id ) && isset( $_SERVER[ 'HTTP_AUTHORIZATION' ] ) )
			$this->user_id = $this->key_check( $_SERVER[ 'HTTP_AUTHORIZATION' ] );
	}

	private function get_user_id_by_nonce(){
		if( ! isset( $this->user_id ) && isset( $_SERVER[ 'HTTP_X_WP_NONCE' ] ) )
			$this->user_id = $this->nonce_check( $_SERVER[ 'HTTP_X_WP_NONCE' ] );
	}

	private function key_check( $key ){
		if(empty($key)) return false;
		$jwt = new War_JWT;
		$res = $jwt->jwt_key_decode( $key );
		print_r( $res );
		if( is_wp_error( $res ) ) return $res;
		$this->auth_type = 'JWT';
	}

	private function nonce_check( $nonce ){
		if( empty( $nonce ) ) return false;
        if( ! wp_verify_nonce( $nonce, 'wp_rest' ) ) return false;
        if( wp_verify_nonce( $nonce, 'wp_rest' ) ){
			$this->auth_type = 'NONCE';
			return get_current_user_id();
		}
	}



}
