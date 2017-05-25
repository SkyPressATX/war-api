<?php

namespace War_Api;

use War_Api\Helpers\Param_Helper as Param_Helper;
use War_Api\Security\Role_Check as Role_Check;

class War_Model {

	private $current_user;
	private $model;
	private $war_config;
	private $param_helper;

	public function __construct( $model = array(), $war_config = array(), $current_user = array() ){
		$this->model = (object)$model;
		$this->war_config = $war_config;
		$this->current_user = $current_user;
		$this->param_helper = new Param_Helper;
	}

    public function register(){
		$prep = $this->model_prep();
		if( is_wp_error( $prep ) ) return $prep;

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
		$role_check = new Role_Check( $this->model->access, $this->current_user, $this->war_config );
		return $role_check->model_has_access( 'create' );
	}
	public function read_permissions_check( \WP_REST_Request $request ){
		$role_check = new Role_Check( $this->model->access, $this->current_user, $this->war_config );
		return $role_check->model_has_access( 'read' );
	}
	public function update_permissions_check( \WP_REST_Request $request ){
		$role_check = new Role_Check( $this->model->access, $this->current_user, $this->war_config );
		return $role_check->model_has_access( 'update' );
	}
	public function delete_permissions_check( \WP_REST_Request $request ){
		$role_check = new Role_Check( $this->model->access, $this->current_user, $this->war_config );
		return $role_check->model_has_access( 'delete' );
	}

	/***** Data Callbacks *****/
	public function create_item( \WP_REST_Request $request ){
		$data = $this->get_request_args( $request );
		if( is_wp_error( $data ) ) return $data;

		return $data;

		// $this->data_object->create_db_connection();
		// apply_filters( 'war_pre_data_' . $this->model->name, $data, $this->model->params );
		// $created = $this->data_object->create_item( $this->model->name, $data, $this->model->params );
		// return $created;
	}

	public function get_items( \WP_REST_Request $request ){
		$data = $this->get_request_args( $request );
		if( is_wp_error( $data ) ) return $data;

		return $data;

		// $query = new war_dao_select;
		// $query->table = $this->model->name;
		// $query->filters = $data->params;
		// $query->limit = 10;
		//
		// print_r( $query->get_query() );
		//
		// $this->data_object->create_db_connection();
		// $result = $this->data_object->get_items( $this->model->name, $data );
		// array_walk( $result, function( &$res ){
		// 	$res = apply_filters( 'war_pre_return_' . $this->model->name, $res );
		// });
		// return $result;
	}

	public function get_item( \WP_REST_Request $request ){
		$data = $this->get_request_args( $request );
		if( is_wp_error( $data ) ) return $data;

		return $data;
		// $assoc = ( isset( $this->model->assoc ) ) ? $this->model->assoc : false;
		// $this->data_object->create_db_connection();
		// $item = $this->data_object->data_model_get_one( $this->model->name, $data, $assoc );
		// $result = apply_filters( 'war_pre_return_' . $this->model->name, $item );
		// return $result;
	}

	public function update_item( \WP_REST_Request $request ){
		$data = $this->get_request_args( $request );
		if( is_wp_error( $data ) ) return $data;

		return $data;

		// $this->data_object->create_db_connection();
		// return $this->data_object->data_model_update_one( $this->model->name, $data );
	}

	public function delete_item( \WP_REST_Request $request ){
		$data = $this->get_request_args( $request );
		if( is_wp_error( $data ) ) return $data;

		return $data;

		// $this->data_object->create_db_connection();
		// return $this->data_object->data_model_delete_one( $this->model->name, $data );
	}

	private function get_request_args( $request ){
		$req = $this->param_helper->get_request_args( $request, $this->war_config );
		if( isset( $this->model->protect ) && $this->model->protect ) $req->params->user = $req->current_user->id;
		return $req;
	}

	private function model_prep(){
		if( ! $this->model->name || empty( $this->model->params ) ) return false;
		$this->model->params = $this->param_helper->process_args( $this->model->params );

		if( isset( $this->model->pre_data ) ) add_filter( 'war_pre_data_' . $this->model->name, $this->model->pre_data, 10, 2 );
		if( isset( $this->model->pre_return ) ) add_filter( 'war_pre_return_' . $this->model->name, $this->model->pre_return );
	}


} // END War_Model Class
