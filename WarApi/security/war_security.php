<?php

class war_security {

    public $current_user = false;


    public function role_check( $cap = false ){
        /** Get the Current User Object as set by the war_set_current_user filter **/
        if( ! $this->current_user ) $this->current_user = apply_filters( 'war_set_current_user', wp_get_current_user() );

        /** Lets break down this user object compared to the request access **/
        if($cap === null) return true; // Means Public Endpoint for all to enjoy
        if( empty( $this->current_user ) ) return false; // No User Object, no access
        if( ! isset( $this->current_user->auth_type ) ) return false; //How else did you get here?
        if( $cap === true ) return true; //We can send this along now
        if( $cap === false ) return ( $this->current_user->auth_type == 'nonce' ); //No User required, but must have Nonce

        /***** If we get here, then we need to check user roles / caps / groups *****/
        if( $this->current_user->has_cap( 'administrator' ) ) return true; // Let Admins do anything
        if( is_array( $cap ) ){
            if( ! $this->current_user->has_prop( 'user_groups' ) ) return false;
            foreach( $cap as $group => $c ){
                if( in_array( $group, $this->current_user->user_groups ) && $cleared === false )
                    $cleared = in_array( $c, $this->current_user->allcaps );
            }
        } else {
            $cleared = in_array( $cap, $this->current_user->allcaps );
        }

        return ( isset( $cleared ) ) ? $cleared : false;
    }

    public function security_is_user_logged_in(){
        /** Check JWT Header and authenticity **/
        if( ! $this->current_user && isset( $_SERVER[ 'HTTP_AUTHORIZATION' ] ) ) $this->security_key_check( $_SERVER[ 'HTTP_AUTHORIZATION' ] );
        /** Check Nonce and authenticity **/
        if( ! $this->current_user && isset( $_SERVER[ 'HTTP_X_WP_NONCE' ] ) ) $this->security_nonce_check( $_SERVER[ 'HTTP_X_WP_NONCE' ] );
        /** Get WP Current User if all else fails **/
        if( ! $this->current_user ) $this->current_user = $this->security_set_current_user();

    }
    public function security_auth_check( &$auth_headers ){

        $result = false;

        /***** First check if a WebToken is used *****/
        if( isset( $auth_headers["jwt"] ) ){
            $auth = $this->security_key_check( $auth_headers["jwt"] );
            $result = ( is_wp_error( $auth ) || $auth === false ) ? $auth : 'jwt';
        }

        /***** If not, then check for a WordPress Nonce *****/
        if( isset( $auth_headers["nonce"] ) ){
            $auth = $this->security_nonce_check( $auth_headers["nonce"] );
            $result = ( is_wp_error( $auth ) || $auth === false ) ? $auth : 'nonce';
        }

        /***** Fail if we get here *****/
        return $result;
    }

    /**
     * security_get_access_levels
     *
     * @return array
     */
    public function get_access_levels( $war_config, $access_options = array() ) {
        if( is_string($access_options) ){ // Set all Perm Levels to String Value
            return (object) [
                'create' => $access_options,
                'read' => $access_options,
                'update' => $access_options,
                'delete' => $access_options,
            ];
        }
        $user_roles = array_reverse( (array) $war_config->user_roles );
        $defaults = array(
            'create' => $user_roles[1],
            'read' => $user_roles[0],
            'update' => $user_roles[1],
            'delete' => $user_roles[1]
        );
        $access_levels = array_merge($defaults, $access_options );
        return (object) $access_levels;
    }

    public function security_get_current_user_id(){
        $cu = $this->security_set_current_user();
        return ( sizeof($cu->allcaps) == 1 ) ? $cu->ID : false;
    }

    public function security_get_current_user(){
        return apply_filters( 'war_set_current_user', [] );
    }

    private function security_set_current_user( $user_id = false, $auth_type = false ){
        $cu = ( is_int( $user_id ) ) ? wp_set_current_user( $user_id ) : wp_get_current_user();
        if($cu->ID !== 0 ){
            $cu->get_role_caps();
            $this->current_user = $cu;
            $this->current_user->user_groups = get_user_option( 'user_groups' );
        }
        if( $auth_type ) $cu->data->auth_type = $auth_type;
        return $this->current_user;
    }

    private function security_nonce_check( $nonce ){
        if( empty( $nonce ) ) return false;
        if( ! wp_verify_nonce( $nonce, 'wp_rest' ) ) return false;
        if( wp_verify_nonce( $nonce, 'wp_rest' ) ) return $this->security_set_current_user( false, 'nonce' );

    }

    private function security_key_check( $key ){
        if(empty($key)) return false;

        $war_jwt = new war_jwt;
        $user_id = $war_jwt->jwt_key_decode( $key );

        if( is_wp_error( $user_id) ) return $user_id;
        if( $user_id == 0 ) return false;
        return $this->security_set_current_user( $user_id, 'jwt' );
    }

}
