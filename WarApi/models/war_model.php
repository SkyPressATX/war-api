<?php

class war_new_model {

    public $war_config;
    public $model;
    public $arg_helper;
    public $security;
    public $access_levels;
    public $auth_headers;
    public $data_object;

    public function __construct( $model ){
        $this->model = $model;
        $this->arg_helper = new war_arg_helper;
    }

    private function model_prep(){
        if( ! $this->model->name || empty( $this->model->params ) ) return false;
        $this->model->params = $this->arg_helper->process_args( $this->model->params );

        if( isset( $this->model->pre_data ) ) add_filter( 'war_pre_data_' . $this->model->name, $this->model->pre_data, 10, 2 );
        if( isset( $this->model->pre_return ) ) add_filter( 'war_pre_return_' . $this->model->name, $this->model->pre_return );

    }

    public function register_model( $config ){
        $this->model_prep();
        $this->war_config = (object)$config;
        $this->security = new war_security;
        $this->access_levels = $this->security->get_access_levels( $this->war_config, $this->model->access );
        $this->data_object = new war_data;

        register_rest_route( $this->war_config->namespace, '/' . $this->model->name, [
				[
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_items' ],
					'permission_callback' => [ $this, 'read_permissions_check' ],
				],
				[
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'create_item' ],
					'permission_callback' => [ $this, 'create_permissions_check' ],
					'args'                => $this->model->params
				]
			]
		);
		register_rest_route( $this->war_config->namespace, '/' . $this->model->name . '/(?P<id>\d+)', [
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

        $this->data_object->create_db_connection();
		apply_filters( 'war_pre_data_' . $this->model->name, $data, $this->model->params );
		$created = $this->data_object->create_item( $this->model->name, $data, $this->model->params );
		return $created;
    }
    public function get_items( \WP_REST_Request $request ){
        $data = $this->get_request_args( $request );
        if( is_wp_error( $data ) ) return $data;

        $query = new war_dao_select;
        $query->table = $this->model->name;
        $query->filters = $data->params;
        $query->limit = 10;

        print_r( $query->get_query() );

        $this->data_object->create_db_connection();
        $result = $this->data_object->get_items( $this->model->name, $data );
        array_walk( $result, function( &$res ){
            $res = apply_filters( 'war_pre_return_' . $this->model->name, $res );
        });
        return $result;
    }
    public function get_item( \WP_REST_Request $request ){
        $data = $this->get_request_args( $request );
        if( is_wp_error( $data ) ) return $data;

        $assoc = ( isset( $this->model->assoc ) ) ? $this->model->assoc : false;
        $this->data_object->create_db_connection();
        $item = $this->data_object->data_model_get_one( $this->model->name, $data, $assoc );
        $result = apply_filters( 'war_pre_return_' . $this->model->name, $item );
        return $result;
    }
    public function update_item( \WP_REST_Request $request ){
        $data = $this->get_request_args( $request );
        if( is_wp_error( $data ) ) return $data;

        $this->data_object->create_db_connection();
        return $this->data_object->data_model_update_one( $this->model->name, $data );
    }
    public function delete_item( \WP_REST_Request $request ){
        $data = $this->get_request_args( $request );
        if( is_wp_error( $data ) ) return $data;

        $this->data_object->create_db_connection();
        return $this->data_object->data_model_delete_one( $this->model->name, $data );
    }

    private function get_request_args( $request ){
        $req = $this->arg_helper->get_request_args( $request, $this->war_config );
        if( isset( $this->model->protect ) && $this->model->protect ) $req->params->user = $req->current_user->id;
        return $req;
    }

}
