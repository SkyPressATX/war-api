<?php

require_once __DIR__ . "/../vendor/autoload.php";
use \Firebase\JWT\JWT;

class war_user_actions {

    public function war_login( $data ){
        $auth = wp_authenticate($data->params->username, $data->params->password);

        if( is_wp_error( $auth ) ) return $auth;

        wp_set_auth_cookie( $auth->ID );
        wp_set_current_user( $auth->ID );

        // return $auth;
        $jwt = $this->war_jwt_create();
        return ['jwt' => $jwt ];
    }

    public function war_logout( $request ){
        wp_logout();
        return true;
    }

    public function war_signup( $request ){
        $data = $this->war_get_request_args( $request );

        if( email_exists( $data->email ) ) return $this->response_prepare( new WP_Error( 409, 'Email Already Taken' ) );

        // Generate the password and create the user
        // $password = wp_generate_password( 12, false );
        $user_id = wp_create_user( $data->email, $data->password, $data->email );

        // Set the nickname
        wp_update_user(
            array(
                'ID'          =>    $user_id,
                'nickname'    =>    $data->email
            )
        );

        // Set the role
        $user = new WP_User( $user_id );
        $user->set_role( 'user' );

        return $this->response_prepare( $user );

    }

    public function war_jwt_create( $id = false ){
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
}
