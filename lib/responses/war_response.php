<?php

class war_response {

    public function response_prepare( $r = null, $s = 200 ){
        //Return WP Errors
        $error = $this->response_is_error( $r );
        if( $error !== false ) return $error;

        //Return 200's
        if( $s === 200 ) return $this->response_return_success( $r );

        //Return all others
        return new WP_REST_Response( $r, $s );
    }

    public function response_local_call( $r = null ){
        $err = $this->response_is_error( $r );
        if( $err !== false ) return $err;
        return $r;
    }

    public function response_logged_in( $r ){
        if( is_wp_error( $r ) ) return $this->response_return_error( 'Username or Password Is Incorrect', 401 );
        return $this->response_return_success( $r );
    }

    private function response_return_error( $message = null, $code = 500 ){
        return new WP_REST_Response( [ 'error' => $message ], $code );
    }

    private function response_return_success($r){
        return new WP_REST_Response( $r, 200 );
    }

    private function response_is_error($r){
        if( is_wp_error( $r ) ){
            return $this->response_return_error( $r->get_error_message(), $r->get_error_code() );
        }
        return false;
    }


}
