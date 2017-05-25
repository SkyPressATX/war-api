<?php

class war_global_helpers {

    /**
     * Is Rest Request
     * Check REQUEST_URI against $api_prefix
     *
     * @param $api_prefix Sting
     * @return Bool
     */
    public function is_rest_request( &$api_prefix ){
        $url = explode('/',$_SERVER["REQUEST_URI"]);
        array_shift($url);
        $is_rest = ( $api_prefix === $url[0] || $url[0] === 'wp-json' );
        define( 'IS_REST', $is_rest );
        return $is_rest;
    }

    /**
     * Format Caps
     * Format User Role capabilities
     *
     * @param $caps Array
     * @return $array
     */
    public function format_caps( $role = false, $caps = [] ){
        $result = array();
        foreach( $caps as $cap ){
            if( ! is_array( $cap ) ) $cap = [ $cap ];
            if( $role && in_array( $role, $cap ) ) $result[ $role ] = true;
            else foreach( $cap as $c ){
                $result[$c] = true;
            }
        }
        return $result;
    }

    public function get_old_api_prefix(){
        $rewrite_rules = get_option( 'rewrite_rules' );
        $old_prefix = NULL;
        if( ! is_array( $rewrite_rules ) ) return $old_prefix;
        foreach( $rewrite_rules as $rule => $dest ){
            if( $dest === 'index.php?rest_route=/' ) $old_prefix = preg_replace( '/[^a-z]*/', '', $rule );
        }
        return $old_prefix;
    }

    public function war_encrypt( $string ){
        $iv_size = mcrypt_get_iv_size( MCRYPT_RIJNDAEL_256, MCRYPT_MODE_CFB );
        $iv = mcrypt_create_iv( $iv_size, MCRYPT_RAND) ;
        $key = hash( 'sha256', SECURE_AUTH_KEY, true );
        $enc = mcrypt_encrypt( MCRYPT_RIJNDAEL_256, $key, $string, MCRYPT_MODE_CFB, $iv);
        $com = $iv . $enc;
        return base64_encode( $com );
    }

    public function war_decrypt( $string ){
        if(! $string) return true;
        $iv_size = mcrypt_get_iv_size( MCRYPT_RIJNDAEL_256, MCRYPT_MODE_CFB );
        $debase = base64_decode( $string );
        $iv = substr( $debase, 0, $iv_size );
        $val = substr( $debase, $iv_size );
        $dc = mcrypt_decrypt( MCRYPT_RIJNDAEL_256, hash( 'sha256', SECURE_AUTH_KEY, true ), $val, MCRYPT_MODE_CFB, $iv );
        return ( $dc ) ? $dc : $val;
    }

}
