<?php

class war_new_endpoint {

    public $war_config;
	public $endpoint;
    public $arg_helper;

    public function __construct(){
        $this->arg_helper = new war_arg_helper;
    }

    public function new_endpoint( $uri, $op, $cb ){
        $op = (object) $op;
        if($uri === false || $cb === false) return false;
        $op->args = ( isset($op->args) ) ? $this->arg_helper->process_args( $op->args ): array();
        return array(
            'uri' => $uri,
            'cb' => $cb,
            'options' => $op
        );
    }

    public function register_endpoint( $endpoint, $config ){
        $this->war_config = (object)$config;
        $this->endpoint = (object)$endpoint;
        register_rest_route( $this->war_config->namespace, $this->endpoint->uri, array(
            array(
                'methods' => ( isset( $this->endpoint->options->method ) ) ? $this->endpoint->options->method : 'GET',
                'callback' => [ $this, 'endpoint_callback' ],
                'permission_callback' => [ $this, 'endpoint_role_check' ],
                'args' => $this->endpoint->options->args
            )
        ) );
    }

    public function endpoint_callback( \WP_REST_Request $request ){
        $data = $this->arg_helper->get_request_args( $request, $this->war_config );
        if( is_wp_error( $data ) ) return $data;
        $data->war_config = $this->war_config;
        if( $this->endpoint->options ) $data->options = $this->endpoint->options;
        if( method_exists( $this->endpoint->cb[0], $this->endpoint->cb[1] ) )
            return call_user_func( [ $this->endpoint->cb[0], $this->endpoint->cb[1] ], $data );
        else
            return new WP_Error( 501, 'Endpoint Method Not Found' );
    }

    public function endpoint_role_check( \WP_REST_Request $request ){
        $security = new war_security;
        $auth_headers = $this->arg_helper->get_auth_headers( $request );
        return $security->role_check( $this->endpoint->options->access, $auth_headers );
    }

}
