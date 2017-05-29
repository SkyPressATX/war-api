<?php

namespace War_Api;

// use War_Api\Helpers\Param_Helper as Param_Helper;
use War_Api\Security\Role_Check as Role_Check;
use War_Api\War_Endpoint as War_Endpoint;

class War_Model {

	private $current_user;
	private $model;
	private $war_config;

	public function __construct( $model = array(), $war_config = array(), $current_user = array() ){
		$this->model = (object)$model;
		$this->war_config = $war_config;
		$this->current_user = $current_user;
	}

    public function register(){
		$this->set_model_filters();
		$this->model_endpoints = $this->create_model_endpoints();

		array_walk( $this->model_endpoints, function( $end ){
			$war_end = new War_Endpoint( $end, $this->war_config, $this->current_user );
			$war_end->register();
		});

	}

	private function create_model_endpoints(){
		return [
			'get_all' => [
				'uri' 		=> '/' . $this->model->name,
				'method'	=> \WP_REST_Server::READABLE,
				'callback' 	=> ( isset( $this->method->callback->get_all ) ) ? $this->method->callback->get_all : [ $this, 'get_items' ],
				'access' 	=> ( isset( $this->access_levels ) ) ? $this->access_levels->read : $this->model->access
			],
			'create_one' => [
				'uri' 		=> '/' . $this->model->name,
				'method' 	=> \WP_REST_Server::CREATABLE,
				'callback' 	=> ( isset( $this->method->callback->create_one ) ) ? $this->method->callback->create_one : [ $this, 'create_item' ],
				'access' 	=> ( isset( $this->access_levels ) ) ? $this->access_levels->create : $this->model->access,
				'params'	=> $this->model->params
			],
			'read_one' => [
				'uri' 		=> '/' . $this->model->name . '/(?P<id>\d+)',
				'method'	=> \WP_REST_Server::READABLE,
				'callback'  => ( isset( $this->method->callback->read_one ) ) ? $this->method->callback->read_one : [ $this, 'get_item' ],
				'access' 	=> ( isset( $this->access_levels ) ) ? $this->access_levels->read : $this->model->access
			],
			'edit_one' => [
				'uri' 		=> '/' . $this->model->name . '/(?P<id>\d+)',
				'method'    => \WP_REST_Server::EDITABLE,
				'callback'  => ( isset( $this->method->callback->edit_one ) ) ? $this->method->callback->edit_one : [ $this, 'update_item' ],
				'access' 	=> ( isset( $this->access_levels ) ) ? $this->access_levels->update : $this->model->access,
				'params'	=> $this->strip_required( $this->model->params )
			],
			'delete_one' => [
				'uri' 		=> '/' . $this->model->name . '/(?P<id>\d+)',
				'method'    => \WP_REST_Server::DELETABLE,
				'callback'  => ( isset( $this->method->callback->delete_one ) ) ? $this->method->callback->delete_one : [ $this, 'delete_item' ],
				'access' 	=> ( isset( $this->access_levels ) ) ? $this->access_levels->delete : $this->model->access
			]
		];
    }

	private function strip_required( $params ){
		array_walk( $params, function( &$v, $k ){
			if( is_array( $v ) && isset( $v[ 'required' ] ) ) unset( $v[ 'required' ] );
		});
		return $params;
	}

	private function get_access_levels(){
		if( is_bool( $this->model->access ) || empty( $this->model->access ) ) return;
		if( is_string( $this->model->access ) ){ // Set all Perm Levels to String Value
			return (object) [
				'create' => $this->model->access,
				'read' => $this->model->access,
				'update' => $this->model->access,
				'delete' => $this->model->access,
			];
		}
		$user_roles = array_reverse( (array) $this->war_config->user_roles );
		$defaults = array(
			'create' => $user_roles[1],
			'read' => $user_roles[0],
			'update' => $user_roles[1],
			'delete' => $user_roles[1]
		);
		$this->access_levels = (object)array_merge( $defaults, $this->model->access );
	}

	/***** Data Callbacks *****/
	public function create_item( $data ){
		return $data;

		// $this->data_object->create_db_connection();
		// apply_filters( 'war_pre_data_' . $this->model->name, $data, $this->model->params );
		// $created = $this->data_object->create_item( $this->model->name, $data, $this->model->params );
		// return $created;
	}

	public function get_items( $data ){
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

	public function get_item( $data ){
		return $data;
		// $assoc = ( isset( $this->model->assoc ) ) ? $this->model->assoc : false;
		// $this->data_object->create_db_connection();
		// $item = $this->data_object->data_model_get_one( $this->model->name, $data, $assoc );
		// $result = apply_filters( 'war_pre_return_' . $this->model->name, $item );
		// return $result;
	}

	public function update_item( $data ){
		return $data;

		// $this->data_object->create_db_connection();
		// return $this->data_object->data_model_update_one( $this->model->name, $data );
	}

	public function delete_item( $data ){
		return $data;

		// $this->data_object->create_db_connection();
		// return $this->data_object->data_model_delete_one( $this->model->name, $data );
	}

	private function set_model_filters(){
		// if( ! $this->model->name || empty( $this->model->params ) ) return false;
		// $this->model->params = $this->param_helper->process_args( $this->model->params );

		if( isset( $this->model->pre_data ) ) add_filter( 'war_pre_data_' . $this->model->name, $this->model->pre_data, 10, 2 );
		if( isset( $this->model->pre_return ) ) add_filter( 'war_pre_return_' . $this->model->name, $this->model->pre_return );
	}


} // END War_Model Class
