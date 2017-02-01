<?php

// require_once "war_response.php";
require_once __DIR__ . "/../vendor/autoload.php";
use \Firebase\JWT\JWT;

// class war_security extends war_response {
class war_security {

    public $current_user = false;

    public function role_check( &$cap = false, &$auth_headers ){
        if($cap === null) return true; // Means Public Endpoint

        /***** Make sure the user is Authenticated *****/
        $this->current_user = $this->security_set_current_user();
        $authed = (! $this->current_user ) ? $this->security_auth_check( $auth_headers ) : true;
        if( $authed === false || is_wp_error( $authed ) ) return $authed;


        /***** Return if user has cap *****/
        if ( $cap === true && ( $authed === true || $authed == 'jwt' ) ) return true; //Any user role, but must be a user
        if ( $cap === false && ( $authed === true || $authed == 'nonce' ) ) return true; //No User required, but must have Nonce

        /***** If we get here, then we need to check user roles / caps / groups *****/
        $cleared = false;
        // $cleared = ($this->current_user->has_cap( $cap ) || $this->current_user->has_cap( 'administrator' ) );
        if( is_array( $cap ) ){
            if( ! $this->current_user->has_prop( 'user_groups' ) ) return false;
            foreach( $cap as $group => $c ){
                if( in_array( $group, $this->current_user->user_groups ) && $cleared === false ) $cleared = in_array( $c, $this->current_user->allcaps );
            }
        } else {
            $cleared = in_array( $cap, $this->current_user->allcaps );
        }

        return $cleared;
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

    private function security_set_current_user( $user_id = false ){
        $cu = ( is_int( $user_id ) ) ? wp_set_current_user( $user_id ) : wp_get_current_user();
        if($cu->ID !== 0 ){
            $cu->get_role_caps();
            $this->current_user = $cu;
            $this->current_user->user_groups = get_user_option( 'user_groups' );
        }
        return $this->current_user;
    }

    private function security_nonce_check( $nonce ){
        if(empty($nonce)) return false;
        return wp_verify_nonce($nonce,'wp_rest');
    }

    private function security_key_check( $key ){
        if(empty($key)) return false;
        $user_id = $this->security_key_decode( $key );
        if( is_wp_error( $user_id) ) return $user_id;
        if( $user_id == 0 ) return false;
        $this->security_set_current_user( $user_id );
        return true;
    }

    private function security_key_decode( $auth_header = false ){
        if($auth_header === false) return false;
        list($token) = sscanf($auth_header, 'Bearer %s');
        if(!$token) return false;

        try {
            $decoded_token = JWT::decode($token, AUTH_KEY, array('HS256'));
            return $this->security_validate_jwt((object)$decoded_token);
        } catch (Exception $e){
            return new WP_Error( 403, $e->getMessage() );
        }
    }

    private function security_validate_jwt( $decoded_token ){

        if( $decoded_token->iss != get_bloginfo('url')) $fail = 'Wrong URL';
        if( ! isset($decoded_token->data->user->id) ) $fail = 'No User';

        if(isset($fail)) return new WP_Error(403, 'Invalid Token Params -- ' . $fail);

        return $decoded_token->data->user->id;
    }
}
