<?php

class war_new_model {

    public $war_config;
    public $model;
    public $arg_helper;
    public $security;
    public $access_levels;
    public $auth_headers;
    public $data_object;

    public function __construct(){
        $this->arg_helper = new war_arg_helper;
    }

    public function new_model( $name = false, $op = false, $pre_data = false, $pre_return = false ){
        $op = (object) $op;
        if( $name === false || empty($op) || $op === false ) return false;
        if(! $op->args ) return false;

        $op->args = ( isset($op->args) ) ? $this->arg_helper->process_args( $op->args ): array();

        if( $pre_data ) add_filter( 'war_pre_data_' . $name, $pre_data, 10, 2 );
        if( $pre_return ) add_filter( 'war_pre_return_' . $name, $pre_return );

        return (object) array(
            'uri' => $name,
            'options' => $op
        );
    }

    public function register_model( $model, $config ){
        $this->war_config = (object)$config;
        $this->model = (object)$model;
        $this->security = new war_security;
        $this->access_levels = $this->security->get_access_levels( $this->war_config, $this->model->options->access );
        $this->data_object = new war_data;

        register_rest_route( $this->war_config->namespace, '/' . $this->model->uri, [
				[
					'methods'         => \WP_REST_Server::READABLE,
					'callback'        => [ $this, 'get_items' ],
					'permission_callback' => [ $this, 'read_permissions_check' ],
				],
				[
					'methods'         => \WP_REST_Server::CREATABLE,
					'callback'        => [ $this, 'create_item' ],
					'permission_callback' => [ $this, 'create_permissions_check' ],
					'args'            => $this->model->options->args
				]
			]
		);
		register_rest_route( $this->war_config->namespace, '/' . $this->model->uri . '/(?P<id>\d+)', [
				[
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_item' ],
					'permission_callback' => [ $this, 'read_permissions_check' ],
				],
				[
					'methods'             => \WP_REST_Server::EDITABLE,
					'callback'            => [ $this, 'update_item' ],
					'permission_callback' => [ $this, 'update_permissions_check' ],
				],
				[
					'methods'             => \WP_REST_Server::DELETABLE,
					'callback'            => [ $this, 'delete_item' ],
					'permission_callback' => [ $this, 'delete_permissions_check' ],
				]
			]
		);
    }

    /***** Permission Callbacks *****/

    public function create_permissions_check( \WP_REST_Request $request ){
        return $this->security->role_check( $this->access_levels->create, $this->arg_helper->get_auth_headers( $request ) );
    }
    public function read_permissions_check( \WP_REST_Request $request ){
        return $this->security->role_check( $this->access_levels->read, $this->arg_helper->get_auth_headers( $request ) );
    }
    public function update_permissions_check( \WP_REST_Request $request ){
        return $this->security->role_check( $this->access_levels->update, $this->arg_helper->get_auth_headers( $request ) );
    }
    public function delete_permissions_check( \WP_REST_Request $request ){
        return $this->security->role_check( $this->access_levels->delete, $this->arg_helper->get_auth_headers( $request ) );
    }

    /***** Data Callbacks *****/

    public function create_item( \WP_REST_Request $request ){
		$data = $this->get_request_args( $request );
        if( is_wp_error( $data ) ) return $data;
		apply_filters( 'war_pre_data_' . $this->model->uri, $data, $this->model->options->args );
		$created = $this->data_object->create_item( $this->model->uri, $data, $this->model->options->args );
		return $created;
    }
    public function get_items( \WP_REST_Request $request ){
        $data = $this->get_request_args( $request );
        if( is_wp_error( $data ) ) return $data;
        $result = $this->data_object->get_items( $this->model->uri, $data );
        foreach( $result as $i => &$res){
            $res = apply_filters( 'war_pre_return_' . $this->model->uri, $res );
        }
        return $result;
    }
    public function get_item( \WP_REST_Request $request ){
        $data = $this->get_request_args( $request );
        if( is_wp_error( $data ) ) return $data;
        $assoc = ( isset( $this->model->options->assoc ) ) ? $this->model->options->assoc : false;
        $item = $this->data_object->data_model_get_one( $this->model->uri, $data, $assoc );
        $result = apply_filters( 'war_pre_return_' . $this->model->uri, $item );
        return $result;
    }
    public function update_item( \WP_REST_Request $request ){
        $data = $this->get_request_args( $request );
        if( is_wp_error( $data ) ) return $data;
        return $this->data_object->data_model_update_one( $this->model->uri, $data );
    }
    public function delete_item( \WP_REST_Request $request ){
        $data = $this->get_request_args( $request );
        if( is_wp_error( $data ) ) return $data;
        return $this->data_object->data_model_delete_one( $this->model->uri, $data );
    }

    private function get_request_args( $request ){
        return $this->arg_helper->get_request_args( $request, $this->war_config );
    }
}
