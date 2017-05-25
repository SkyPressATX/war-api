<?php

namespace War_Api;

use War_Api\Helpers\Param_Helper as Param_Helper;

class War_Model {

	private $model;
	private $war_config;
	private $param_helper;

	public function __construct( $model = array(), $war_config = array() ){
		$this->model = (object)$model;
		$this->war_config = $war_config;
		$this->param_helper = new Param_Helper;
	}

    public function register(){

		print_r( [ $this->model, $this->war_config ] );
        // $prep = $this->endpoint_prep();
        // if( is_wp_error( $prep ) ) return $prep;
        // $this->war_config = (object)$config;
        // $register_options = array(
        //     'methods' => ( isset( $this->endpoint->method ) ) ? $this->endpoint->method : 'GET',
        //     'callback' => [ $this, 'endpoint_callback' ]
        // );
		//
        // if( isset( $this->endpoint->access ) && $this->endpoint->access !== null )
        //     $register_options[ 'permission_callback' ] = [ $this, 'endpoint_role_check' ];
		//
        // if( isset( $this->endpoint->params ) && ! empty( $this->endpoint->params ) )
        //     $register_options[ 'args' ] = $this->endpoint->params;
		//
        // register_rest_route( $this->war_config->namespace, $this->endpoint->uri, [ $register_options ] );
    }

    // public function endpoint_callback( \WP_REST_Request $request ){
    //     $data = $this->arg_helper->get_request_args( $request, $this->war_config );
    //     if( is_wp_error( $data ) ) return $data;
    //     $data->war_config = $this->war_config;
    //     if( method_exists( $this->endpoint->callback[0], $this->endpoint->callback[1] ) )
    //         return call_user_func( [ $this->endpoint->callback[0], $this->endpoint->callback[1] ], $data );
    //     else
    //         return new WP_Error( 501, 'Endpoint Method Not Found' );
    // }

    // public function endpoint_role_check( \WP_REST_Request $request ){
    //     $security = new war_security;
    //     return $security->role_check( $this->endpoint->access );
    // }
	//
	// private function endpoint_prep(){
	// 	if( ! isset( $this->endpoint->uri ) || ! isset( $this->endpoint->callback ) )
	// 	return new WP_Error( 'endpoint-error', 'Missing Values' );
	// 	if( ! preg_match( '/^\//', $this->endpoint->uri ) )
	// 	$this->endpoint->uri = '/' . $this->endpoint->uri;
	// 	if( isset( $this->endpoint->params ) )
	// 	$this->endpoint->params = $this->arg_helper->process_args( $this->endpoint->params );
	// }
}
