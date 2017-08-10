<?php

namespace War_Api;

// use War_Api\Helpers\Param_Helper as Param_Helper;
use War_Api\Security\Role_Check as Role_Check;
use War_Api\War_Endpoint as War_Endpoint;
use War_Api\Data\DAO as DAO;
use War_Api\Data\Query_Search as Query_Search;
use War_Api\Helpers\Param_Helper as Param_Helper;
use War_Api\Helpers\Global_Helpers as Global_Helpers;

class War_Model {

	private $current_user;
	private $model;
	private $war_config;
	private $param_helper;
	private $db;

	public function __construct( $model = array(), $war_config = array(), $current_user = array() ){
		$this->model = (object)$model;

		$this->war_config = $war_config;
		$this->current_user = $current_user;
		$this->param_helper = new Param_Helper( $this->war_config );
	}

    public function register(){
		$this->set_model_filters();
		$this->get_access_levels();
		$this->model_endpoints = $this->create_model_endpoints();

		array_walk( $this->model_endpoints, function( $end ){
			$war_end = new War_Endpoint( $end, $this->war_config, $this->current_user );
			$war_end->register();
		});

	}

	private function create_model_endpoints(){
		return [
			'read_records' => [
				'uri' 		=> '/' . $this->model->name,
				'method'	=> \WP_REST_Server::READABLE,
				'callback' 	=> ( isset( $this->method->callback->read_items ) ) ? $this->method->callback->read_items : [ $this, 'read_records' ],
				'access' 	=> ( isset( $this->access_levels ) ) ? $this->access_levels->read : $this->model->access,
				'params'	=> $this->param_helper->get_read_records_params()
			],
			'create_record' => [
				'uri' 		=> '/' . $this->model->name,
				'method' 	=> \WP_REST_Server::CREATABLE,
				'callback' 	=> ( isset( $this->method->callback->create_item ) ) ? $this->method->callback->create_item : [ $this, 'create_record' ],
				'access' 	=> ( isset( $this->access_levels ) ) ? $this->access_levels->create : $this->model->access,
				'params'	=> $this->model->params
			],
			'read_record' => [
				'uri' 		=> '/' . $this->model->name . '/(?P<id>\d+)',
				'method'	=> \WP_REST_Server::READABLE,
				'callback'  => ( isset( $this->method->callback->read_item ) ) ? $this->method->callback->read_item : [ $this, 'read_record' ],
				'access' 	=> ( isset( $this->access_levels ) ) ? $this->access_levels->read : $this->model->access,
				'params'	=> $this->param_helper->get_read_record_params()
			],
			'edit_record' => [
				'uri' 		=> '/' . $this->model->name . '/(?P<id>\d+)',
				'method'    => \WP_REST_Server::EDITABLE,
				'callback'  => ( isset( $this->method->callback->update_item ) ) ? $this->method->callback->update_item : [ $this, 'edit_record' ],
				'access' 	=> ( isset( $this->access_levels ) ) ? $this->access_levels->update : $this->model->access,
				'params'	=> $this->strip_required( $this->model->params )
			],
			'delete_record' => [
				'uri' 		=> '/' . $this->model->name . '/(?P<id>\d+)',
				'method'    => \WP_REST_Server::DELETABLE,
				'callback'  => ( isset( $this->method->callback->delete_item ) ) ? $this->method->callback->delete_item : [ $this, 'delete_record' ],
				'access' 	=> ( isset( $this->access_levels ) ) ? $this->access_levels->delete : $this->model->access
			]
		];
    }

	/***** Data Callbacks *****/

	/*
	* Read All Items
	*
	* Should only handle pre_data and pre_return filters, $db connection, and receiving items from the DAO
	*/
	public function read_records( $request ){
		try {
			$request = apply_filters( 'war_pre_data_' . $this->model->name, $request );
			$db_info = ( property_exists( $this->model, 'db_info' ) ) ? $this->model->db_info : array();

			$dao = new DAO( $db_info, $this->model, $request, $this->war_config );
			$response = $dao->read_all();

			if( property_exists( $this->model, 'pre_return ' ) ){
				if( empty( $response->data ) ) return apply_filters( 'war_pre_return_' . $this->model->name, $response->data );
				array_walk( $response->data, function( &$item ){
					$item = apply_filters( 'war_pre_return_' . $this->model->name, $item );
				});
			}

			return $response;
		}catch( \Exception $e ){
			throw new \Exception( $e->getMessage() );
		}
	}

	public function read_record( $request ){
		try {
			$request = apply_filters( 'war_pre_data_' . $this->model->name, $request );
			$db_info = ( property_exists( $this->model, 'db_info' ) ) ? $this->model->db_info : array();

			$dao = new DAO( $db_info, $this->model, $request, $this->war_config );
			$item = $dao->read_one();

			$result = apply_filters( 'war_pre_return_' . $this->model->name, $item );
			return $result;
		}catch( \Exception $e ){
			throw new \Exception( $e->getMessage() );
		}

	}

	public function create_record( $request ){
		try {
			$request = apply_filters( 'war_pre_data_' . $this->model->name, $request );
			$db_info = ( property_exists( $this->model, 'db_info' ) ) ? $this->model->db_info : array();

			$dao = new DAO( $db_info, $this->model, $request, $this->war_config );
			return $dao->insert_one();
		} catch( \Exception $e ){
			return $e->getMessage();
		}
	}

	public function update_record( $request ){
		try {
			$request = apply_filters( 'war_pre_data_' . $this->model->name, $request );
			$db_info = ( property_exists( $this->model, 'db_info' ) ) ? $this->model->db_info : array();

			$dao = new DAO( $db_info, $this->model, $request, $this->war_config );
			return $dao->update_one();
		}catch( \Exception $e ){
			throw new \Exception( $e->getMessage() );
		}


	}

	public function delete_record( $request ){
		$request = apply_filters( 'war_pre_data_' . $this->model->name, $request );
		$db_info = ( property_exists( $this->model, 'db_info' ) ) ? $this->model->db_info : array();

		$dao = new DAO( $db_info, $this->model, $request, $this->war_config );
		return $dao->delete_one();
	}

	private function set_model_filters(){
		if( isset( $this->model->pre_data ) ) add_filter( 'war_pre_data_' . $this->model->name, $this->model->pre_data, 15 );
		if( isset( $this->model->pre_return ) ) add_filter( 'war_pre_return_' . $this->model->name, $this->model->pre_return, 15 );
	}

	private function strip_required( $params ){
		array_walk( $params, function( &$v, $k ){
			if( is_array( $v ) && isset( $v[ 'required' ] ) ) unset( $v[ 'required' ] );
		});
		return $params;
	}

	private function get_access_levels(){
		if( is_bool( $this->model->access ) || $this->model->access === NULL ) return;
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
			'create' => $this->war_config->default_access,
			'read' => $this->war_config->default_access,
			'update' => $this->war_config->default_access,
			'delete' => $this->war_config->default_access
		);
		$this->access_levels = (object)array_merge( $defaults, $this->model->access );
	}
} // END War_Model Class
