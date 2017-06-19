<?php

namespace War_Api\Security;

use \Firebase\JWT\JWT;

class War_JWT {

	private $jwt_expire;
	private $user_id;


	public function __construct( $jwt_expire = false, $user_id = 0 ){
		$this->jwt_expire = $jwt_expire;
		$this->user_id = $user_id;
	}

	public function jwt_key_create(){

        $time = time();
        $token = array(
            'iss' => get_bloginfo('url'),
            'iat' => $time,
            'nbf' => $time,
            'data' => array(
                'user' => array(
                    'id' => $this->user_id
                )
            )
        );

		if( $this->jwt_expire ) $token[ 'exp' ] = $this->jwt_expire;

        return JWT::encode( $token, AUTH_KEY );
    }

	public function jwt_key_decode( $auth_header = false ){
        if($auth_header === false) return false;
        list($token) = sscanf($auth_header, 'Bearer %s');
        if(!$token) return false;

        $decoded_token = JWT::decode($token, AUTH_KEY, array('HS256'));
        return $this->jwt_validate((object)$decoded_token);
    }

	private function jwt_validate( $decoded_token ){

        if( $decoded_token->iss != get_bloginfo('url')) $fail = 'Wrong URL';
        if( ! isset($decoded_token->data->user->id) ) $fail = 'No User';

        if(isset($fail)) throw new \Exception( 'Invalid Token Params -- ' . $fail);
        return $decoded_token->data->user->id;
    }

}
