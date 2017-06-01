<?php

class war_arg_helper {

    public function get_request_args( $request = [], $war_config = [] ){
        if( empty( $request ) ) return new WP_Error( 403, 'No Request Object Provided' );
        $data = (object) array();
        $data->params = ( is_object($request) ) ? (object) $request->get_params() : (object) $request;
        $cu = apply_filters( 'war_set_current_user', wp_get_current_user() );
        $data->current_user = (object) [
            'id' => $cu->ID,
            'email' => $cu->data->user_email,
            'role' => $cu->roles[0],
            'caps' => array_keys( array_filter( $cu->allcaps, function( $c ){ return $c; } ) )
        ];
        if( isset( $war_config->user_groups ) ) $data->current_user->groups = get_user_option( 'user_groups' );
        return $data;
    }

    public function get_auth_headers( $request ){
        return [
            'jwt' => $request->get_header( 'authorization' ),
            'nonce' => $request->get_header( 'x-wp-nonce' )
        ];
    }

    public function process_args( $args = array() ){
        array_walk( $args, function( &$att ){
            if( ! is_array( $att ) ) $att = [ 'type' => $att ];
        } );
        return $this->request_args( $args );
    }

    private function request_args( $args = array() ){
        // $x = array();
        foreach($args as $key => $val){
            $o = ( isset($val["options"]) ) ? $val["options"] : false;
            $x[$key] = array(
                'required' => false,
                'validate_callback' => $this->validate_param($val["type"], $o),
                'sanitize_callback' => $this->sanitize_global()
            );

            if($val["type"] == 'bool'){
                $x[$key]['default'] = false;
                $x[$key]['sanitize_callback'] = $this->sanitize_bool();
            }

            if( $val[ 'type' ] == 'array' ){
                $x[ $key ][ 'sanitize_callback' ] = $this->sanitize_array();
            }

            $x[ $key ] = array_merge( $x[ $key ], $val );
        }
        return $x;
    }

    public function validate_param( $p = 'string', $x = false ){

        if( is_array( $x ) || $p == 'enum' ) return function( $a ) use ( $p, $x ){
            if( $x === false ) return $x;
            if( $p == 'array' && is_string( $a ) && preg_match( '/^[^,]+,?/', $a ) )
                $a = explode( ',', $a );

            if( is_array( $a ) ) $res = array_filter( $a, function( $v ) use( $x ){
                return ( ! in_array( $v, $x ) );
            } );
            if( ! isset( $res ) ) $res = ( in_array( $a, $x ) );

            if( is_array( $res ) ) return empty( $res );
            return $res;
        };

        switch ($p){
            case 'string':
                return function($a){ return ( is_string( $a ) || is_bool( $a ) ); };
                break;
            case 'text':
                return function($a){ return ( is_string( $a ) ); };
                break;
            case 'integer':
                return function($a){ return is_numeric( $a ); };
                break;
            case 'date':
                return function($a){
                    $match = preg_match('/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/',$a);
                    return ($match === 1) ? true : false;
                };
                break;
            case 'array':
                return function($a){ return ( is_array($a) || ( is_string( $a ) && preg_match( '/^[^,]+,?/', $a ) ) ); }; //If is string, then it needs to be a CSL
                break;
            case 'object':
                return function($a){ return is_object($a); };
                break;
            case 'bool':
                return function($a){ return ( is_bool($a) || $a = preg_match( '/^1|0|true|false$/', $a ) ); };
                break;
            case 'email':
                return function($a){ $email = filter_var($a, FILTER_SANITIZE_EMAIL); return (filter_var($email, FILTER_VALIDATE_EMAIL) !== false); };
                break;
        }
        return function($a){ return true; };
    }

    public function sanitize_global(){
        return function( $a ){
            return ( is_numeric( $a ) ) ? floatval( $a ) : $a;
        };
    }

    public function sanitize_bool(){
        return function($a){ if($a === true || $a == 'true' || $a == 1){ return true; }else{ return false; } };
    }

    public function sanitize_array(){
        return function( $a ){
            if( is_string( $a ) ) $a = explode( ',', $a );
            array_walk( $a, function( &$v ){
                $v = trim( $v );
                $v = ( is_numeric( $v ) ) ? floatval( $v ) : $v;
            } );
            return $a;
        };
    }

}