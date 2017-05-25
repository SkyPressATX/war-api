<?php

use \Firebase\JWT\JWT;

class war_jwt {

	public function jwt_key_create( $id = false ){
        if( ! $id ) {
            $cu = wp_get_current_user();
            $id = $cu->ID;
        }

        if( $id == 0 ) return new WP_Error(403, 'No Active User' );

        $time = time();
        $token = array(
            'iss' => get_bloginfo('url'),
            'iat' => $time,
            'nbf' => $time,
            'data' => array(
                'user' => array(
                    'id' => $id
                )
            )
        );

        $e = $time + (DAY_IN_SECONDS * 30);
        $exp = apply_filters( 'war_jwt_expire', $e );

        if( $exp !== FALSE ) $token[ 'exp' ] = $exp;

        return JWT::encode( $token, AUTH_KEY );
    }

	public function jwt_key_decode( $auth_header = false ){
        if($auth_header === false) return false;
        list($token) = sscanf($auth_header, 'Bearer %s');
        if(!$token) return false;

        try {
            $decoded_token = JWT::decode($token, AUTH_KEY, array('HS256'));
            return $this->jwt_validate((object)$decoded_token);
        } catch (Exception $e){
            return new WP_Error( 403, $e->getMessage() );
        }
    }

	private function jwt_validate( $decoded_token ){

        if( $decoded_token->iss != get_bloginfo('url')) $fail = 'Wrong URL';
        if( ! isset($decoded_token->data->user->id) ) $fail = 'No User';

        if(isset($fail)) return new WP_Error(403, 'Invalid Token Params -- ' . $fail);

        return $decoded_token->data->user->id;
    }

}
