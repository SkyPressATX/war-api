<?php

class war_user_actions {

    public function war_login( $data ){
        $auth = wp_authenticate($data->params->username, $data->params->password);

        if( is_wp_error( $auth ) ) return $auth;

        wp_set_auth_cookie( $auth->ID );
        wp_set_current_user( $auth->ID );

        // return $auth;
        $war_jwt = new war_jwt;
        $jwt = $war_jwt->jwt_key_create();
        return ['jwt' => $jwt ];
    }

    public function war_logout( $request ){
        wp_logout();
        return true;
    }

    public function war_signup( $request ){
        $data = $this->war_get_request_args( $request );

        if( email_exists( $data->email ) ) return $this->response_prepare( new WP_Error( 409, 'Email Already Taken' ) );

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

}
