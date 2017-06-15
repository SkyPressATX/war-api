<?php

namespace War_Api;

use War_Api\Helpers\Param_Helper as Param_Helper;
use War_Api\Security\Role_Check as Role_Check;

class War_Endpoint {

	private $current_user;
	private $endpoint;
	private $war_config;
	private $param_helper;

	public function __construct( $endpoint = array(), $war_config = array(), $current_user = array() ){
		$this->current_user = $current_user;
		$this->endpoint = (object)$endpoint;
		$this->war_config = $war_config;
		$this->param_helper = new Param_Helper;
	}

    public function register(){
        $prep = $this->endpoint_prep();
        if( is_wp_error( $prep ) ) return $prep;

		$register_options = array(
			'methods' => ( isset( $this->endpoint->method ) ) ? $this->endpoint->method : \WP_REST_Server::READABLE,
			'callback' => [ $this, 'endpoint_callback' ]
		);

        if( isset( $this->endpoint->access ) && $this->endpoint->access !== null )
            $register_options[ 'permission_callback' ] = [ $this, 'endpoint_role_check' ];

        if( isset( $this->endpoint->params ) && ! empty( $this->endpoint->params ) )
            $register_options[ 'args' ] = $this->endpoint->params;

        register_rest_route( $this->war_config->namespace, $this->endpoint->uri, [ $register_options ] );
    }

    public function endpoint_callback( \WP_REST_Request $request ){
		try {
	        $data = $this->param_helper->get_request_args( $request );
	        if( is_wp_error( $data ) ) return $data;

	        $data->war_config = $this->war_config;
			$data->current_user = $this->current_user;

	        if( method_exists( $this->endpoint->callback[0], $this->endpoint->callback[1] ) )
	            return call_user_func( [ $this->endpoint->callback[0], $this->endpoint->callback[1] ], $data );
	        else
	            throw new \Exception( 'Endpoint Method Not Found' );
		} catch( \Exception $e ){
			return new \WP_REST_Response( $e->getMessage() );
		}
    }

    public function endpoint_role_check( \WP_REST_Request $request ){
        $role_check = new Role_Check( $this->endpoint->access, $this->current_user );
        return $role_check->has_access();
    }

	private function endpoint_prep(){
		if( ! isset( $this->endpoint->uri ) )
			throw new \Exception( 'Missing Valid URI' );
		if( ! isset( $this->endpoint->callback ) )
			throw new \Exception( 'Missing Valid Callback' );

		if( ! preg_match( '/^\//', $this->endpoint->uri ) )
			$this->endpoint->uri = '/' . $this->endpoint->uri;

		if( isset( $this->endpoint->params ) )
			$this->endpoint->params = $this->param_helper->process_args( $this->endpoint->params );

		if( $this->endpoint->access === null ) $this->endpoint->access = 'null';
		
		if( ! isset( $this->endpoint->access ) )
			$this->endpoint->access = $this->war_config->default_access;
	}
}
